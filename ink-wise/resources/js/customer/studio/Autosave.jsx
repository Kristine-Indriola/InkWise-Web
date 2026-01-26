export function createAutosaveController(options = {}) {
  const {
    collectSnapshot,
    routes = {},
    statusLabel = null,
    statusDot = null,
    csrfToken = null,
    debounce = 1200,
    onAfterSave = null,
    imageSizeThreshold = 100 * 1024, // 100 KB
    maxParallelUploads = 3,
  } = options;

  if (typeof collectSnapshot !== 'function') {
    throw new Error('createAutosaveController requires a collectSnapshot function');
  }

  let timerId = null;
  let pendingReason = null;
  let inflightPromise = null;
  let lastSavedAt = null;

  const applyStatus = (state, meta = {}) => {
    if (statusDot instanceof HTMLElement) statusDot.setAttribute('data-state', state);
    if (statusLabel instanceof HTMLElement) {
      switch (state) {
        case 'saving': statusLabel.textContent = 'Saving…'; break;
        case 'dirty': statusLabel.textContent = 'Unsaved changes'; break;
        case 'error': statusLabel.textContent = 'Save failed'; break;
        case 'saved': {
          const timestamp = meta.timestamp instanceof Date ? meta.timestamp : new Date();
          statusLabel.textContent = `Saved ${timestamp.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}`;
          break;
        }
        default: statusLabel.textContent = 'Saved';
      }
    }
  };

  // Utilities
  const dataUrlRegex = /data:(image\/[a-z0-9.+-]+;base64,([A-Za-z0-9+/=\n]+))/ig;

  function dataUrlToBlob(dataUrl) {
    const match = /^data:([^;]+);base64,(.*)$/.exec(dataUrl.replace(/\s+/g, ''));
    if (!match) return null;
    const mime = match[1];
    const bstr = atob(match[2]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);
    while (n--) u8arr[n] = bstr.charCodeAt(n);
    return new Blob([u8arr], { type: mime });
  }

  const uploadBlob = async (blob, filename = 'upload.png') => {
    const uploadEndpoint = routes.uploadImage || '/design/upload-image';
    const fd = new FormData();
    fd.append('image', blob, filename);

    const res = await fetch(uploadEndpoint, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
    });

    if (!res.ok) {
      const text = await res.text().catch(() => null);
      const e = new Error(`Upload failed: ${res.status}`);
      e.status = res.status; e.responseText = text;
      throw e;
    }

    return res.json(); // expected { path, url }
  };

  // Find data URLs inside a snapshot and return replacements
  const findAndUploadDataUrls = async (snapshot) => {
    if (!snapshot || typeof snapshot !== 'object') return snapshot;

    const uploads = [];

    // Helper: scan a string and return array of {full, dataUrl}
    const scanStringForDataUrls = (str) => {
      const found = [];
      let m;
      while ((m = dataUrlRegex.exec(str)) !== null) {
        found.push({ full: m[0], dataUrl: m[1] });
      }
      return found;
    };

    // Collect candidates from sides.svg, sides.preview, preview.images, design.images
    const candidates = [];

    try {
      const sides = snapshot?.design?.sides ?? {};
      Object.keys(sides).forEach((sideKey) => {
        const s = sides[sideKey];
        if (!s) return;
        if (typeof s.svg === 'string') {
          scanStringForDataUrls(s.svg).forEach((f) => candidates.push({ location: ['design','sides', sideKey, 'svg'], type: 'svg', dataUrl: f.dataUrl, full: f.full }));
        }
        if (typeof s.preview === 'string' && s.preview.startsWith('data:')) {
          candidates.push({ location: ['design','sides', sideKey, 'preview'], type: 'preview', dataUrl: s.preview, full: s.preview });
        }
      });

      if (snapshot && snapshot.preview && Array.isArray(snapshot.preview.images)) {
        snapshot.preview.images.forEach((img, idx) => {
          if (typeof img === 'string' && img.startsWith('data:')) {
            candidates.push({ location: ['preview','images', idx], type: 'preview', dataUrl: img, full: img });
          }
        });
      }

      if (snapshot && snapshot.design && Array.isArray(snapshot.design.images)) {
        snapshot.design.images.forEach((img, idx) => {
          // if object with `src` or `dataUrl`
          if (typeof img === 'string' && img.startsWith('data:')) {
            candidates.push({ location: ['design','images', idx], type: 'image', dataUrl: img, full: img });
          } else if (img && (img.dataUrl || img.src) && (img.dataUrl || img.src).startsWith('data:')) {
            candidates.push({ location: ['design','images', idx], type: 'image', dataUrl: img.dataUrl || img.src, full: img.dataUrl || img.src });
          }
        });
      }
    } catch (e) {
      console.warn('[Autosave] Error scanning snapshot for data URLs', e);
    }

    // Deduplicate by dataUrl
    const unique = [];
    const seen = new Set();
    candidates.forEach((c) => {
      if (!seen.has(c.dataUrl)) { seen.add(c.dataUrl); unique.push(c); }
    });

    // Filter by size threshold
    const toUpload = unique.filter((c) => {
      return (c.dataUrl && c.dataUrl.length > imageSizeThreshold);
    });

    if (!toUpload.length) return snapshot;

    // Upload with limited concurrency
    const results = new Map();
    const queue = toUpload.slice();
    const runWorker = async () => {
      while (queue.length) {
        const item = queue.shift();
        try {
          const blob = dataUrlToBlob(item.dataUrl);
          if (!blob) throw new Error('Failed to parse data URL');
          const filename = `design-${Date.now()}.png`;
          const json = await uploadBlob(blob, filename);
          if (json && json.url) {
            results.set(item.dataUrl, json.url);
          } else if (json && json.path) {
            // fallback to path
            results.set(item.dataUrl, json.path);
          }
        } catch (e) {
          console.warn('[Autosave] Upload failed for one image, will keep inline data URL', e);
          // do not throw; keep original data URL
        }
      }
    };

    // start workers
    const workers = [];
    for (let i = 0; i < Math.min(maxParallelUploads, queue.length + toUpload.length); i++) workers.push(runWorker());
    await Promise.all(workers);

    // Apply replacements in snapshot
    const replacer = (value) => {
      if (typeof value !== 'string') return value;
      let changed = value;
      results.forEach((url, dataUrl) => {
        changed = changed.split(dataUrl).join(url);
      });
      return changed;
    };

    // Deep copy snapshot to avoid mutating original
    const cloned = JSON.parse(JSON.stringify(snapshot));

    // Replace in sides.svg and sides.preview
    const sides = cloned?.design?.sides ?? {};
    Object.keys(sides).forEach((sideKey) => {
      const s = sides[sideKey];
      if (!s) return;
      if (typeof s.svg === 'string') s.svg = replacer(s.svg);
      if (typeof s.preview === 'string') s.preview = replacer(s.preview);
    });

    if (cloned.preview && Array.isArray(cloned.preview.images)) cloned.preview.images = cloned.preview.images.map(replacer);
    if (cloned.design && Array.isArray(cloned.design.images)) cloned.design.images = cloned.design.images.map((img) => {
      if (typeof img === 'string') return replacer(img);
      if (img && img.dataUrl) img.dataUrl = replacer(img.dataUrl);
      if (img && img.src) img.src = replacer(img.src);
      return img;
    });

    return cloned;
  };

  const sendRequest = async (snapshot) => {
    const endpoint = routes.autosave;
    if (!endpoint) {
      console.warn('[InkWise Studio] Autosave endpoint is not configured.');
      return null;
    }

    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
      },
      credentials: 'same-origin',
      body: JSON.stringify(snapshot),
    });

    if (!response.ok) {
      const error = new Error(`Autosave responded with ${response.status}`);
      error.status = response.status;
      error.responseText = await response.text().catch(() => null);
      throw error;
    }

    try { return await response.json(); } catch (parseError) { return {}; }
  };

  const saveNow = async (reason) => {
    console.log('[Autosave:advanced] Saving', reason);
    if (inflightPromise) {
      try { await inflightPromise; } catch (_) {}
    }

    pendingReason = null;
    applyStatus('saving');

    let snapshot;
    try { snapshot = await collectSnapshot(reason); } catch (collectionError) {
      console.error('[InkWise Studio] Failed to collect autosave snapshot.', collectionError);
      applyStatus('error');
      throw collectionError;
    }

    if (!snapshot || typeof snapshot !== 'object') {
      applyStatus('saved', { timestamp: new Date() });
      return null;
    }

    // Upload large embedded images and replace inline data URLs with server URLs
    let processedSnapshot;
    try {
      processedSnapshot = await findAndUploadDataUrls(snapshot);
    } catch (e) {
      console.warn('[Autosave] Image uploads failed; proceeding with original snapshot', e);
      processedSnapshot = snapshot;
    }

    inflightPromise = sendRequest(processedSnapshot);

    try {
      const result = await inflightPromise;
      lastSavedAt = new Date();
      applyStatus('saved', { timestamp: lastSavedAt });
      if (typeof onAfterSave === 'function') {
        try { onAfterSave(result, processedSnapshot); } catch (callbackError) { console.warn('[InkWise Studio] Autosave onAfterSave callback failed.', callbackError); }
      }
      return result;
    } catch (saveError) {
      console.error('[InkWise Studio] Autosave request failed.', saveError);
      applyStatus('error');
      throw saveError;
    } finally { inflightPromise = null; }
  };

  const schedule = (reason = 'change') => {
    console.log('[Autosave:advanced] Scheduling', reason);
    pendingReason = reason;
    if (timerId) clearTimeout(timerId);
    applyStatus('dirty');
    timerId = window.setTimeout(() => {
      timerId = null; void saveNow(pendingReason).catch(() => {});
    }, Math.max(250, debounce));
  };

  const flush = async (reason = 'flush') => {
    if (timerId) { clearTimeout(timerId); timerId = null; }
    if (inflightPromise) await inflightPromise;
    if (pendingReason !== null) return saveNow(reason);
    return null;
  };

  return {
    schedule,
    flush,
    isSaving: () => Boolean(inflightPromise),
    getLastSavedAt: () => lastSavedAt,
    notifyError: () => applyStatus('error'),
    markDirty: () => applyStatus('dirty'),
  };
}

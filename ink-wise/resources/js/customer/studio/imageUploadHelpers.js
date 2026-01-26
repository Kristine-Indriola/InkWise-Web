// Utility to scan payloads for large data URLs and upload them, returning a cloned payload with replacements.
export const DEFAULT_IMAGE_SIZE_THRESHOLD = 100 * 1024; // 100 KB
export const DEFAULT_MAX_PARALLEL_UPLOADS = 3;

function dataUrlToBlob(dataUrl) {
  const m = /^data:([^;]+);base64,(.*)$/s.exec(dataUrl.replace(/\s+/g, ''));
  if (!m) return null;
  const mime = m[1];
  const bstr = atob(m[2]);
  let n = bstr.length; const u8 = new Uint8Array(n);
  while (n--) u8[n] = bstr.charCodeAt(n);
  return new Blob([u8], { type: mime });
}

async function uploadBlob(blob, endpoint, csrfToken) {
  const fd = new FormData();
  fd.append('image', blob, `upload-${Date.now()}.png`);
  const res = await fetch(endpoint, {
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
  return res.json();
}

const dataUrlRegex = /data:([a-zA-Z0-9/+-.]+\/[a-zA-Z0-9.+-]+);base64,[A-Za-z0-9+/=\n]+/g;

export async function uploadSnapshotDataUrls(snapshot, options = {}) {
  const { routes = {}, csrfToken = null, imageSizeThreshold = DEFAULT_IMAGE_SIZE_THRESHOLD, maxParallelUploads = DEFAULT_MAX_PARALLEL_UPLOADS } = options;
  if (!snapshot || typeof snapshot !== 'object') return snapshot;

  const uploadEndpoint = routes.uploadImage || '/design/upload-image';

  // collect candidates
  const candidates = [];
  try {
    const sides = snapshot?.design?.sides || {};
    Object.keys(sides).forEach((sideKey) => {
      const side = sides[sideKey];
      if (!side) return;
      if (typeof side.svg === 'string') {
        const matches = side.svg.match(dataUrlRegex) || [];
        matches.forEach((m) => candidates.push({ type: 'svg', dataUrl: m }));
      }
      if (typeof side.preview === 'string' && side.preview.startsWith('data:')) candidates.push({ type: 'preview', dataUrl: side.preview });
    });

    if (snapshot?.preview && Array.isArray(snapshot.preview.images)) {
      snapshot.preview.images.forEach((img) => { if (typeof img === 'string' && img.startsWith('data:')) candidates.push({ type: 'preview', dataUrl: img }); });
    }

    if (snapshot?.design && Array.isArray(snapshot.design.images)) {
      snapshot.design.images.forEach((img) => {
        if (typeof img === 'string' && img.startsWith('data:')) candidates.push({ type: 'image', dataUrl: img });
        else if (img && (img.dataUrl || img.src) && (img.dataUrl || img.src).startsWith('data:')) candidates.push({ type: 'image', dataUrl: img.dataUrl || img.src });
      });
    }
  } catch (e) {
    console.warn('[imageUploadHelpers] Error scanning snapshot', e);
  }

  // dedupe
  const unique = [];
  const seen = new Set();
  for (const c of candidates) {
    if (!seen.has(c.dataUrl)) { seen.add(c.dataUrl); unique.push(c); }
  }

  // filter by size
  const toUpload = unique.filter((c) => c.dataUrl && c.dataUrl.length > imageSizeThreshold);
  if (!toUpload.length) return snapshot;

  // upload with concurrency
  const results = new Map();
  const queue = toUpload.slice();
  async function worker() {
    while (queue.length) {
      const item = queue.shift();
      try {
        const blob = dataUrlToBlob(item.dataUrl);
        if (!blob) throw new Error('Invalid data URL');
        const json = await uploadBlob(blob, uploadEndpoint, csrfToken);
        if (json && json.url) results.set(item.dataUrl, json.url);
        else if (json && json.path) results.set(item.dataUrl, json.path);
      } catch (e) {
        console.warn('[imageUploadHelpers] upload failed for one candidate', e);
      }
    }
  }

  const workers = [];
  for (let i = 0; i < Math.min(maxParallelUploads, toUpload.length); i++) workers.push(worker());
  await Promise.all(workers);

  // Replace occurrences in a deep clone
  const cloned = JSON.parse(JSON.stringify(snapshot));
  const replacer = (str) => {
    if (typeof str !== 'string') return str;
    let out = str;
    results.forEach((url, dataUrl) => { out = out.split(dataUrl).join(url); });
    return out;
  };

  try {
    const sides = cloned?.design?.sides || {};
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
  } catch (e) {
    console.warn('[imageUploadHelpers] Replacement failed', e);
  }

  return cloned;
}

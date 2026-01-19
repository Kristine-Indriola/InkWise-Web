export function createAutosaveController(options = {}) {
    const {
        collectSnapshot,
        routes = {},
        statusLabel = null,
        statusDot = null,
        csrfToken = null,
        debounce = 1200,
        onAfterSave = null,
    } = options;

    if (typeof collectSnapshot !== 'function') {
        throw new Error('createAutosaveController requires a collectSnapshot function');
    }

    let timerId = null;
    let pendingReason = null;
    let inflightPromise = null;
    let lastSavedAt = null;

    const applyStatus = (state, meta = {}) => {
        // Find the parent status container
        const statusContainer = (statusDot instanceof HTMLElement && statusDot.parentElement) ||
                               (statusLabel instanceof HTMLElement && statusLabel.parentElement);

        if (statusContainer instanceof HTMLElement) {
            // Remove all status classes
            statusContainer.classList.remove('topbar-status--saving', 'topbar-status--error', 'topbar-status--dirty');
            
            // Add the appropriate class
            if (state === 'saving') {
                statusContainer.classList.add('topbar-status--saving');
            } else if (state === 'error') {
                statusContainer.classList.add('topbar-status--error');
            } else if (state === 'dirty') {
                statusContainer.classList.add('topbar-status--dirty');
            }
        }

        if (statusDot instanceof HTMLElement) {
            statusDot.setAttribute('data-state', state);
        }

        if (statusLabel instanceof HTMLElement) {
            switch (state) {
                case 'saving':
                    statusLabel.textContent = 'Saving…';
                    break;
                case 'dirty':
                    statusLabel.textContent = 'Unsaved changes';
                    break;
                case 'error':
                    statusLabel.textContent = 'Save failed';
                    break;
                case 'saved': {
                    const timestamp = meta.timestamp instanceof Date ? meta.timestamp : new Date();
                    const formatted = timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    statusLabel.textContent = `Saved ${formatted}`;
                    break;
                }
                default:
                    statusLabel.textContent = 'Saved';
                    break;
            }
        }
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

        try {
            return await response.json();
        } catch (parseError) {
            return {};
        }
    };

    const saveNow = async (reason) => {
        console.log('[Autosave] Saving', reason);
        if (inflightPromise) {
            try {
                await inflightPromise;
            } catch (_) {
                // ignore prior failure, we are retrying now
            }
        }

        pendingReason = null;
        applyStatus('saving');

        let snapshot;
        try {
            snapshot = await collectSnapshot(reason);
        } catch (collectionError) {
            console.error('[InkWise Studio] Failed to collect autosave snapshot.', collectionError);
            applyStatus('error');
            throw collectionError;
        }

        if (!snapshot || typeof snapshot !== 'object') {
            applyStatus('saved', { timestamp: new Date() });
            return null;
        }

        inflightPromise = sendRequest(snapshot);

        try {
            const result = await inflightPromise;
            lastSavedAt = new Date();
            applyStatus('saved', { timestamp: lastSavedAt });
            if (typeof onAfterSave === 'function') {
                try {
                    onAfterSave(result, snapshot);
                } catch (callbackError) {
                    console.warn('[InkWise Studio] Autosave onAfterSave callback failed.', callbackError);
                }
            }
            return result;
        } catch (saveError) {
            console.error('[InkWise Studio] Autosave request failed.', saveError);
            applyStatus('error');
            throw saveError;
        } finally {
            inflightPromise = null;
        }
    };

    const schedule = (reason = 'change') => {
        console.log('[Autosave] Scheduling', reason);
        pendingReason = reason;
        if (timerId) {
            clearTimeout(timerId);
        }
        applyStatus('dirty');
        timerId = window.setTimeout(() => {
            timerId = null;
            void saveNow(pendingReason).catch(() => {});
        }, Math.max(250, debounce));
    };

    const flush = async (reason = 'flush') => {
        if (timerId) {
            clearTimeout(timerId);
            timerId = null;
        }

        if (inflightPromise) {
            await inflightPromise;
        }

        if (pendingReason !== null) {
            return saveNow(reason);
        }

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

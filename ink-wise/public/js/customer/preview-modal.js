document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('productPreviewOverlay');
    const frame = document.getElementById('productPreviewFrame');
    const closeBtn = document.getElementById('productPreviewClose');
    const triggerSelector = '.preview-trigger[data-preview-url]';

    if (!overlay || !frame || !closeBtn) {
        return;
    }

    const openPreview = (previewUrl) => {
        if (!previewUrl) return;
        frame.src = previewUrl;
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const closePreview = () => {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        // Delay clearing src to avoid flash during fade-out
        setTimeout(() => {
            frame.src = '';
        }, 200);
    };

    const registerPointerCursor = () => {
        document.querySelectorAll(triggerSelector).forEach((trigger) => {
            trigger.style.cursor = 'pointer';
        });
    };

    const handleEditorRequest = (event) => {
        if (!event || typeof event.data !== 'object' || event.data === null) {
            return;
        }

        if (event.data.type !== 'inkwise:open-editor') {
            return;
        }

        if (event.origin && event.origin !== window.location.origin) {
            return;
        }

        closePreview();

        if (event.data.href) {
            const target = (event.data.target || '_self').toLowerCase();
            if (target === '_blank') {
                window.open(event.data.href, '_blank', 'noopener');
                return;
            }

            try {
                window.location.href = event.data.href;
            } catch (error) {
                console.warn('Unable to navigate parent window to editor.', error);
            }
        }
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest(triggerSelector);
        if (!trigger) {
            return;
        }

        event.preventDefault();
        const previewUrl = trigger.getAttribute('data-preview-url');
        openPreview(previewUrl);
    });

    document.addEventListener('preview:register-triggers', registerPointerCursor);

    registerPointerCursor();

    closeBtn.addEventListener('click', closePreview);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closePreview();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && overlay.classList.contains('is-visible')) {
            closePreview();
        }
    });

    window.addEventListener('message', handleEditorRequest);
});

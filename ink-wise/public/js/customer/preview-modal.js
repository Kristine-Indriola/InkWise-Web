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
});

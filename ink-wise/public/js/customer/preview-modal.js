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

    document.querySelectorAll(triggerSelector).forEach((img) => {
        img.addEventListener('click', (event) => {
            event.preventDefault();
            const previewUrl = img.getAttribute('data-preview-url');
            openPreview(previewUrl);
        });
        img.style.cursor = 'pointer';
    });

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

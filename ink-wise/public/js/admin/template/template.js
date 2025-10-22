document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".create-form");
    const designInput = document.getElementById("design");

    if (form && designInput) {
        form.addEventListener("submit", function () {
            designInput.value = JSON.stringify({
                text: "Please join us...",
                bride: "ELEANOR",
                groom: "VINCENT",
                date: "25 October 2025",
            });
        });
    }

    const previewModal = document.getElementById('previewModal');
    const modalImage = document.getElementById('modalImg');
    const closePreview = document.getElementById('closePreview');

    if (previewModal && modalImage && closePreview) {
        document.querySelectorAll('.preview-thumb').forEach(function(img) {
            img.addEventListener('click', function() {
                modalImage.src = img.dataset.img;
                previewModal.classList.add('is-visible');
            });
        });

        closePreview.addEventListener('click', function() {
            previewModal.classList.remove('is-visible');
            modalImage.src = '';
        });

        previewModal.addEventListener('click', function(e) {
            if (e.target === this) {
                previewModal.classList.remove('is-visible');
                modalImage.src = '';
            }
        });
    }


    document.querySelectorAll('.video-swatches').forEach(group => {
        const card = group.closest('.w-full');
        if (!card) return;

        const video = card.querySelector('.template-video');
        const image = card.querySelector('.template-image');
        const source = video ? video.querySelector('source') : null;

        group.querySelectorAll('.swatch-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!(video && image && source)) return;

                const videoSrc = btn.getAttribute('data-video');
                const imageSrc = btn.getAttribute('data-image');

                if (videoSrc) {
                    image.classList.add('hidden');
                    video.classList.remove('hidden');
                    if (source.src !== videoSrc) {
                        source.src = videoSrc;
                        video.load();
                    }
                    video.play();
                }

                if (imageSrc) {
                    image.src = imageSrc;
                    image.classList.remove('hidden');
                    video.classList.add('hidden');
                }
            });
        });
    });

    // Upload to product handling
    document.querySelectorAll('.btn-upload').forEach(btn => {
        btn.addEventListener('click', function (e) {
            // If the button is inside a form and it's a submit, allow normal POST when JS is disabled.
            e.preventDefault();

            // Determine POST URL: prefer data-url, then form action
            let url = btn.getAttribute('data-url');
            if (!url) {
                const form = btn.closest('form');
                url = form ? form.getAttribute('action') : btn.getAttribute('href');
            }

            if (!url) {
                showToast('Upload URL not found', true);
                return;
            }

            // disable button to prevent double submits
            btn.classList.add('disabled');
            btn.setAttribute('aria-disabled', 'true');

            // Try to get CSRF token from meta tag or hidden input in form
            let csrf = document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').getAttribute('content') : '';
            if (!csrf) {
                const form = btn.closest('form');
                if (form) {
                    const tokenInput = form.querySelector('input[name=_token]');
                    if (tokenInput) csrf = tokenInput.value;
                }
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                }
            }).then(resp => {
                if (resp.ok) return resp.json().catch(() => ({}));
                throw new Error('Upload failed');
            }).then(data => {
                // remove the template card from the grid so it looks deleted from templates
                const card = btn.closest('.template-card');
                if (card) card.remove();

                if (data.success) {
                    showToast('Sent');
                } else if (data && data.existing) {
                    showToast('Already exists');
                } else {
                    showToast('Sent');
                }

            }).catch(err => {
                console.error(err);
                showToast('Failed to send template', true);
                btn.classList.remove('disabled');
                btn.removeAttribute('aria-disabled');
            });
        });
    });

    // simple toast implementation
    function showToast(message, isError = false) {
        let toast = document.getElementById('tw-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'tw-toast';
            toast.className = 'tw-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.toggle('error', !!isError);
        toast.classList.add('visible');
        setTimeout(() => toast.classList.remove('visible'), 3000);
    }
});

// Create Template Dropdown Functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownContainer = document.querySelector('.create-dropdown-container');
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.create-dropdown-menu');

    if (dropdownContainer && dropdownToggle && dropdownMenu) {
        let hoverTimeout;

        // Hover to open dropdown
        dropdownContainer.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            dropdownToggle.setAttribute('aria-expanded', 'true');
            dropdownMenu.setAttribute('aria-hidden', 'false');
        });

        // Hover to close dropdown (with delay)
        dropdownContainer.addEventListener('mouseleave', function() {
            hoverTimeout = setTimeout(() => {
                dropdownToggle.setAttribute('aria-expanded', 'false');
                dropdownMenu.setAttribute('aria-hidden', 'true');
            }, 300); // 300ms delay before closing
        });

        // Click to toggle (for mobile/touch devices)
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isExpanded = dropdownToggle.getAttribute('aria-expanded') === 'true';
            clearTimeout(hoverTimeout); // Clear any pending close timeout

            dropdownToggle.setAttribute('aria-expanded', !isExpanded);
            dropdownMenu.setAttribute('aria-hidden', isExpanded);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownContainer.contains(e.target)) {
                clearTimeout(hoverTimeout);
                dropdownToggle.setAttribute('aria-expanded', 'false');
                dropdownMenu.setAttribute('aria-hidden', 'true');
            }
        });

        // Close dropdown on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && dropdownToggle.getAttribute('aria-expanded') === 'true') {
                clearTimeout(hoverTimeout);
                dropdownToggle.setAttribute('aria-expanded', 'false');
                dropdownMenu.setAttribute('aria-hidden', 'true');
            }
        });

        // Handle dropdown item clicks
        dropdownMenu.addEventListener('click', function(e) {
            const item = e.target.closest('.dropdown-item');
            if (item) {
                // Close dropdown after selection
                clearTimeout(hoverTimeout);
                dropdownToggle.setAttribute('aria-expanded', 'false');
                dropdownMenu.setAttribute('aria-hidden', 'true');
            }
        });
    }
});



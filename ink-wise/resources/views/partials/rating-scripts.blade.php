<script>
(function () {
    const supportsDataTransfer = (() => {
        try {
            return typeof DataTransfer === 'function';
        } catch (error) {
            return false;
        }
    })();

    const toast = document.querySelector('[data-toast]');
    let toastTimer = null;

    function showToast(message) {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add('show');

        if (toastTimer) {
            window.clearTimeout(toastTimer);
        }

        toastTimer = window.setTimeout(() => {
            toast.classList.remove('show');
        }, 2600);
    }

    function updateStarState(container, activeValue) {
        const stars = container.querySelectorAll('[data-star]');
        stars.forEach((star) => {
            const starValue = Number(star.dataset.star || 0);
            if (starValue <= activeValue) {
                star.classList.add('is-active');
            } else {
                star.classList.remove('is-active');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const forms = document.querySelectorAll('[data-rating-form]');
        if (!forms.length) {
            return;
        }

        forms.forEach((form) => {
            const ratingInput = form.querySelector('[data-rating-input]');
            const ratingValue = form.querySelector('[data-rating-value]');
            const starButtons = form.querySelectorAll('[data-star]');
            const reviewArea = form.querySelector('[data-review]');
            const charCount = form.querySelector('[data-char-count]');
            const photoInput = form.querySelector('[data-photo-input]');
            const photoPreview = form.querySelector('[data-photo-preview]');
            const maxFiles = Number(photoInput?.dataset.maxFiles || 5);

            const initialRating = Number(ratingInput?.value || 0);
            if (initialRating > 0) {
                updateStarState(form, initialRating);
                if (ratingValue) {
                    ratingValue.textContent = `${initialRating}/5`;
                }
            }

            starButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const selectedValue = Number(button.dataset.star || 0);
                    if (!ratingInput) {
                        return;
                    }

                    ratingInput.value = String(selectedValue);
                    updateStarState(form, selectedValue);
                    if (ratingValue) {
                        ratingValue.textContent = `${selectedValue}/5`;
                    }
                });
            });

            if (reviewArea && charCount) {
                const updateCount = () => {
                    const currentLength = reviewArea.value.length;
                    charCount.textContent = `${currentLength}`;
                };

                reviewArea.addEventListener('input', updateCount);
                updateCount();
            }

            if (photoInput && photoPreview) {
                let buffer = [];

                const renderPreviews = () => {
                    photoPreview.innerHTML = '';

                    buffer.forEach((file, index) => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'photo-preview';

                        const image = document.createElement('img');
                        image.alt = file.name;
                        image.src = URL.createObjectURL(file);
                        wrapper.appendChild(image);

                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.textContent = '×';
                        remove.setAttribute('aria-label', 'Remove photo');
                        remove.addEventListener('click', () => {
                            buffer.splice(index, 1);
                            renderPreviews();
                            syncInputFiles();
                        });
                        wrapper.appendChild(remove);

                        photoPreview.appendChild(wrapper);
                    });
                };

                const syncInputFiles = () => {
                    if (!photoInput) {
                        return;
                    }

                    if (!buffer.length) {
                        photoInput.value = '';
                        return;
                    }

                    if (supportsDataTransfer) {
                        try {
                            const dt = new DataTransfer();
                            buffer.forEach((file) => dt.items.add(file));
                            photoInput.files = dt.files;
                        } catch (error) {
                            // If the browser supports DataTransfer but throws, fall back silently.
                        }
                    }
                };

                photoInput.addEventListener('change', (event) => {
                    const files = Array.from(event.target.files || []);
                    if (!files.length) {
                        return;
                    }

                    const availableSlots = maxFiles - buffer.length;
                    const accepted = files.slice(0, Math.max(availableSlots, 0));

                    if (accepted.length < files.length) {
                        showToast(`You can upload up to ${maxFiles} photos.`);
                    }

                    accepted.forEach((file) => {
                        if (!file.type.startsWith('image/')) {
                            showToast('Only image files are allowed.');
                            return;
                        }
                        buffer.push(file);
                    });

                    renderPreviews();
                    syncInputFiles();

                    // Clear native input to allow re-selecting the same file later.
                    photoInput.value = '';
                });

                form.addEventListener('submit', () => {
                    if (!supportsDataTransfer && buffer.length && photoInput) {
                        try {
                            const dt = new DataTransfer();
                            buffer.forEach((file) => dt.items.add(file));
                            photoInput.files = dt.files;
                        } catch (error) {
                            // If DataTransfer construction is blocked, allow the last selection through.
                        }
                    }
                });

                renderPreviews();
            }
        });
    });
})();
</script>

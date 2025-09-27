const imageDropZone = document.getElementById('imageDropZone');
const sidebarImageInput = document.getElementById('sidebarImageInput');
const sidebarImageThumbs = document.getElementById('sidebarImageThumbs');
const imageUploadSpinner = document.getElementById('imageUploadSpinner');
const imageUploadError = document.getElementById('imageUploadError');

let sidebarImages = []; // Track uploaded images for sidebar

// Automatically focus the first image thumbnail after upload for faster keyboard navigation
function handleSidebarImages(files) {
    imageUploadError.style.display = 'none';
    imageUploadSpinner.style.display = 'block';
    let loadedCount = 0;
    let validFiles = 0;
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) {
            imageUploadError.textContent = 'Only image files are allowed.';
            imageUploadError.style.display = 'block';
            imageUploadSpinner.style.display = 'none';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            imageUploadError.textContent = 'File too large (max 5MB).';
            imageUploadError.style.display = 'block';
            imageUploadSpinner.style.display = 'none';
            return;
        }
        validFiles++;
        const url = URL.createObjectURL(file);
        const img = new window.Image();
        img.onload = function() {
            sidebarImages.push({ img, url });
            renderSidebarImages();
            loadedCount++;
            if (loadedCount === validFiles) {
                imageUploadSpinner.style.display = 'none';
                // Focus the first thumbnail for accessibility
                const thumbs = sidebarImageThumbs.querySelectorAll('img');
                if (thumbs.length) thumbs[thumbs.length - validFiles].focus();
            }
        };
        img.onerror = function() {
            imageUploadError.textContent = 'Failed to load image.';
            imageUploadError.style.display = 'block';
            imageUploadSpinner.style.display = 'none';
        };
        img.src = url;
    });
    if (validFiles === 0) imageUploadSpinner.style.display = 'none';
}

// Keyboard accessibility: allow deleting sidebar images with keyboard
sidebarImageThumbs.addEventListener('keydown', function(e) {
    if ((e.key === 'Delete' || e.key === 'Backspace') && document.activeElement.tagName === 'IMG') {
        const thumbs = Array.from(sidebarImageThumbs.querySelectorAll('img'));
        const idx = thumbs.indexOf(document.activeElement);
        if (idx !== -1) {
            sidebarImages.splice(idx, 1);
            renderSidebarImages();
            // Move focus to next or previous thumbnail
            const newThumbs = Array.from(sidebarImageThumbs.querySelectorAll('img'));
            if (newThumbs[idx]) newThumbs[idx].focus();
            else if (newThumbs[idx - 1]) newThumbs[idx - 1].focus();
        }
    }
});

// Add ARIA roles and labels for improved accessibility
imageDropZone.setAttribute('role', 'button');
imageDropZone.setAttribute('aria-label', 'Upload images by drag and drop or click');
sidebarImageThumbs.setAttribute('role', 'list');
function renderSidebarImages() {
    sidebarImageThumbs.innerHTML = '';
    sidebarImages.forEach((media, idx) => {
        const thumbWrap = document.createElement('div');
        thumbWrap.style.position = 'relative';
        thumbWrap.style.display = 'inline-block';
        thumbWrap.setAttribute('role', 'listitem');

        const thumb = document.createElement('img');
        thumb.src = media.url;
        thumb.alt = 'Uploaded image preview';
        thumb.style.width = '48px';
        thumb.style.height = '48px';
        thumb.style.objectFit = 'cover';
        thumb.style.borderRadius = '6px';
        thumb.style.cursor = 'pointer';
        thumb.title = 'Insert image to canvas';
        thumb.setAttribute('tabindex', '0');
        thumb.setAttribute('aria-label', 'Insert image to canvas');
        thumb.onclick = () => {
            if (window.addImageToCanvas) window.addImageToCanvas(media.img);
        };
        thumb.onkeydown = function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (window.addImageToCanvas) window.addImageToCanvas(media.img);
            }
        };
        // Add focus/blur event for visual feedback
        thumb.addEventListener('focus', function() {
            thumb.style.outline = '2px solid #2563eb';
            thumb.style.boxShadow = '0 0 0 3px #e0e7ff';
        });
        thumb.addEventListener('blur', function() {
            thumb.style.outline = '';
            thumb.style.boxShadow = '';
        });

        // Delete icon
        const delBtn = document.createElement('span');
        delBtn.textContent = '✕';
        delBtn.style.position = 'absolute';
        delBtn.style.right = '2px';
        delBtn.style.top = '2px';
        delBtn.style.background = '#fff';
        delBtn.style.color = '#e11d48';
        delBtn.style.borderRadius = '50%';
        delBtn.style.cursor = 'pointer';
        delBtn.style.fontSize = '14px';
        delBtn.style.padding = '2px 5px';
        delBtn.title = 'Remove image';
        delBtn.setAttribute('aria-label', 'Remove image');
        delBtn.onclick = (e) => {
            e.stopPropagation();
            sidebarImages.splice(idx, 1);
            renderSidebarImages();
        };

        thumbWrap.appendChild(thumb);
        thumbWrap.appendChild(delBtn);
        sidebarImageThumbs.appendChild(thumbWrap);
    });
}

// Allow users to clear all uploaded images from the sidebar with one button
const clearImagesBtn = document.createElement('button');
clearImagesBtn.textContent = 'Clear All Images';
clearImagesBtn.className = 'btn full';
clearImagesBtn.style.margin = '10px 0';
clearImagesBtn.onclick = function() {
    sidebarImages = [];
    renderSidebarImages();
};
if (sidebarImageThumbs.parentNode) {
    sidebarImageThumbs.parentNode.insertBefore(clearImagesBtn, sidebarImageThumbs);
}

// Add dragover/dragleave highlight for drop zone
imageDropZone.addEventListener('dragover', e => {
    e.preventDefault();
    imageDropZone.classList.add('dragover');
});
imageDropZone.addEventListener('dragleave', e => {
    imageDropZone.classList.remove('dragover');
});
imageDropZone.addEventListener('drop', e => {
    e.preventDefault();
    imageDropZone.classList.remove('dragover');
    handleSidebarImages(e.dataTransfer.files);
});

// Accessibility: allow users to focus drop zone and trigger file input with keyboard
imageDropZone.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        sidebarImageInput.click();
    }
});

// Ensure drop zone is focusable via Tab (already set in HTML, but reinforce here)
imageDropZone.setAttribute('tabindex', '0');

// Add support for uploading images by dragging and dropping directly onto the canvas
const templateCanvas = document.getElementById('templateCanvas');
if (templateCanvas) {
    templateCanvas.addEventListener('dragover', e => {
        e.preventDefault();
        templateCanvas.style.border = '2px dashed #2563eb';
    });
    templateCanvas.addEventListener('dragleave', e => {
        templateCanvas.style.border = '';
    });
    templateCanvas.addEventListener('drop', e => {
        e.preventDefault();
        templateCanvas.style.border = '';
        Array.from(e.dataTransfer.files).forEach(file => {
            if (!file.type.startsWith('image/')) {
                imageUploadError.textContent = 'Only image files are allowed.';
                imageUploadError.style.display = 'block';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                imageUploadError.textContent = 'File too large (max 5MB).';
                imageUploadError.style.display = 'block';
                return;
            }
            const url = URL.createObjectURL(file);
            const img = new window.Image();
            img.onload = function() {
                if (window.addImageToCanvas) window.addImageToCanvas(img);
            };
            img.onerror = function() {
                imageUploadError.textContent = 'Failed to load image.';
                imageUploadError.style.display = 'block';
            };
            img.src = url;
        });
    });
}

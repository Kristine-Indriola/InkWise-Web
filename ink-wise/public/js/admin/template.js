document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".create-form");
    const designInput = document.getElementById("design");

    // For now, set some sample JSON design data
    form.addEventListener("submit", function () {
        designInput.value = JSON.stringify({
            text: "Please join us...",
            bride: "ELEANOR",
            groom: "VINCENT",
            date: "25 October 2025",
        });
    });

    
    document.querySelectorAll('.preview-thumb').forEach(function(img) {
        img.addEventListener('click', function() {
            document.getElementById('modalImg').src = img.dataset.img;
            document.getElementById('previewModal').style.display = 'flex';
        });
    });
    document.getElementById('closePreview').onclick = function() {
        document.getElementById('previewModal').style.display = 'none';
        document.getElementById('modalImg').src = '';
    };
    document.getElementById('previewModal').onclick = function(e) {
        if (e.target === this) {
            this.style.display = 'none';
            document.getElementById('modalImg').src = '';
        }
    };

    document.querySelectorAll('.video-swatches').forEach(group => {
        const card = group.closest('.w-full');
        const video = card.querySelector('.template-video');
        const image = card.querySelector('.template-image');
        const source = video ? video.querySelector('source') : null;

        group.querySelectorAll('.swatch-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const videoSrc = btn.getAttribute('data-video');
                const imageSrc = btn.getAttribute('data-image');
                if (video && image && source) {
                    if (videoSrc) {
                        image.classList.add('hidden');
                        video.classList.remove('hidden');
                        if (source.src !== videoSrc) {
                            source.src = videoSrc;
                            video.load();
                        }
                        video.play();
                    }
                    const imageSrc = btn.getAttribute('data-image');
                    if (imageSrc) {
                        image.src = imageSrc;
                        image.classList.remove('hidden');
                        video.classList.add('hidden');
                    }
                }
            });
        });
    });
});



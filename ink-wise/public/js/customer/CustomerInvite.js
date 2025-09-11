 // Switch video when a swatch is clicked
  document.querySelectorAll('.video-swatches span').forEach(swatch => {
    swatch.addEventListener('click', function() {
      const videoSrc = this.getAttribute('data-video');
      const videoEl = this.closest('article').querySelector('video');
      // Change the source
      videoEl.querySelector('source').src = videoSrc;
      // Reload video so the new source plays
      videoEl.load();
      videoEl.play();
    });
  });
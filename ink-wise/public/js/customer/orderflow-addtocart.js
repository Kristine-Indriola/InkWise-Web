document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.addtocart-shell');
  const addToCartBtn = document.getElementById('addToCartBtn');
  const toast = document.getElementById('addToCartToast');
  const flipContainer = document.querySelector('.card-flip');
  const toggleButtons = Array.from(document.querySelectorAll('.preview-toggle button'));

  const previewPlaceholder = '/images/placeholder.png';
  const storageKey = shell?.dataset?.storageKey ?? 'inkwise-addtocart';
  const envelopeUrl = addToCartBtn?.dataset?.envelopeUrl ?? shell?.dataset?.envelopeUrl ?? '/order/envelope';
  const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

  let toastTimeout = null;

  const readSummary = () => {
    try {
      const raw = window.sessionStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      console.warn('Unable to parse stored order summary', error);
      return null;
    }
  };

  const writeSummary = (summary) => {
    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify(summary));
    } catch (error) {
      console.error('Unable to store order summary', error);
    }
  };

  const showToast = (message) => {
    if (toastTimeout) {
      clearTimeout(toastTimeout);
    }

    toast.textContent = message;
    toast.classList.add('is-visible');

    toastTimeout = setTimeout(() => {
      toast.classList.remove('is-visible');
    }, 3000);
  };

  const togglePreview = (side) => {
    const isFlipped = side === 'back';
    flipContainer?.classList.toggle('flipped', isFlipped);

    toggleButtons.forEach((button) => {
      const buttonSide = button.dataset.side;
      const isActive = buttonSide === side;
      button.classList.toggle('active', isActive);
      button.setAttribute('aria-pressed', isActive);
    });
  };

  // Preview toggle functionality
  toggleButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const side = button.dataset.side;
      togglePreview(side);
    });
  });

  // Add to cart functionality
  addToCartBtn?.addEventListener('click', async (event) => {
    event.preventDefault();

    const summary = readSummary();
    if (!summary) {
      showToast('No order details found. Please go back and configure your options.');
      return;
    }

    // Store the summary for the next step
    writeSummary(summary);

    showToast('Added to cart — redirecting to envelope options...');

    window.setTimeout(() => {
      window.location.href = envelopeUrl;
    }, 600);
  });

  // Keyboard navigation
  window.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') togglePreview('front');
    if (event.key === 'ArrowRight') togglePreview('back');
  });

  // Initialize
  togglePreview('front');
});

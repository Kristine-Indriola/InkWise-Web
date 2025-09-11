document.addEventListener('DOMContentLoaded', function() {
    // Highlight the review section on page load
    const review = document.querySelector('.order-highlight');
    if (review) {
        review.style.boxShadow = '0 0 8px #06b6d4';
    }

    // Live total price calculation
    const quantityInput = document.getElementById('quantity');
    const pricePerInvitation = document.getElementById('pricePerInvitation');
    const totalPrice = document.getElementById('totalPrice');
    function updateTotal() {
        const qty = parseInt(quantityInput.value) || 0;
        const price = parseInt(pricePerInvitation.textContent) || 0;
        totalPrice.textContent = qty * price;
    }
    if (quantityInput && pricePerInvitation && totalPrice) {
        quantityInput.addEventListener('input', updateTotal);
        updateTotal();
    }

    // Google Map update on location input (optional, basic demo)
    const locationInput = document.getElementById('locationInput');
    const googleMap = document.getElementById('googleMap');
    if (locationInput && googleMap) {
        locationInput.addEventListener('change', function() {
            const address = encodeURIComponent(locationInput.value + ', Philippines');
            // Replace YOUR_REAL_API_KEY with your actual Google Maps Embed API key
            googleMap.src = `https://www.google.com/maps/embed/v1/place?key=YOUR_REAL_API_KEY&q=${address}`;
        });
    }

    // Confirm button alert (for demo)
    const form = document.getElementById('orderForm') || document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Order confirmed! (Demo)');
        });
    }

    // OpenStreetMap iframe integration
    const osmMap = document.getElementById('osmMap');
    if (osmMap) {
        osmMap.src = 'https://www.openstreetmap.org/export/embed.html?bbox=120.87890625%2C11.178401873711785%2C126.9140625%2C15.284185114076433&amp;layer=mapnik';
    }
});
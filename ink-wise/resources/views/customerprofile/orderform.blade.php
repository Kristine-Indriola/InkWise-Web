<!-- filepath: resources/views/customerprofile/orderform.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Invitation</title>
    <link rel="stylesheet" href="{{ asset('css/admin-css/edit.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/customer/customerorderform.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="bg-gray-100">

<div class="max-w-2xl mx-auto bg-white shadow rounded p-8 mt-10">
    <h2 class="text-2xl font-bold mb-6 text-center">Order Invitation</h2>
    <form id="orderForm">
        <!-- Front & Back Image Preview -->
        <div class="flex justify-center gap-6 mb-6">
            <div class="text-center">
                <div class="font-semibold mb-1">Front Design</div>
                <img src="{{ asset('customerimages/invite/wedding3.jpg') }}" alt="Front Design" class="w-40 h-56 object-cover border rounded shadow">
            </div>
            <div class="text-center">
                <div class="font-semibold mb-1">Back Design</div>
                <img src="{{ asset('customerimages/invite/wed1.png') }}" alt="Back Design" class="w-40 h-56 object-cover border rounded shadow">
            </div>
        </div>
        <!-- Event Type -->
        <div class="mb-4">
            <label class="block font-semibold mb-1">Event Type</label>
            <input type="text" class="form-input w-full" value="Wedding" readonly>
        </div>
        <!-- Trim Type -->
        <div class="mb-4">
            <label class="block font-semibold mb-1">Trim Type</label>
            <select id="trimType" name="trimType" class="form-select w-full">
                <option value="standard" data-price="20">Standard (+₱0)</option>
                <option value="rounded" data-price="27">Rounded (+₱7)</option>
                <option value="ticket" data-price="70">Ticket (+₱50)</option>
            </select>
        </div>
        <!-- Back Design Text -->
        <div class="mb-4">
            <label class="block font-semibold mb-1">Back Design</label>
            <div class="border rounded p-3 bg-gray-50">
                Daniel Gallego and Sacha Dubois<br>
                Thank you for being part of our special day.<br>
                We can’t wait to celebrate this beautiful moment with you.
            </div>
        </div>
        <!-- Review & Confirm Design -->
        <div class="mb-4">
            <label class="block font-semibold mb-1">Review & Confirm Design</label>
            <div class="border rounded p-3 bg-gray-50 order-highlight">
                <div>Customer Name: <span class="font-semibold">Daniel Gallego</span></div>
                <div>Date & Time: <span class="font-semibold">April 20, 2025, 10:00 AM</span></div>
                <div>
                    Quantity:
                    <input type="number" id="quantity" name="quantity" min="1" value="5" class="border rounded px-2 py-1 w-20 ml-2">
                </div>
                <div>Price per Invitation: <span class="font-semibold" id="pricePerInvitation">20</span></div>
                <div>
                    Total Price:
                    <span class="font-semibold" id="totalPrice">100</span>
                </div>
                <div>Paper: <span class="font-semibold">Premium Matte</span></div>
                <div>
                    Location:
                    <input type="text" id="locationInput" name="location" class="border rounded px-2 py-1 w-full mt-1" placeholder="Enter your address in the Philippines">
                    <div class="mt-2">
                        <div id="map" style="height: 200px;"></div>
                    </div>
                </div>
                <div>Product Name: <span class="font-semibold">Wedding Invitation - Elegant Theme</span></div>
            </div>
        </div>
        <!-- Confirm/Back Buttons -->
        <div class="flex justify-between mt-6">
            <a href="{{ route('design.edit') }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Back To Edit</a>
            <button type="submit" class="order-confirm-btn px-6 py-2 bg-cyan-500 text-white rounded hover:bg-cyan-600">Confirm Design</button>
        </div>
    </form>
</div>

<script src="{{ asset('js/customerorderform.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Leaflet map
    var map = L.map('map').setView([13.41, 122.56], 6); // Center on Philippines
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    var marker;

    document.getElementById('locationInput').addEventListener('change', function() {
        var address = this.value + ', Philippines';
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address))
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    var lat = data[0].lat;
                    var lon = data[0].lon;
                    if (marker) map.removeLayer(marker);
                    marker = L.marker([lat, lon]).addTo(map);
                    map.setView([lat, lon], 14);
                } else {
                    alert('Location not found!');
                }
            });
    });

    // Live price calculation
    const quantityInput = document.getElementById('quantity');
    const trimType = document.getElementById('trimType');
    const pricePerInvitation = document.getElementById('pricePerInvitation');
    const totalPrice = document.getElementById('totalPrice');

    function updatePrice() {
        const qty = parseInt(quantityInput.value) || 1;
        const trimOption = trimType.options[trimType.selectedIndex];
        const price = parseInt(trimOption.getAttribute('data-price')) || 20;
        pricePerInvitation.textContent = price;
        totalPrice.textContent = price * qty;
    }

    quantityInput.addEventListener('input', updatePrice);
    trimType.addEventListener('change', updatePrice);

    updatePrice();
});
</script>
</body>
</html>
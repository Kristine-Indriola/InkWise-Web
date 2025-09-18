@extends('layouts.customerprofile')

@section('title', 'My Addresses')

@section('head')
    <link rel="stylesheet" href="{{ asset('css/customer/customerprofile.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body, .card, input, select, button, h1, h2, h3, h4, h5, h6, label, span, a, div, p {
            font-family: 'Poppins', sans-serif !important;
        }
    </style>
@endsection



@section('content')
<div class="card bg-white p-6 md:p-8 border border-gray-100">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold">My Addresses</h2>
        <button id="addAddressBtn"
            class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white font-medium px-6 py-2 rounded shadow flex items-center gap-2 transition">
            <span class="text-xl">+</span> Add New Address
        </button>
    </div>
    <hr class="mb-6">

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div>
        <h3 class="text-lg font-medium mb-2">Address</h3>
        @forelse($addresses as $address)
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4 border-b pb-4">
                <div>
                    <div class="flex flex-col mb-1">
                        <span class="font-bold text-lg">{{ $address->full_name ?? auth()->user()->name }}</span>
                        <span class="text-gray-600">{{ $address->phone ?? auth()->user()->phone ?? '' }}</span>
                    </div>
                    <div class="text-gray-600 mt-1">
                        {{ $address->street }}<br>
                        {{ $address->barangay }}, {{ $address->city }}, {{ $address->province }}, {{ $address->region ?? '' }}, {{ $address->postal_code }}
                    </div>
                </div>
                <div>
                    <button type="button"
                        class="text-[#1976d2] hover:underline mr-3 edit-address-btn"
                        data-address='@json($address)'>
                        Edit
                    </button>
                    <form method="POST" action="{{ route('customer.profile.addresses.destroy', $address->address_id) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-[#1976d2] hover:underline bg-transparent border-0 p-0 m-0">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-gray-500">No addresses found.</div>
        @endforelse
    </div>

    <!-- Add Address Modal -->
    <div id="addAddressModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="address-form-modal bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto relative">
            <button id="closeAddAddress" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-xl font-bold">✖</button>
            <h3 class="text-lg font-semibold mb-4">Add New Address</h3>
            <form method="POST" action="{{ route('customer.profile.addresses.store') }}">
                @csrf
                <input type="hidden" name="country" value="Philippines">
                <div class="mb-3 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm mb-1">Full Name</label>
                        <input type="text" name="full_name" class="w-full border rounded px-3 py-2" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Phone Number</label>
                        <input type="text" name="phone" class="w-full border rounded px-3 py-2" value="{{ auth()->user()->phone ?? '' }}" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Region</label>
                    <select name="region" id="region" class="w-full border rounded px-3 py-2" required>
                        <option value="">Select Region</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Province</label>
                    <select name="province" id="province" class="w-full border rounded px-3 py-2" required>
                        <option value="">Select Province</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">City</label>
                    <select name="city" id="city" class="w-full border rounded px-3 py-2" required>
                        <option value="">Select City</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Barangay</label>
                    <select name="barangay" id="barangay" class="w-full border rounded px-3 py-2" required>
                        <option value="">Select Barangay</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Postal Code</label>
                    <input type="text" name="postal_code" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Street</label>
                    <input type="text" name="street" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Label as</label>
                    <select name="label" class="w-full border rounded px-3 py-2" required>
                        <option value="Home">Home</option>
                        <option value="Work">Work</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" id="closeAddAddress2" class="px-5 py-2 rounded border border-[#a6b7ff] text-[#a6b7ff] bg-white hover:bg-[#d3b7ff]">Cancel</button>
                    <button type="submit" class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-5 py-2 rounded">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS for Dynamic Region/Province/City/Barangay -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const regionSelect = document.getElementById("region");
    const provinceSelect = document.getElementById("province");
    const citySelect = document.getElementById("city");
    const barangaySelect = document.getElementById("barangay");

    // Load regions
    fetch("https://psgc.gitlab.io/api/regions/")
        .then(res => res.json())
        .then(data => {
            data.forEach(region => {
                let option = document.createElement("option");
                option.value = region.name; // store name instead of code
                option.textContent = region.name;
                option.dataset.code = region.code; // keep code if needed
                regionSelect.appendChild(option);
            });
        });

    // Provinces
    regionSelect.addEventListener("change", function () {
        provinceSelect.innerHTML = '<option value="">Select Province</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        let selected = regionSelect.selectedOptions[0];
        if (selected && selected.dataset.code) {
            fetch(`https://psgc.gitlab.io/api/regions/${selected.dataset.code}/provinces/`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(province => {
                        let option = document.createElement("option");
                        option.value = province.name;
                        option.textContent = province.name;
                        option.dataset.code = province.code;
                        provinceSelect.appendChild(option);
                    });
                });
        }
    });

    // Cities
    provinceSelect.addEventListener("change", function () {
        citySelect.innerHTML = '<option value="">Select City</option>';
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        let selected = provinceSelect.selectedOptions[0];
        if (selected && selected.dataset.code) {
            fetch(`https://psgc.gitlab.io/api/provinces/${selected.dataset.code}/cities-municipalities/`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(city => {
                        let option = document.createElement("option");
                        option.value = city.name;
                        option.textContent = city.name;
                        option.dataset.code = city.code;
                        citySelect.appendChild(option);
                    });
                });
        }
    });

    // Barangays
    citySelect.addEventListener("change", function () {
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        let selected = citySelect.selectedOptions[0];
        if (selected && selected.dataset.code) {
            fetch(`https://psgc.gitlab.io/api/cities-municipalities/${selected.dataset.code}/barangays/`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(barangay => {
                        let option = document.createElement("option");
                        option.value = barangay.name;
                        option.textContent = barangay.name;
                        barangaySelect.appendChild(option);
                    });
                });
        }
    });
});


document.addEventListener("DOMContentLoaded", function () {
    // ...existing region/province/city/barangay JS...

    // Modal logic for Add Address
    const addBtn = document.getElementById("addAddressBtn");
    const modal = document.getElementById("addAddressModal");
    const closeBtn = document.getElementById("closeAddAddress");
    const closeBtn2 = document.getElementById("closeAddAddress2");

    if (addBtn && modal) {
        addBtn.addEventListener("click", function () {
            modal.classList.remove("hidden");
        });
    }
    if (closeBtn && modal) {
        closeBtn.addEventListener("click", function () {
            modal.classList.add("hidden");
        });
    }
    if (closeBtn2 && modal) {
        closeBtn2.addEventListener("click", function () {
            modal.classList.add("hidden");
        });
    }
});

</script>
@endsection

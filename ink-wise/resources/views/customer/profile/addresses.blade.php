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
                        <span class="font-bold text-lg">{{ auth()->user()->name }}</span>
                        <span class="text-gray-600">{{ auth()->user()->phone ?? '' }}</span>
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
                        @method('DELETE')
                        <button type="submit" class="text-[#1976d2] hover:underline bg-transparent border-0 p-0 m-0">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-gray-500">No addresses found.</div>
        @endforelse
    </div>

    <!-- Add/Edit Address Modal -->
    <div id="addressModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="address-form-modal bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto relative">
            <button id="closeAddressModal" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-xl font-bold">✖</button>
            <h3 id="modalTitle" class="text-lg font-semibold mb-4">Add New Address</h3>

            <form id="addressForm" method="POST" action="">
                @csrf
                <!-- method spoofing toggled by JS -->
                <input type="hidden" name="_method" id="addressFormMethod" value="POST">

                {{-- Region --}}
                <label for="form_region" class="block font-medium">Region</label>
                <select id="form_region" name="region" class="w-full border rounded p-2" required>
                    <option value="">Select Region</option>
                </select>

                {{-- Province --}}
                <label for="form_province" class="block font-medium mt-3">Province</label>
                <select id="form_province" name="province" class="w-full border rounded p-2" required>
                    <option value="">Select Province</option>
                </select>

                {{-- City --}}
                <label for="form_city" class="block font-medium mt-3">City</label>
                <select id="form_city" name="city" class="w-full border rounded p-2" required>
                    <option value="">Select City</option>
                </select>

                {{-- Barangay --}}
                <label for="form_barangay" class="block font-medium mt-3">Barangay</label>
                <select id="form_barangay" name="barangay" class="w-full border rounded p-2" required>
                    <option value="">Select Barangay</option>
                </select>

                {{-- Postal Code --}}
                <label for="form_postal_code" class="block font-medium mt-3">Postal Code</label>
                <input type="text" id="form_postal_code" name="postal_code" class="w-full border rounded p-2" required>

                {{-- Street --}}
                <label for="form_street" class="block font-medium mt-3">Street</label>
                <input type="text" id="form_street" name="street" class="w-full border rounded p-2" required>

                {{-- Label --}}
                <label for="form_label" class="block font-medium mt-3">Address Label</label>
                <select id="form_label" name="label" class="w-full border rounded p-2" required>
                    <option value="Home">Home</option>
                    <option value="Work">Work</option>
                    <option value="Other">Other</option>
                </select>

                {{-- Submit --}}
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" id="cancelModal" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" id="submitBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("addressModal");
    const modalTitle = document.getElementById("modalTitle");
    const closeModal = document.getElementById("closeAddressModal");
    const cancelModal = document.getElementById("cancelModal");
    const form = document.getElementById("addressForm");
    const submitBtn = document.getElementById("submitBtn");
    const methodInput = document.getElementById('addressFormMethod');

    const fields = {
        region: document.getElementById("form_region"),
        province: document.getElementById("form_province"),
        city: document.getElementById("form_city"),
        barangay: document.getElementById("form_barangay"),
        postal_code: document.getElementById("form_postal_code"),
        street: document.getElementById("form_street"),
        label: document.getElementById("form_label"),
    };

    // route templates
    const storeUrl = "{{ route('customer.profile.addresses.store') }}";
    const updateUrlTemplate = "{{ route('customer.profile.addresses.update', ['address' => '__ID__']) }}";

    // ---------------- Show modal for ADD ----------------
    document.getElementById("addAddressBtn").addEventListener("click", () => {
        modalTitle.textContent = "Add New Address";
        submitBtn.textContent = "Submit";
        form.action = storeUrl;
        methodInput.value = "POST";

        Object.values(fields).forEach(input => input.value = "");
        modal.classList.remove("hidden");
    });

    // ---------------- Show modal for EDIT ----------------
    document.querySelectorAll(".edit-address-btn").forEach(button => {
        button.addEventListener("click", () => {
            const address = JSON.parse(button.dataset.address);

            modalTitle.textContent = "Edit Address";
            submitBtn.textContent = "Update";

            // build update url by replacing placeholder
            form.action = updateUrlTemplate.replace('__ID__', address.address_id);
            methodInput.value = "PUT";

            fields.postal_code.value = address.postal_code ?? "";
            fields.street.value = address.street ?? "";
            fields.label.value = address.label ?? "Home";

            // Load PSGC values dynamically
            loadRegions().then(() => {
                fields.region.value = address.region ?? "";
                if (address.region) {
                    loadProvinces(address.region).then(() => {
                        fields.province.value = address.province ?? "";
                        if (address.province) {
                            loadCities(address.province).then(() => {
                                fields.city.value = address.city ?? "";
                                if (address.city) {
                                    loadBarangays(address.city).then(() => {
                                        fields.barangay.value = address.barangay ?? "";
                                    });
                                }
                            });
                        }
                    });
                }
            });

            modal.classList.remove("hidden");
        });
    });

    // Close modal
    [closeModal, cancelModal].forEach(btn => btn.addEventListener("click", () => modal.classList.add("hidden")));

    // ---------------- PSGC Dropdown Functions ----------------
    async function loadRegions() {
        fields.region.innerHTML = '<option value="">Select Region</option>';
        let res = await fetch("https://psgc.gitlab.io/api/regions/");
        let data = await res.json();
        data.forEach(region => {
            let option = document.createElement("option");
            option.value = region.name;
            option.textContent = region.name;
            option.dataset.code = region.code;
            fields.region.appendChild(option);
        });
    }

    async function loadProvinces(regionName) {
        fields.province.innerHTML = '<option value="">Select Province</option>';
        fields.city.innerHTML = '<option value="">Select City</option>';
        fields.barangay.innerHTML = '<option value="">Select Barangay</option>';

        let selected = [...fields.region.options].find(o => o.value === regionName);
        if (!selected) return;

        let res = await fetch(`https://psgc.gitlab.io/api/regions/${selected.dataset.code}/provinces/`);
        let data = await res.json();
        data.forEach(province => {
            let option = document.createElement("option");
            option.value = province.name;
            option.textContent = province.name;
            option.dataset.code = province.code;
            fields.province.appendChild(option);
        });
    }

    async function loadCities(provinceName) {
        fields.city.innerHTML = '<option value="">Select City</option>';
        fields.barangay.innerHTML = '<option value="">Select Barangay</option>';

        let selected = [...fields.province.options].find(o => o.value === provinceName);
        if (!selected) return;

        let res = await fetch(`https://psgc.gitlab.io/api/provinces/${selected.dataset.code}/cities-municipalities/`);
        let data = await res.json();
        data.forEach(city => {
            let option = document.createElement("option");
            option.value = city.name;
            option.textContent = city.name;
            option.dataset.code = city.code;
            fields.city.appendChild(option);
        });
    }

    async function loadBarangays(cityName) {
        fields.barangay.innerHTML = '<option value="">Select Barangay</option>';

        let selected = [...fields.city.options].find(o => o.value === cityName);
        if (!selected) return;

        let res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${selected.dataset.code}/barangays/`);
        let data = await res.json();
        data.forEach(barangay => {
            let option = document.createElement("option");
            option.value = barangay.name;
            option.textContent = barangay.name;
            fields.barangay.appendChild(option);
        });
    }

    // Initial load regions for Add form
    loadRegions();

    // Event listeners for dropdowns
    fields.region.addEventListener("change", () => loadProvinces(fields.region.value));
    fields.province.addEventListener("change", () => loadCities(fields.province.value));
    fields.city.addEventListener("change", () => loadBarangays(fields.city.value));
});
</script>

@endsection

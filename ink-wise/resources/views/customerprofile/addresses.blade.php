@extends('layouts.customerprofile')

@section('title', 'My Addresses')

@section('head')
    <link rel="stylesheet" href="{{ asset('css/customer/customerprofile.css') }}">
    <!-- Add Poppins font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body, .card, .address-form-modal, input, select, button, h1, h2, h3, h4, h5, h6, label, span, a, div, p {
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

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Address List -->
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
                    <form method="POST" action="{{ route('customerprofile.addresses.destroy', $address->address_id) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-[#1976d2] hover:underline bg-transparent border-0 p-0 m-0">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-gray-500">No addresses found.</div>
        @endforelse
    </div>

    <!-- Add Address Modal/Form (hidden by default) -->
    <div id="addAddressModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="address-form-modal bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto">
            <button id="closeAddAddress" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-xl font-bold">✖</button>
            <h3 class="text-lg font-semibold mb-4">Add New Address</h3>
            <form method="POST" action="{{ route('customerprofile.addresses.store') }}">
                @csrf
                <!-- Hidden country field -->
                <input type="hidden" name="country" value="Philippines">
                <div class="mb-3 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm mb-1">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="w-full border rounded px-3 py-2" placeholder="Full Name" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Phone Number</label>
                        <input type="text" name="phone" id="phone" class="w-full border rounded px-3 py-2" placeholder="Phone Number" value="{{ auth()->user()->phone ?? '' }}" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Region</label>
                        <select name="region" id="region" class="w-full border rounded px-3 py-2" required>
                            <option value="">Select Region</option>
                            <option value="Ilocos Region (Region I)">Ilocos Region (Region I)</option>
                            <option value="Cagayan Valley (Region II)">Cagayan Valley (Region II)</option>
                            <option value="Central Luzon (Region III)">Central Luzon (Region III)</option>
                            <option value="CALABARZON (Region IV-A)">CALABARZON (Region IV-A)</option>
                            <option value="MIMAROPA (Region IV-B)">MIMAROPA (Region IV-B)</option>
                            <option value="Bicol Region (Region V)">Bicol Region (Region V)</option>
                            <option value="Western Visayas (Region VI)">Western Visayas (Region VI)</option>
                            <option value="Central Visayas (Region VII)">Central Visayas (Region VII)</option>
                            <option value="Eastern Visayas (Region VIII)">Eastern Visayas (Region VIII)</option>
                            <option value="Zamboanga Peninsula (Region IX)">Zamboanga Peninsula (Region IX)</option>
                            <option value="Northern Mindanao (Region X)">Northern Mindanao (Region X)</option>
                            <option value="Davao Region (Region XI)">Davao Region (Region XI)</option>
                            <option value="SOCCSKSARGEN (Region XII)">SOCCSKSARGEN (Region XII)</option>
                            <option value="Caraga (Region XIII)">Caraga (Region XIII)</option>
                            <option value="Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)">BARMM</option>
                            <option value="Cordillera Administrative Region (CAR)">Cordillera Administrative Region (CAR)</option>
                            <option value="National Capital Region (NCR)">National Capital Region (NCR)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Province</label>
                        <select name="province" id="province" class="w-full border rounded px-3 py-2" required>
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">City</label>
                        <select name="city" id="city" class="w-full border rounded px-3 py-2" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Barangay</label>
                        <input type="text" name="barangay" id="barangay" class="w-full border rounded px-3 py-2" placeholder="Barangay" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Postal Code</label>
                    <input type="text" name="postal_code" id="postal_code" class="w-full border rounded px-3 py-2" placeholder="Postal Code" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Street Name</label>
                    <input type="text" name="street" id="street" class="w-full border rounded px-3 py-2" placeholder="Street Name" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Label as</label>
                    <select name="label" id="label" class="w-full border rounded px-3 py-2" required>
                        <option value="Home">Home</option>
                        <option value="Work">Work</option>
                    </select>
                </div>
                <!-- Google Maps Embed -->
                <div class="mb-3">
                    <label class="block text-sm mb-1">Google Location</label>
                    <div class="w-full h-56 rounded overflow-hidden border" id="map_embed_wrap">
                        <iframe
                            id="map_embed"
                            width="100%"
                            height="100%"
                            frameborder="0"
                            style="border:0; min-height:220px;"
                            src="https://maps.google.com/maps?q=Cebu%20City&t=&z=13&ie=UTF8&iwloc=&output=embed"
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" id="closeAddAddress2" class="px-5 py-2 rounded border border-[#a6b7ff] text-[#a6b7ff] bg-white hover:bg-[#d3b7ff]">Cancel</button>
                    <button type="submit" class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-5 py-2 rounded">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Address Modal/Form (hidden by default) -->
    <div id="editAddressModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="address-form-modal bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-auto">
            <button type="button" id="closeEditAddress" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-xl font-bold">✖</button>
            <h3 class="text-lg font-semibold mb-4">Edit Address</h3>
            <form method="POST" id="editAddressForm">
                @csrf
                <input type="hidden" name="country" value="Philippines">
                <div class="mb-3 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm mb-1">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Region</label>
                        <select name="region" id="edit_region" class="w-full border rounded px-3 py-2" required>
                            <!-- Same options as Add Modal -->
                            <option value="">Select Region</option>
                            <option value="Ilocos Region (Region I)">Ilocos Region (Region I)</option>
                            <option value="Cagayan Valley (Region II)">Cagayan Valley (Region II)</option>
                            <option value="Central Luzon (Region III)">Central Luzon (Region III)</option>
                            <option value="CALABARZON (Region IV-A)">CALABARZON (Region IV-A)</option>
                            <option value="MIMAROPA (Region IV-B)">MIMAROPA (Region IV-B)</option>
                            <option value="Bicol Region (Region V)">Bicol Region (Region V)</option>
                            <option value="Western Visayas (Region VI)">Western Visayas (Region VI)</option>
                            <option value="Central Visayas (Region VII)">Central Visayas (Region VII)</option>
                            <option value="Eastern Visayas (Region VIII)">Eastern Visayas (Region VIII)</option>
                            <option value="Zamboanga Peninsula (Region IX)">Zamboanga Peninsula (Region IX)</option>
                            <option value="Northern Mindanao (Region X)">Northern Mindanao (Region X)</option>
                            <option value="Davao Region (Region XI)">Davao Region (Region XI)</option>
                            <option value="SOCCSKSARGEN (Region XII)">SOCCSKSARGEN (Region XII)</option>
                            <option value="Caraga (Region XIII)">Caraga (Region XIII)</option>
                            <option value="Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)">BARMM</option>
                            <option value="Cordillera Administrative Region (CAR)">Cordillera Administrative Region (CAR)</option>
                            <option value="National Capital Region (NCR)">National Capital Region (NCR)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Province</label>
                        <select name="province" id="edit_province" class="w-full border rounded px-3 py-2" required>
                            <option value="">Select Province</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">City</label>
                        <select name="city" id="edit_city" class="w-full border rounded px-3 py-2" required>
                            <option value="">Select City</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Barangay</label>
                        <input type="text" name="barangay" id="edit_barangay" class="w-full border rounded px-3 py-2" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Postal Code</label>
                    <input type="text" name="postal_code" id="edit_postal_code" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Street Name</label>
                    <input type="text" name="street" id="edit_street" class="w-full border rounded px-3 py-2" required>
                </div>
                <div class="mb-3">
                    <label class="block text-sm mb-1">Label as</label>
                    <select name="label" id="edit_label" class="w-full border rounded px-3 py-2" required>
                        <option value="Home">Home</option>
                        <option value="Work">Work</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" id="closeEditAddress2" class="px-5 py-2 rounded border border-[#a6b7ff] text-[#a6b7ff] bg-white hover:bg-[#d3b7ff]">Cancel</button>
                    <button type="submit" class="bg-[#a6b7ff] hover:bg-[#bce6ff] text-white px-5 py-2 rounded">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="{{ asset('js/customer/addresses.js') }}"></script>
@endsection
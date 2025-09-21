@extends('customerprofile.dashboard')

@section('title', 'Edit Profile')

@section('content')
<div class="card bg-white p-6 md:p-8 border border-gray-100">
  <h2 class="text-xl font-semibold mb-6">Profile Photo</h2>
  <form method="POST" action="{{ route('customer.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <!-- Photo + buttons -->
    <div class="flex items-center gap-5">
      <div id="avatarWrap" class="w-24 h-24 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center">
        <img id="avatarImg"
             src="{{ Auth::user()->customer?->photo ? asset('storage/' . Auth::user()->customer->photo) : asset('images/default-avatar.png') }}"
             alt="Profile"
             class="w-full h-full object-cover {{ Auth::user()->customer?->photo ? '' : 'hidden' }}"
             onerror="this.classList.add('hidden');" />
        <span id="avatarFallback" class="text-3xl text-gray-400 {{ Auth::user()->customer?->photo ? 'hidden' : '' }}">👤</span>
      </div>
      <div class="flex gap-3">
        <button type="button" id="removePhoto"
          class="px-4 py-2 rounded-xl border border-gray-300 hover:bg-gray-50">
          Remove Photo
        </button>
        <label class="px-4 py-2 rounded-xl border border-gray-300 hover:bg-gray-50 cursor-pointer">
          Change photo
          <input id="photoInput" type="file" name="photo" accept="image/*" class="hidden">
        </label>
      </div>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm text-gray-600">First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name', Auth::user()->customer?->first_name) }}" placeholder="First Name"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name', Auth::user()->customer?->middle_name) }}" placeholder="Middle Name"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name', Auth::user()->customer?->last_name) }}" placeholder="Last Name"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Email</label>
        <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}" placeholder="Enter Email Address"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Phone Number</label>
        <input type="text" name="phone" value="{{ old('phone', Auth::user()->customer?->phone) }}" placeholder="Enter Phone Number"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">House / Unit / Lot No.</label>
        <input type="text" name="house_number" value="{{ old('house_number', Auth::user()->customer?->house_number) }}" placeholder="e.g. 123"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Street</label>
        <input type="text" name="street" value="{{ old('street', Auth::user()->customer?->street) }}" placeholder="e.g. Mabini St."
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Barangay</label>
        <input type="text" name="barangay" value="{{ old('barangay', Auth::user()->customer?->barangay) }}" placeholder="e.g. Brgy. San Isidro"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">City / Municipality</label>
        <input type="text" name="city" value="{{ old('city', Auth::user()->customer?->city) }}" placeholder="e.g. Quezon City"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Province</label>
        <input type="text" name="province" value="{{ old('province', Auth::user()->customer?->province) }}" placeholder="e.g. Metro Manila"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Postal Code</label>
        <input type="text" name="postal_code" value="{{ old('postal_code', Auth::user()->customer?->postal_code) }}" placeholder="e.g. 1101"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Country</label>
        <input type="text" name="country" value="{{ old('country', Auth::user()->customer?->country ?? 'Philippines') }}"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">New Password</label>
        <input type="password" name="password" placeholder="Enter New Password"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
      <div>
        <label class="text-sm text-gray-600">Confirm Password</label>
        <input type="password" name="password_confirmation" placeholder="Confirm Password"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>
    </div>
    <div class="pt-2">
      <button type="submit"
              class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:opacity-95">
        Update Profile
      </button>
      @if (session('status'))
        <span class="ml-3 text-sm text-green-600">{{ session('status') }}</span>
      @endif
    </div>
  </form>
</div>
@endsection
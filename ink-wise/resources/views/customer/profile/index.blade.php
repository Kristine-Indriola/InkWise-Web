@extends('layouts.customerprofile')

@section('title', 'Edit Profile')

@section('content')
<div class="card bg-white p-6 md:p-8 border border-gray-100">
  <h2 class="text-xl font-semibold mb-6">Profile Photo</h2>

  <form method="POST" action="{{ route('customerprofile.update') }}" enctype="multipart/form-data">

  <form method="POST" action="{{ route('customerprofile.index') }}" enctype="multipart/form-data">

    @csrf
    @method('PUT')

    <!-- Photo + buttons -->
    <div class="flex items-center gap-5">
      <div id="avatarWrap" class="w-24 h-24 rounded-full bg-gray-100 overflow-hidden flex items-center justify-center">
  @if(Auth::user()->customer?->photo)
    <img id="avatarImg"
         src="{{ asset('storage/' . Auth::user()->customer->photo) }}"
         alt="Profile"
         class="w-full h-full object-cover"
         onerror="console.log('Image failed to load:', this.src)" />
  @else
    <span id="avatarFallback" class="text-3xl text-gray-400"><i class="fa-regular fa-user"></i></span>
  @endif
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
    <!-- Hidden input to tell server to remove photo -->
    <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">
</div>

    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm text-gray-600">First Name</label>
        <input type="text" name="first_name" 
               value="{{ old('first_name', Auth::user()->customer?->first_name) }}"
               placeholder="First Name"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>

      <div>
        <label class="text-sm text-gray-600">Middle Name</label>
        <input type="text" name="middle_name" 
               value="{{ old('middle_name', Auth::user()->customer?->middle_name) }}"
               placeholder="Middle Name"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>

      <div>
        <label class="text-sm text-gray-600">Last Name</label>
        <input type="text" name="last_name" 
               value="{{ old('last_name', Auth::user()->customer?->last_name) }}"
               placeholder="Last Name"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>

      <div>
        <label class="text-sm text-gray-600">Email</label>
        <input type="email" name="email" 
               value="{{ old('email', Auth::user()->email) }}"
               placeholder="Enter Email Address"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>

      <div>
        <label class="text-sm text-gray-600">Phone Number</label>
        <input type="text" name="phone" 
               value="{{ old('phone', Auth::user()->customer?->contact_number) }}"
               placeholder="Enter Phone Number"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>

      <div>
        <label class="text-sm text-gray-600">Date of Birth</label>
        <input type="date" name="birthdate" 
               value="{{ old('birthdate', Auth::user()->customer?->date_of_birth) }}"
               class="w-full mt-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-200">
      </div>

      <div>
        <label class="text-sm text-gray-600 block mb-1">Gender</label>
        <div class="flex items-center gap-6 mt-1">
          <label class="inline-flex items-center">
            <input type="radio" name="gender" value="male"
              {{ old('gender', Auth::user()->customer?->gender) == 'male' ? 'checked' : '' }}
              class="form-radio text-indigo-600">
            <span class="ml-2">Male</span>
          </label>
          <label class="inline-flex items-center">
            <input type="radio" name="gender" value="female"
              {{ old('gender', Auth::user()->customer?->gender) == 'female' ? 'checked' : '' }}
              class="form-radio text-indigo-600">
            <span class="ml-2">Female</span>
          </label>
          <label class="inline-flex items-center">
            <input type="radio" name="gender" value="other"
              {{ old('gender', Auth::user()->customer?->gender) == 'other' ? 'checked' : '' }}
              class="form-radio text-indigo-600">
            <span class="ml-2">Other</span>
          </label>
        </div>
      </div>
    </div>

    <div class="pt-2">
      <button type="submit"
        class="px-5 py-2.5 rounded-xl text-white font-medium hover:opacity-95"
        style="background-color:#a6b7ff;">
        Update Profile
      </button>
      @if (session('status'))
        <span class="ml-3 text-sm text-green-600">{{ session('status') }}</span>
      @endif
    </div>
  </form>
</div>
<script>
    const removeButton = document.getElementById('removePhoto');
    const avatarImg = document.getElementById('avatarImg');
    const avatarFallback = document.getElementById('avatarFallback');
    const removeInput = document.getElementById('removePhotoInput');
    const photoInput = document.getElementById('photoInput');

    removeButton.addEventListener('click', function () {
        if (confirm('Are you sure you want to remove your photo?')) {
            // Set hidden input so server knows to remove the photo
            removeInput.value = 1;

            // Immediately hide the image and show fallback avatar
            avatarImg.classList.add('hidden');
            avatarFallback.classList.remove('hidden');

            // Clear the file input
            photoInput.value = '';

            // Optionally, you can submit the form automatically
            // this.closest('form').submit();
        }
    });

    // Handle photo preview when file is selected
    photoInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                photoInput.value = '';
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Please select an image smaller than 2MB.');
                photoInput.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                // Hide fallback and show image
                avatarFallback.classList.add('hidden');
                avatarImg.classList.remove('hidden');

                // Set the preview image source
                avatarImg.src = e.target.result;

                // Reset remove input since we're uploading a new photo
                removeInput.value = 0;
            };
            reader.readAsDataURL(file);
        }
    });
</script>


@endsection

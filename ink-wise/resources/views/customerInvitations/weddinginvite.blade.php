@php($invitationType = 'Wedding')
@extends('customerInvitations.invitations')

@section('title', 'Wedding Invitations')
<link rel="stylesheet" href="{{ asset('css/customer/template.css') }}">
<script src="{{ asset('js/customer/template.js') }}" defer></script>
@section('content')
<main class="py-12 px-6 text-center">
    <h1 class="page-title">
        <span class="cursive">W</span>edding 
        <span class="cursive">I</span>nvitations
    </h1>
    <p class="page-subtitle mb-10">Choose from our curated selection of elegant invitation designs.</p>

    <!-- Individual Card Grids -->
    <div class="mx-auto max-w-7xl flex flex-wrap gap-6 justify-center">
        <!-- Card 1 -->
        <div class="bg-white rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 border border-gray-100 p-4 flex flex-col items-center w-full max-w-xs">
            <div class="w-full h-[260px] flex items-center justify-center relative">
                <!-- Heart Icon -->
                <button class="absolute top-3 right-3 z-10 bg-white/80 rounded-full p-1 shadow hover:bg-[#a6b7ff] group transition">
                    <svg class="w-6 h-6 text-[#a6b7ff] group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 21C12 21 4 13.5 4 8.5C4 5.42 6.42 3 9.5 3C11.24 3 12.91 3.81 14 5.08C15.09 3.81 16.76 3 18.5 3C21.58 3 24 5.42 24 8.5C24 13.5 16 21 16 21H12Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <img src="{{ asset('customerimages/invite/wedding1.png') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-contain template-image preview-trigger"
                     data-template="Floral Invitation"
                >
                <video class="w-full h-full object-contain template-video hidden" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/Wedding/Floral1.mp4') }}" type="video/mp4">
                </video>
            </div>
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Floral1.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding1.png') }}"
                        style="background-color:white"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Floral2.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding1b.png') }}"
                        style="background-color:lightgreen"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Floral3.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding1c.png') }}"
                        style="background-color:darkgreen"></button>
            </div>
            <div class="font-semibold text-base text-gray-800 mt-3">4.6" x 7.2"</div>
            <div class="mt-1 text-sm text-gray-500 line-through">As low as ₱1,200</div>
            <div class="text-green-600 font-bold text-base">As low as <span class="text-[#009966]">₱900</span> per piece</div>
        </div>
        <!-- Card 2 -->
        <div class="bg-white rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 border border-gray-100 p-4 flex flex-col items-center w-full max-w-xs">
            <div class="w-full h-[260px] flex items-center justify-center relative">
                <!-- Heart Icon -->
                <button class="absolute top-3 right-3 z-10 bg-white/80 rounded-full p-1 shadow hover:bg-[#a6b7ff] group transition">
                    <svg class="w-6 h-6 text-[#a6b7ff] group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 21C12 21 4 13.5 4 8.5C4 5.42 6.42 3 9.5 3C11.24 3 12.91 3.81 14 5.08C15.09 3.81 16.76 3 18.5 3C21.58 3 24 5.42 24 8.5C24 13.5 16 21 16 21H12Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <img src="{{ asset('customerimages/invite/wedding3.jpg') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-contain template-image preview-trigger"
                     data-template="Beige Invitation"
                >
                <video class="w-full h-full object-contain template-video hidden" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/Wedding/Beige1.mp4') }}" type="video/mp4">
                </video>
            </div>
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Beige1.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding3.jpg') }}"
                        style="background-color:#f1eee9"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Beige2.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding3b.jpg') }}"
                        style="background-color:#230e00"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Beige3.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding3c.jpg') }}"
                        style="background-color:#b49a6a"></button>
            </div>
            <div class="font-semibold text-base text-gray-800 mt-3">5.5" x 4"</div>
            <div class="mt-1 text-sm text-gray-500 line-through">As low as ₱1,350</div>
            <div class="text-green-600 font-bold text-base">As low as <span class="text-[#009966]">₱700</span> per piece</div>
        </div>
        <!-- Card 3 (Adjusted for fit and working swatch) -->
        <div class="bg-white rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 border border-gray-100 p-4 flex flex-col items-center w-full max-w-xs">
            <div class="w-full h-[260px] flex items-center justify-center relative">
                <!-- Heart Icon -->
                <button class="absolute top-3 right-3 z-10 bg-white/80 rounded-full p-1 shadow hover:bg-[#a6b7ff] group transition">
                    <svg class="w-6 h-6 text-[#a6b7ff] group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 21C12 21 4 13.5 4 8.5C4 5.42 6.42 3 9.5 3C11.24 3 12.91 3.81 14 5.08C15.09 3.81 16.76 3 18.5 3C21.58 3 24 5.42 24 8.5C24 13.5 16 21 16 21H12Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <img src="{{ asset('customerimages/invite/wedding2.png') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-contain template-image preview-trigger"
                     data-template="Grey Watercolor"
                >
                <video class="w-full h-full object-contain template-video hidden" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/Wedding/Grey1.mp4') }}" type="video/mp4">
                </video>
            </div>
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Grey1.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding2.png') }}"
                        style="background-color:pink"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Grey2.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding2b.png') }}"
                        style="background-color:blue"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Grey3.mp4') }}"
                        data-image="{{ asset('customerimages/invite/wedding2c.png') }}"
                        style="background-color:lavender"></button>
            </div>
            <div class="font-semibold text-base text-gray-800 mt-3">4.6" x 7.2"</div>
            <div class="mt-1 text-sm text-gray-500 line-through">As low as ₱1,500</div>
            <div class="text-green-600 font-bold text-base">As low as <span class="text-[#009966]">₱900</span> per piece</div>
        </div>
    </div>
</main>
@endsection





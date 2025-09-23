@php($invitationType = 'Birthday')
@extends('customer.Invitations.invitations')

@section('title', 'Birthday Invitations')

@section('content')
<!-- Page Content -->
<main class="py-12 px-6 text-center">
    <h1 class="page-title">
        <span class="cursive">B</span>irthday 
        <span class="cursive">I</span>nvitations
    </h1>
    <p class="page-subtitle mb-10">Choose from our curated selection of fun birthday invitation designs.</p>

    

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1 -->
        <div class="w-full max-w-md mx-auto h-[420px]">
            <div class="w-full h-[300px] flex items-center justify-center relative">
                <img src="{{ asset('customerimages/invite/birthday1.png') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-contain template-image">
                <video class="w-full h-full object-contain template-video hidden" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/birthday/birthday1.mp4') }}" type="video/mp4">
                </video>
                <!-- Hidden Front & Back Images -->
                <img src="{{ asset('customerimages/invite/birthday1-front.png') }}" alt="Front Design" class="hidden front-image">
                <img src="{{ asset('customerimages/invite/birthday1-back.png') }}" alt="Back Design" class="hidden back-image">
            </div>
            <div class="text-center mt-2 font-semibold text-gray-700">Pink and Black Minimal 18th Birthday Invitation</div>
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday1.mp4') }}"
                        style="background-color:lightpink"></button>
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday2.mp4') }}"
                        style="background-color:lavender"></button>
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday3.mp4') }}"
                        style="background-color:darkmagenta"></button>
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('customer.profile.orderform') }}">
                    <button class="px-5 py-2 text-sm font-medium text-black border-2 border-cyan-400 rounded-full bg-white hover:bg-cyan-50 hover:border-cyan-500 transition">
                        Choose Template
                    </button>
                </a>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="w-full max-w-md mx-auto h-[420px]">
            <div class="w-full h-[300px] flex items-center justify-center relative">
                <img src="{{ asset('customerimages/invite/birthday2.png') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-contain template-image">
                <video class="w-full h-full object-contain template-video hidden" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/birthday/birthday4.mp4') }}" type="video/mp4">
                </video>
                <!-- Hidden Front & Back Images -->
                <img src="{{ asset('customerimages/invite/birthday2-front.png') }}" alt="Front Design" class="hidden front-image">
                <img src="{{ asset('customerimages/invite/birthday2-back.png') }}" alt="Back Design" class="hidden back-image">
            </div>
            <div class="text-center mt-2 font-semibold text-gray-700">Pink and White Watercolor Birthday Party Invitation</div>
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday4.mp4') }}"
                        style="background-color:lightpink"></button>
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday5.mp4') }}"
                        style="background-color:#745e4d"></button>
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday6.mp4') }}"
                        style="background-color:#1649ff"></button>
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('customer.profile.orderform') }}">
                    <button class="px-5 py-2 text-sm font-medium text-black border-2 border-cyan-400 rounded-full bg-white hover:bg-cyan-50 hover:border-cyan-500 transition">
                        Choose Template
                    </button>
                </a>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="w-full max-w-md mx-auto h-[420px]">
            <div class="w-full h-[300px] flex items-center justify-center relative rounded-none overflow-hidden border border-gray-200 shadow">
                <img src="{{ asset('customerimages/invite/birthday3.png') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-cover template-image rounded-none">
                <video class="w-full h-full object-cover template-video hidden rounded-none" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/birthday/birthday7.mp4') }}" type="video/mp4">
                </video>
                <!-- Hidden Front & Back Images -->
                <img src="{{ asset('customerimages/invite/birthday3-front.png') }}" alt="Front Design" class="hidden front-image">
                <img src="{{ asset('customerimages/invite/birthday3-back.png') }}" alt="Back Design" class="hidden back-image">
            </div>
            <div class="text-center mt-2 font-semibold text-gray-700">Blue and Gold Birthday Bash Invitation</div>
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday7.mp4') }}"
                        style="background-color:skyblue"></button>
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday8.mp4') }}"
                        style="background-color:gold"></button>
                <button class="w-5 h-5 rounded-full border-2 border-gray-300 shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/birthday/birthday9.mp4') }}"
                        style="background-color:navy"></button>
            </div>
            <div class="text-center mt-4">
                <a href="{{ route('customer.profile.orderform') }}">
                    <button class="px-5 py-2 text-sm font-medium text-black border-2 border-cyan-400 rounded-full bg-white hover:bg-cyan-50 hover:border-cyan-500 transition">
                        Choose Template
                    </button>
                </a>
            </div>
        </div>
    </div>
    
</main>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.video-swatches').forEach(group => {
        const card = group.closest('.w-full');
        const video = card.querySelector('.template-video');
        const image = card.querySelector('.template-image');
        const source = video ? video.querySelector('source') : null;

        group.querySelectorAll('.swatch-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const videoSrc = btn.getAttribute('data-video');
                if (video && image && source) {
                    image.classList.add('hidden');
                    video.classList.remove('hidden');
                    if (source.src !== videoSrc) {
                        source.src = videoSrc;
                        video.load();
                    }
                    video.play();
                }
            });
        });
    });
});
</script>


@php($invitationType = 'Baptism')
@extends('customer.Invitations.invitations')

@section('title', 'Wedding Invitations')

@section('content')
<!-- Page Content -->
<main class="py-12 px-6 text-center">
    <h1 class="page-title">
        <span class="cursive">W</span>edding 
        <span class="cursive">I</span>nvitations
    </h1>
    <p class="page-subtitle mb-10">Choose from our curated selection of elegant invitation designs.</p>

    
    <!-- Cards Grid -->
    
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Card 1 -->
        <div class="w-full max-w-md mx-auto h-[420px]">
            <div class="w-full h-[300px] flex items-center justify-center relative">
                <img src="{{ asset('customerimages/invite/wedding1.png') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-contain template-image">
                <video class="w-full h-full object-contain template-video hidden" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/Wedding/Floral1.mp4') }}" type="video/mp4">
                </video>

                <!-- Hidden Front & Back Images -->
                <img src="{{ asset('customerimages/invite/wedding1-front.png') }}" alt="Front Design" class="hidden front-image">
                <img src="{{ asset('customerimages/invite/wedding1-back.png') }}" alt="Back Design" class="hidden back-image">
            </div>
            
            <!-- Invitation Name -->
            <div class="text-center mt-2 font-semibold text-gray-700">Floral Invitation</div>

            <!-- Swatches -->
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Floral1.mp4') }}"
                        style="background-color:white"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Floral2.mp4') }}"
                        style="background-color:lightgreen"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Floral3.mp4') }}"
                        style="background-color:darkgreen"></button>
            </div>

            <!-- Button -->
            <div class="text-center mt-4">
                <a href="{{ route('design.edit') }}">
                    <button class="px-5 py-2 text-sm font-medium text-[#06b6d4] border-2 border-[#06b6d4] rounded-full bg-white hover:bg-[#e0f7fa] hover:border-[#0891b2] transition">
                        Choose Template
                    </button>
                </a>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="w-full max-w-md mx-auto h-[420px]">
            <div class="w-full h-[300px] flex items-center justify-center relative">
                <img src="{{ asset('customerimages/invite/wedding3.jpg') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-contain template-image">
                <video class="w-full h-full object-contain template-video hidden" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/Wedding/Beige1.mp4') }}" type="video/mp4">
                </video>

                <!-- Hidden Front & Back Images -->
                <img src="{{ asset('customerimages/invite/wedding3-front.png') }}" alt="Front Design" class="hidden front-image">
                <img src="{{ asset('customerimages/invite/wedding3-back.png') }}" alt="Back Design" class="hidden back-image">
            </div>
            
            <!-- Invitation Name -->
            <div class="text-center mt-2 font-semibold text-gray-700">Beige Invitation</div>

            <!-- Swatches -->
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Beige1.mp4') }}"
                        style="background-color:#f1eee9"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Beige2.mp4') }}"
                        style="background-color:#230e00"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Beige3.mp4') }}"
                        style="background-color:#b49a6a"></button>
            </div>

            <!-- Button -->
            <div class="text-center mt-4">
                <a href="{{ route('design.edit') }}">
                    <button class="px-5 py-2 text-sm font-medium text-[#06b6d4] border-2 border-[#06b6d4] rounded-full bg-white hover:bg-[#e0f7fa] hover:border-[#0891b2] transition">
                        Choose Template
                    </button>
                </a>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="w-full max-w-md mx-auto h-[420px]">
            <div class="w-full h-[300px] flex items-center justify-center relative rounded-none overflow-hidden border border-gray-200 shadow">
                <img src="{{ asset('customerimages/invite/wedding2.png') }}" 
                     alt="Template Preview" 
                     class="w-full h-full object-cover template-image rounded-none">
                <video class="w-full h-full object-cover template-video hidden rounded-none" autoplay muted loop playsinline>
                    <source src="{{ asset('customerVideo/Wedding/Grey1.mp4') }}" type="video/mp4">
                </video>

                <!-- Hidden Front & Back Images -->
                <img src="{{ asset('customerimages/invite/wedding2-front.png') }}" alt="Front Design" class="hidden front-image">
                <img src="{{ asset('customerimages/invite/wedding2-back.png') }}" alt="Back Design" class="hidden back-image">
            </div>

            <!-- Invitation Name -->
            <div class="text-center mt-2 font-semibold text-gray-700">Grey Watercolor</div>

            <!-- Swatches -->
            <div class="flex justify-center gap-2 mt-3 video-swatches">
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Grey1.mp4') }}"
                        style="background-color:pink"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Grey2.mp4') }}"
                        style="background-color:blue"></button>
                <button class="w-5 h-5 rounded-full border-2 border-[#06b6d4] shadow cursor-pointer swatch-btn"
                        data-video="{{ asset('customerVideo/Wedding/Grey3.mp4') }}"
                        style="background-color:lavender"></button>
            </div>

            <!-- Button -->
            <div class="text-center mt-4">
                <a href="{{ route('design.edit') }}">
                    <button class="px-5 py-2 text-sm font-medium text-[#06b6d4] border-2 border-[#06b6d4] rounded-full bg-white hover:bg-[#e0f7fa] hover:border-[#0891b2] transition">
                        Choose Template
                    </button>
                </a>
            </div>
        </div>
    </div>
</main>

<!-- Script -->
<script>
document.querySelectorAll('.video-swatches').forEach(group => {
    const card = group.closest('.w-full'); 
    const video = card.querySelector('.template-video');
    const image = card.querySelector('.template-image');

    const buttons = group.querySelectorAll('.swatch-btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const videoSrc = btn.getAttribute('data-video');
            if (video && image) {
                image.classList.add('hidden');
                video.classList.remove('hidden');
                video.querySelector('source').src = videoSrc;
                video.load();
                video.play();
            }
        });
    });
});
</script>
@endsection

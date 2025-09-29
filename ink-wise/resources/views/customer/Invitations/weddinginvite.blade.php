@php($invitationType = 'Wedding')
@extends('customer.Invitations.invitations')

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
        @foreach($products as $product)
        <div class="bg-white rounded-2xl shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 border border-gray-100 p-4 flex flex-col items-center w-full max-w-xs">
            <div class="w-full h-[260px] flex items-center justify-center relative">
                <!-- Heart Icon -->
                <button class="absolute top-3 right-3 z-10 bg-white/80 rounded-full p-1 shadow hover:bg-[#a6b7ff] group transition">
                    <svg class="w-6 h-6 text-[#a6b7ff] group-hover:text-white transition" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 21C12 21 4 13.5 4 8.5C4 5.42 6.42 3 9.5 3C11.24 3 12.91 3.81 14 5.08C15.09 3.81 16.76 3 18.5 3C21.58 3 24 5.42 24 8.5C24 13.5 16 21 16 21H12Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <img src="{{ $product->image ? \App\Support\ImageResolver::url($product->image) : asset('images/placeholder.png') }}" 
                     alt="{{ $product->name }}" 
                     class="w-full h-full object-contain template-image preview-trigger"
                     data-template="{{ $product->name }}"
                >
            </div>
            <div class="font-semibold text-base text-gray-800 mt-3">{{ $product->name }}</div>
            <div class="mt-1 text-sm text-gray-500">{{ $product->theme_style }}</div>
            <div class="text-green-600 font-bold text-base">Starting at ₱{{ $product->unit_price ?? 'TBD' }}</div>
        </div>
        @endforeach
    </div>
</main>
@endsection





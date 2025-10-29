<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkwise Dashboard</title>


   <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');

        :root {
            --color-primary: #06b6d4;
            --color-primary-dark: #0891b2;
            --shadow-elevated: 0 16px 48px rgba(4, 29, 66, 0.18);
            --font-display: 'Playfair Display', serif;
            --font-accent: 'Seasons', serif;
            --font-script: 'Edwardian Script ITC', cursive;
        }

        .layout-container {
            width: min(1200px, 100%);
            margin-inline: auto;
            padding-inline: clamp(16px, 5vw, 48px);
        }

        .layout-stack {
            display: flex;
            flex-direction: column;
            gap: clamp(40px, 6vw, 72px);
        }

        body {
            font-family: var(--font-accent);
            color: #1f2937;
            background-color: #ffffff;
        }

        h1, h2, h3, h4 {
            font-family: var(--font-display);
        }

        a {
            transition: color .2s ease, transform .2s ease;
        }

        .logo-i {
            line-height: 1;
        }

        .logo-script {
            font-family: var(--font-script);
            color: var(--color-primary);
        }

        .logo-serif {
            font-family: var(--font-display);
            color: var(--color-primary-dark);
        }

        .page-title {
            font-size: 1.1rem;
        }

        .hero-wrapper {
            min-height: 420px;
        }

        .hero-title {
            font-size: clamp(2.25rem, 4vw, 3rem);
            font-weight: 700;
            display: inline-flex;
            flex-wrap: wrap;
            gap: .35rem;
        }

        .hero-title-highlight {
            color: var(--color-primary);
        }

        .hero-title-accent {
            color: var(--color-primary-dark);
        }

        .hero-subtitle {
            font-size: 1.05rem;
            color: #4b5563;
        }

        .btn-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: .75rem 1.5rem;
            font-weight: 600;
            line-height: 1;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .btn-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(6, 182, 212, 0.18);
        }

        .btn-primary {
            background: var(--color-primary);
            color: #ffffff;
            font-family: var(--font-display);
        }

        .btn-outline {
            border: 2px solid transparent;
            background-origin: border-box;
            background-clip: padding-box, border-box;
            background-image: linear-gradient(#ffffff, #ffffff), linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: #1f2937;
            font-family: var(--font-display);
        }

        .btn-outline:hover {
            color: var(--color-primary-dark);
        }

        .focus-ring:focus {
            outline: 3px solid rgba(6, 182, 212, 0.25);
            outline-offset: 2px;
        }

        .flip-card .flip-card-inner {
            perspective: 1000px;
        }

        .flip-card-front,
        .flip-card-back {
            min-height: 14rem;
        }

        @media (min-width: 768px) {
            .flip-card-front,
            .flip-card-back {
                min-height: 22rem;
            }
        }

        .template-image,
        .flip-card-front video,
        .flip-card-back img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .flip-card {
            margin-top: 0;
        }

        .space-y-6 {
            gap: .75rem;
        }

        .text-5xl {
            font-size: 2.25rem;
        }

        .text-lg {
            font-size: 0.95rem;
        }

        .chat-widget {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            z-index: 60;
        }

        .chat-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            padding: 6px;
            background: linear-gradient(90deg, #5de0e6, #004aad);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 26px rgba(4, 29, 66, 0.14);
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .chat-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(4, 29, 66, 0.18);
        }

        .chat-btn:active {
            transform: scale(.98);
        }

        .chat-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .chat-inner img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
        }

        .chat-panel {
            width: 560px;
            max-width: calc(100vw - 3rem);
            position: absolute;
            right: 0;
            bottom: 100px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: var(--shadow-elevated);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        /* icon placement for header */

        .chat-header {
            padding: 16px 18px;
            display: flex;
            gap: 12px;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        }

        .chat-header h4 {
            margin: 0;
            font-weight: 800;
            font-size: 16px;
            color: #044e86;
        }

        .chat-body {
            padding: 16px 16px 18px;
            max-height: 520px;
            overflow: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            scroll-behavior: smooth;
            background: linear-gradient(180deg, rgba(6, 182, 212, 0.02), transparent);
        }

        .chat-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
        }

        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .chat-body::-webkit-scrollbar {
            width: 10px;
        }

        .chat-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-body::-webkit-scrollbar-thumb {
            background: rgba(4, 29, 66, 0.1);
            border-radius: 9999px;
        }

        .chat-input {
            display: flex;
            gap: 12px;
            padding: 14px;
            border-top: 1px solid rgba(0, 0, 0, 0.04);
        }

        .chat-input input[type="text"] {
            flex: 1;
            border-radius: 999px;
            padding: 12px 18px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            outline: none;
            background: #fbfeff;
            font-size: 15px;
        }

        .chat-input button {
            background: var(--color-primary);
            color: #fff;
            border-radius: 999px;
            padding: 10px 16px;
            border: 0;
            cursor: pointer;
            font-weight: 700;
        }

        .msg {
            display: inline-flex;
            position: relative;
            max-width: 86%;
            padding: 12px 14px;
            border-radius: 16px;
            font-size: 15px;
            line-height: 1.4;
            word-break: break-word;
            box-shadow: 0 8px 22px rgba(4, 29, 66, 0.05);
        }

        .msg .avatar {
            width: 44px;
            height: 44px;
            border-radius: 9999px;
            overflow: hidden;
            flex-shrink: 0;
            box-shadow: 0 3px 8px rgba(4, 29, 66, 0.06);
        }

        .msg .bubble {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 48px;
        }

        .msg .text {
            white-space: pre-wrap;
        }

        .msg .time {
            font-size: 12px;
            color: #6b7280;
            align-self: flex-end;
            margin-top: 6px;
        }

        .msg.user {
            background: linear-gradient(180deg, #e6f7fb, #c9f0f5);
            margin-left: auto;
            align-self: flex-end;
            color: #022a37;
            border-bottom-right-radius: 6px;
        }

        .msg.bot {
            background: linear-gradient(180deg, #f4f8ff, #eaf3ff);
            align-self: flex-start;
            color: #03305a;
            border-bottom-left-radius: 6px;
            gap: 12px;
            align-items: flex-start;
        }

        .msg.bot::after,
        .msg.user::after {
            content: "";
            position: absolute;
            top: 16px;
            width: 14px;
            height: 14px;
            transform: rotate(45deg);
            box-shadow: 0 8px 14px rgba(4, 29, 66, 0.03);
            border-radius: 2px;
            z-index: 0;
            background: inherit;
        }

        .msg.bot::after {
            left: -7px;
        }

        .msg.user::after {
            right: -7px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            margin-top: 0.5rem;
            width: 12rem;
            background: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 16px 32px rgba(2, 6, 23, 0.16);
            border: 1px solid rgba(0, 0, 0, 0.04);
            overflow: hidden;
            transform: translateY(0.5rem);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
            z-index: 50;
        }

        .dropdown-menu.is-open {
            display: block;
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .dropdown-menu a,
        .dropdown-menu button,
        .dropdown-menu div {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 0.95rem;
            color: #374151;
            background: transparent;
            text-align: left;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .dropdown-menu a:hover,
        .dropdown-menu button:hover,
        .dropdown-menu div:hover {
            background: #e0f7fa;
            color: #065f73;
        }

        #bgCanvas {
            display: block;
            background: linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .hero-wrapper::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.4));
            pointer-events: none;
            z-index: 5;
        }

        #mainNav.mobile-open {
            display: block !important;
        }

        #mainNav {
            z-index: 40;
        }

        @media (max-width: 767px) {
            #mainNav {
                display: none;
                position: absolute;
                left: 0;
                right: 0;
                top: 100%;
                background: white;
                padding: 1rem;
                box-shadow: 0 8px 30px rgba(2, 6, 23, 0.08);
                border-bottom-left-radius: 8px;
                border-bottom-right-radius: 8px;
            }

            #mainNav a {
                display: block;
                padding: 0.5rem 0;
            }
        }

        @media (max-width: 720px) {
            .chat-panel {
                width: 92vw;
                right: 4%;
                bottom: 88px;
            }

            .chat-btn {
                width: 70px;
                height: 70px;
            }

            .chat-inner img,
            .chat-avatar {
                width: 38px;
                height: 38px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
   </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Icon fonts used by invitations/header -->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (request('modal') === 'login')
        <script>
            window.__OPEN_MODAL__ = 'login';
        </script>
    @endif

    <!-- Custom CSS -->

    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customertemplates.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/template.css') }}">

    <!-- Custom JS -->
    <script src="{{ asset('js/customer/customer.js') }}" defer></script>
    <script src="{{ asset('js/customer/customertemplate.js') }}" defer></script>
    <script src="{{ asset('js/customer/template.js') }}" defer></script>

    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
      <link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">
    <!-- Chat widget styles -->
    <style>
        /* Responsive tweaks for dashboard */
        .logo-i { line-height: 1; }
    .page-title { font-size: 1.1rem; }

        /* Flip card responsive sizing */
        .flip-card .flip-card-inner { perspective: 1000px; }
        .flip-card-front, .flip-card-back { min-height: 14rem; }
        @media (min-width: 768px) {
            .flip-card-front, .flip-card-back { min-height: 22rem; }
        }

    /* Ensure video & image scale nicely */
    .template-image, .flip-card-front video, .flip-card-back img { width: 100%; height: 100%; object-fit: cover; }

    /* Hero spacing tweaks */
    .flip-card { margin-top: 0; }
    .space-y-6 { gap: .75rem; }
    .text-5xl { font-size: 2.25rem; }
    .text-lg { font-size: 0.95rem; }

        /* Accessible focus styles */
        .focus-ring:focus { outline: 3px solid rgba(6,182,212,0.25); outline-offset: 2px; }

        /* Chat widget container (fixed bottom-right) */
        .chat-widget { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 60; }

        /* Circular button with 90deg linear-gradient stroke (enlarged) */
       .chat-btn {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    padding: 6px;
    background: linear-gradient(90deg, #5de0e6, #004aad);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 26px rgba(4, 29, 66, 0.14);
    cursor: pointer;
    transition: transform .15s ease;
}
        .chat-btn:active { transform: scale(.98); }

        /* inner circle that holds the image */
       .chat-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
       .chat-inner img {
    width: 45px;   /* fixed size */
    height: 45px;
    object-fit: cover;
    border-radius: 50%;
}

        /* chat panel (enlarged for readability) */
      .chat-panel {
    width: 560px;
    max-width: calc(100vw - 3rem);
    position: absolute;
    right: 0;
    bottom: 100px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 16px 48px rgba(4,29,66,0.18);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(0,0,0,0.04);
}
        .chat-header { padding: 16px 18px; display:flex; gap:12px; align-items:center; border-bottom: 1px solid rgba(0,0,0,0.04); }
        .chat-header h4 { margin:0; font-weight:800; font-size:16px; color:#044e86; }
        .chat-body {
            padding:16px;
            padding-bottom: 18px;
            max-height:520px;
            overflow:auto;
            display:flex;
            flex-direction:column;
            gap:14px;
            scroll-behavior:smooth;
            background: linear-gradient(180deg, rgba(6,182,212,0.02), transparent);
        }

        .chat-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
}
.chat-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}





        /* custom thin scrollbar */
        .chat-body::-webkit-scrollbar { width: 10px; }
        .chat-body::-webkit-scrollbar-track { background: transparent; }
        .chat-body::-webkit-scrollbar-thumb { background: rgba(4,29,66,0.10); border-radius: 9999px; }

        .chat-input { display:flex; gap:12px; padding:14px; border-top:1px solid rgba(0,0,0,0.04); }
        .chat-input input[type="text"]{ flex:1; border-radius:999px; padding:12px 18px; border:1px solid rgba(0,0,0,0.08); outline:none; background:#fbfeff; font-size:15px; }
        .chat-input button{ background:#06b6d4; color:#fff; border-radius:999px; padding:10px 16px; border:0; cursor:pointer; font-weight:700; }

        /* message bubbles (larger) */
        .msg { display:inline-flex; position:relative; max-width:86%; padding:12px 14px; border-radius:16px; font-size:15px; line-height:1.4; word-break:break-word; box-shadow: 0 8px 22px rgba(4,29,66,0.05); }
        .msg .avatar { width:44px; height:44px; border-radius:9999px; overflow:hidden; flex-shrink:0; box-shadow:0 3px 8px rgba(4,29,66,0.06); }
        .msg .bubble { display:flex; flex-direction:column; gap:8px; min-width:48px; }
        .msg .text { white-space:pre-wrap; }
        .msg .time { font-size:12px; color:#6b7280; align-self:flex-end; margin-top:6px; }

        .msg.user {
            background: linear-gradient(180deg,#e6f7fb,#c9f0f5);
            margin-left:auto;
            align-self:flex-end;
            color:#022a37;
            border-bottom-right-radius:6px;
        }
        .msg.bot {
            background: linear-gradient(180deg,#f4f8ff,#eaf3ff);
            align-self:flex-start;
            color:#03305a;
            border-bottom-left-radius:6px;
            gap:12px;
            align-items:flex-start;
        }

        /* little "tail" on bubbles */
        .msg.bot::after, .msg.user::after {
            content: "";
            position: absolute;
            top: 16px;
            width: 14px;
            height: 14px;
            transform: rotate(45deg);
            box-shadow: 0 8px 14px rgba(4,29,66,0.03);
            border-radius: 2px;
            z-index: 0;
            background: inherit;
        }
        .msg.bot::after { left: -7px; }
        .msg.user::after { right: -7px; }

        /* responsive tweaks */

       @media (max-width: 720px) {
    .chat-panel { width: 92vw; right: 4%; bottom: 88px; }
    .chat-btn { width: 70px; height: 70px; }
    .chat-inner img, .chat-avatar { width: 38px; height: 38px; }
}

        @media (max-width: 720px) {
            .chat-panel { width: 92vw; right: 4%; bottom: 88px; }
            .chat-btn { width: 80px; height: 80px; }
        }

        /* Mobile navigation panel */
        #mainNav.mobile-open { display: block !important; }
        #mainNav { z-index: 40; }
        @media (max-width: 767px) {
            #mainNav { display: none; position: absolute; left: 0; right: 0; top: 100%; background: white; padding: 1rem; box-shadow: 0 8px 30px rgba(2,6,23,0.08); border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
            #mainNav a { display: block; padding: .5rem 0; }
        }

        /* Hero background canvas */
        .hero-wrapper { min-height: 420px; }
        #bgCanvas { display:block; background: linear-gradient(180deg, #ffffff, #fbfdff); }
        /* gentle overlay to make text pop */
        .hero-wrapper::after { content: ''; position: absolute; inset:0; background: linear-gradient(180deg, rgba(255,255,255,0.0), rgba(255,255,255,0.4)); pointer-events: none; z-index:5; }

        header { position: fixed; top: 0; width: 100%; z-index: 50; }
        body { padding-top: 64px; }

    </style>
</head>
<body id="dashboard" class="antialiased bg-white">

    
   <!-- Top Navigation Bar -->
<header class="shadow animate-fade-in-down bg-white w-full">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center animate-bounce-slow flex-shrink-0">
            <span class="text-5xl font-bold logo-i logo-script">I</span>
            <span class="text-2xl font-bold logo-serif">nkwise</span>
        </div>

        <!-- Navigation Links -->
        <button id="mobileNavBtn" class="md:hidden p-2 rounded-md focus-ring mr-2" aria-label="Toggle navigation" aria-controls="mainNav" aria-expanded="false">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <!-- Mobile search toggle -->
        <button id="mobileSearchBtn" class="md:hidden p-2 rounded-md focus-ring mr-2" aria-label="Toggle search" title="Search">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
        </button>
        <nav id="mainNav" class="hidden md:flex flex-wrap space-x-6" role="navigation">
            <a href="#dashboard" class="text-gray-700 hover:text-[#06b6d4]">Home</a>
            <a href="#categories" class="text-gray-700 hover:text-[#06b6d4]">Categories</a>
            <a href="#templates" class="text-gray-700 hover:text-[#06b6d4]">Templates</a>
            <a href="#about" class="text-gray-700 hover:text-[#06b6d4]">About</a>
            <a href="#contact" class="text-gray-700 hover:text-[#06b6d4]">Contact</a>
        </nav>

        <!-- Mobile search input (hidden on desktop) -->
        <div id="mobileSearch" class="hidden md:hidden w-full px-4 mt-2">
            <form action="{{ url('/search') }}" method="GET">
                <input type="text" name="query" placeholder="Search..." class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-[#06b6d4]" />
            </form>
        </div>

      
        <div class="flex items-center space-x-4 relative min-w-0">
            <!-- Search Form -->
            <form action="{{ url('/search') }}" method="GET" class="hidden md:flex">
                <div class="search-with-icons" style="position:relative; display:flex; align-items:center;">
                    <input type="text" name="query" placeholder="Search..."
                           class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-[#06b6d4]"
                           style="padding-right:3.5rem;" />
                </div>
            </form>
  
   {{-- If not logged in --}}
@guest
     <a href="{{ route('customer.login') }}"
         id="openLogin"
         class="btn-pill btn-primary animate-ocean focus-ring">
       Sign in
    </a>
@endguest

{{-- If logged in --}}
@auth
    <div class="relative min-w-0 group">
        <button id="userDropdownBtn" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
            {{-- Display customer's name or fallback --}}
            <span>{{ Auth::user()->customer?->first_name ?? Auth::user()->email ?? 'Customer' }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div id="userDropdownMenu"
             class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50 hidden group-hover:block">
            <!-- Profile -->
            <a href="{{ route('customer.profile.index') }}"
               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">
                My Account
            </a>
            <!-- My Purchase (link to my_purchase) -->
            <a href="{{ route('customer.my_purchase.completed') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">
                My Purchase
            </a>
            <!-- My Favorites (link to favorites) -->
            <a href="{{ route('customer.favorites') }}" class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">
                My Favorites
            </a>
            <!-- Logout -->
            <form method="POST" action="{{ route('customer.logout') }}">
    @csrf
    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">Logout</button>
</form>

        </div>
    @endauth
</div>
</header>
        
<script>
document.addEventListener('DOMContentLoaded', function () {
    // inject icons into header right-side (only on desktop)
    try {
        const headerRight = document.querySelector('header .flex.items-center.space-x-4.relative.min-w-0');
        const searchIcons = document.querySelector('.search-icons');
        // check if icons already present anywhere
        if (!document.querySelector('.nav-icon-button')) {
            const notifications = document.createElement('a');
            notifications.className = 'nav-icon-button';
            notifications.setAttribute('href', '{{ route('customer.notifications') }}');
            notifications.setAttribute('aria-label', 'Notifications');
            notifications.setAttribute('title', 'Notifications');
            notifications.innerHTML = '<i class="fi fi-br-bell" aria-hidden="true"></i>';

            // Add notification badge if there are unread notifications
            @auth
                @php
                    $unreadCount = auth()->user()->unreadNotifications()->count();
                @endphp
                @if($unreadCount > 0)
                    const badge = document.createElement('span');
                    badge.className = 'notification-badge';
                    badge.textContent = '{{ $unreadCount }}';
                    notifications.appendChild(badge);
                @endif
            @endauth

            const fav = document.createElement('a');
            fav.className = 'nav-icon-button';
            fav.setAttribute('href', '{{ route('customer.favorites') }}');
            fav.setAttribute('aria-label', 'My favorites');
            fav.setAttribute('title', 'My favorites');
            fav.innerHTML = '<i class="fi fi-br-comment-heart" aria-hidden="true"></i>';

            const cart = document.createElement('a');
            cart.className = 'nav-icon-button';
            cart.setAttribute('href', '#');
            cart.setAttribute('aria-label', 'My cart');
            cart.setAttribute('title', 'My cart');
            cart.innerHTML = '<i class="bi bi-bag-heart-fill" aria-hidden="true"></i>';

            if (headerRight) {
                // prefer to place icons right after the search form (outside the input)
                const searchForm = headerRight.querySelector('form[action="{{ url('/search') }}"]');
                if (searchForm && searchForm.parentElement) {
                    const iconsWrap = document.createElement('div');
                    iconsWrap.className = 'hidden md:flex items-center gap-2 ml-3';
                    iconsWrap.appendChild(notifications);
                    iconsWrap.appendChild(fav);
                    iconsWrap.appendChild(cart);
                    // insert after the search form element
                    searchForm.parentElement.insertBefore(iconsWrap, searchForm.nextSibling);
                } else {
                    const container = document.createElement('div');
                    container.className = 'hidden md:flex items-center gap-2';
                    container.appendChild(notifications);
                    container.appendChild(fav);
                    container.appendChild(cart);
                    headerRight.insertBefore(container, headerRight.firstChild);
                }
            }
        }
    } catch (e) { /* ignore */ }

    // Attach behavior: check server order, create from sessionStorage if missing, then redirect to /order/summary
    const storageKey = 'inkwise-finalstep';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const icons = Array.from(document.querySelectorAll('.nav-icon-button'));
    if (!icons.length) return;

    const serverHasOrder = async () => {
        try {
            const res = await fetch('/order/summary.json', { method: 'GET', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            return res.ok;
        } catch (e) { return false; }
    };

    const createOrderFromSummary = async (summary) => {
        if (!summary) return false;
        const pid = summary.productId ?? summary.product_id ?? null;
        const quantity = Number(summary.quantity ?? 10);
        if (!pid) return false;
        try {
            const res = await fetch('/order/cart/items', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
                },
                credentials: 'same-origin',
                body: JSON.stringify({ product_id: Number(pid), quantity: Number(quantity) })
            });
            return res.ok;
        } catch (e) { return false; }
    };

    icons.forEach((icon) => {
        try {
            if (icon.getAttribute && icon.getAttribute('aria-disabled') === 'true') {
                icon.setAttribute('data-was-aria-disabled', 'true');
                icon.removeAttribute('aria-disabled');
                try { icon.style.pointerEvents = 'auto'; } catch (e) {}
                try { icon.setAttribute('tabindex', '0'); } catch (e) {}
                try { icon.setAttribute('role', 'button'); } catch (e) {}
                icon.addEventListener('keydown', (ev) => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); icon.click(); } });
            }
        } catch (e) { /* ignore */ }

        icon.addEventListener('click', async (e) => {
            // Skip order logic for notification bell icon
            if (icon.querySelector('i.fi-br-bell')) {
                return; // Let it go to the notifications page
            }

            try {
                e.preventDefault();
                if (await serverHasOrder()) { window.location.href = '/order/summary'; return; }
                let raw = null; try { raw = window.sessionStorage.getItem(storageKey); } catch (err) { raw = null; }
                let summary = null; try { summary = raw ? JSON.parse(raw) : null; } catch (err) { summary = null; }
                if (summary && (summary.productId || summary.product_id)) {
                    const created = await createOrderFromSummary(summary);
                    if (created) { window.location.href = '/order/summary'; return; }
                }
                const href = icon.getAttribute('href');
                if (href && href !== '#') { window.location.href = href; return; }
                window.location.href = '/order/summary';
            } catch (err) { window.location.href = '/order/summary'; }
        });
    });
});
</script>


<div class="py-2 px-4">
        @yield('content')
    </div>

    <!-- Main Content -->
<main class="py-8 bg-white" style="min-height:60vh;">
    <div class="hero-wrapper relative overflow-hidden">
        <div class="layout-container grid md:grid-cols-2 gap-8 items-center relative z-10">
        
        <!-- Left Content -->
        <div class="space-y-6 animate-fade-in-left">
                <h1 class="hero-title">
                    <span class="hero-title-highlight">Invitation</span>
                    <span class="hero-title-accent">maker</span>
            </h1>

                <p class="hero-subtitle">
                Custom Invitations & Giveaways Crafted with Care.
            </p>

            <div class="flex space-x-4">
     <!-- Order Now -->
     <a href="{{ route('templates.wedding.invitations') }}"
         class="btn-pill btn-primary focus-ring">
       Order Now
    </a>

    <a href="#categories"  
    class="btn-pill btn-outline focus-ring">
   View Design
</a>
</div>

        </div>

        <!-- Right Content: Flip Card -->
        <div class="flip-card animate-fade-in-right">
            <div class="flip-card-inner">
                <!-- Front (Video) -->
                <div class="flip-card-front bg-white shadow-lg rounded-4x3 overflow-hidden flex items-center justify-center">
                    <video class="w-full h-64 md:h-96 object-cover rounded-2xl" autoplay loop muted>
                        <source src="{{ asset('customerVideo/Video/invitation.mp4') }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <!-- Back (Image) -->
                <div class="flip-card-back bg-white shadow-lg rounded-2xl overflow-hidden">
                    <img src="{{ asset('Customerimages/image/invitation.png') }}" alt="Invitation Design" class="w-full h-64 md:h-96 object-cover">
                </div>
            </div>
        </div>
        </div>
    </div>
</main>

{{-- Login modal --}}
@include('auth.customer.login')

{{-- Templates Section --}}
<div class="layout-stack">
    {{-- Templates Section --}}
    @include('customer.partials.templates')

    {{-- Categories Section --}}
    @include('customer.partials.categories')

    {{-- About Section --}}
    @include('customer.partials.about')

    {{-- Contact Section --}}
    @include('customer.partials.contact')
</div>

<!-- Chat bot AI assistance widget -->
@include('customer.partials.chatbot')
<!-- Optional: If you want to replace the placeholder bot replies with a real AI API call,
     replace the setTimeout block above with a fetch() to your server endpoint that proxies to OpenAI or another model. -->


<script>
    // Mobile nav & search toggle
    (function () {
        var btn = document.getElementById('mobileNavBtn');
        var nav = document.getElementById('mainNav');
        var searchBtn = document.getElementById('mobileSearchBtn');
        var searchPanel = document.getElementById('mobileSearch');

        function openNav() {
            nav.classList.remove('hidden');
            nav.classList.add('mobile-open');
            btn.setAttribute('aria-expanded', 'true');
        }

        function closeNav() {
            nav.classList.add('hidden');
            nav.classList.remove('mobile-open');
            btn.setAttribute('aria-expanded', 'false');
        }

        if (btn && nav) {
            btn.addEventListener('click', function () {
                if (nav.classList.contains('hidden')) {
                    openNav();
                } else {
                    closeNav();
                }
            });
        }

        if (searchBtn && searchPanel) {
            searchBtn.addEventListener('click', function () {
                if (searchPanel.classList.contains('hidden')) {
                    searchPanel.classList.remove('hidden');
                    searchPanel.classList.add('block');
                } else {
                    searchPanel.classList.remove('block');
                    searchPanel.classList.add('hidden');
                }
            });
        }

        // Close nav when resizing to desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                nav.classList.remove('hidden');
                nav.classList.remove('mobile-open');
                btn.setAttribute('aria-expanded', 'false');
                if (searchPanel) {
                    searchPanel.classList.add('hidden');
                }
            } else {
                nav.classList.add('hidden');
            }
        });
    })();

    // Add keyboard-visible focus ring for better accessibility
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Tab') {
            document.body.classList.add('user-is-tabbing');
        }
    });
    document.addEventListener('mousedown', function () {
        document.body.classList.remove('user-is-tabbing');
    });

    // Optional: close mobile nav when clicking a link (improves UX)
    (function () {
        var nav = document.getElementById('mainNav');
        if (!nav) return;
        nav.addEventListener('click', function (e) {
            var target = e.target.closest('a');
            if (!target) return;
            if (window.innerWidth < 768) {
                nav.classList.remove('block');
                nav.classList.add('hidden');
            }
        });
    })();

    // Show login modal if password was just reset or login requested
    @if(session('status') && str_contains(session('status'), 'Password reset successfully'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.classList.remove('hidden');
                loginModal.classList.add('flex');
            }
        });
    </script>
    @elseif(request('show_login'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.classList.remove('hidden');
                loginModal.classList.add('flex');
            }
        });
    </script>
    @endif
</script>

</body>
</html>

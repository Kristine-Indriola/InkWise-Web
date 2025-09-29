<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkwise Dashboard</title>


   <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
   </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

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
            width: 96px;
            height: 96px;
            border-radius: 9999px;
            padding: 6px; /* thickness of the gradient ring */
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
            border-radius: 9999px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .chat-inner img { width: 80%; height: 80%; object-fit: cover; border-radius: 9999px; }

        /* chat panel (enlarged for readability) */
        .chat-panel {
            width: 560px;
            max-width: calc(100vw - 3rem);
            position: absolute;
            right: 0;
            bottom: 108px;
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
    </style>
</head>
<body id="dashboard" class="antialiased bg-white">

    
   <!-- Top Navigation Bar -->
<header class="shadow animate-fade-in-down bg-white w-full">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center animate-bounce-slow flex-shrink-0">
            <span class="text-5xl font-bold logo-i" style="font-family: Edwardian Script ITC; color:#06b6d4;">I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: #0891b2;">nkwise</span>
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
                <input type="text" name="query" placeholder="Search..." 
                       class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-[#06b6d4]">
            </form>
  
   {{-- If not logged in --}}
@guest
    <a href="{{ route('customer.login') }}"
       id="openLogin"
       class="text-white px-5 py-2 font-semibold animate-ocean rounded-full"
       style="font-family: 'Seasons', serif;">
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
            <!-- My Purchase (no link) -->
            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] cursor-pointer transition-colors">
                My Purchase
            </div>
            <!-- My Favorites (no link) -->
            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] cursor-pointer transition-colors">
                My Favorites
            </div>
            <!-- Logout -->
            <form method="POST" action="{{ route('customer.logout') }}">
    @csrf
    <button type="submit" class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] transition-colors">Logout</button>
</form>

        </div>
    @endauth
</div>
</header>


<div class="py-2 px-4">
        @yield('content')
    </div>

    <!-- Main Content -->
<main class="py-8 bg-white" style="min-height:60vh;">
    <div class="hero-wrapper relative overflow-hidden">
        <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-2 gap-8 items-center relative z-10">
        
        <!-- Left Content -->
        <div class="space-y-6 animate-fade-in-left">
            <h1 class="text-5xl font-bold" style="font-family: 'Playfair Display', serif;">
               <span style="color: #06b6d4;">Invitation</span>
               <span style="color: #0891b2;">maker</span>
            </h1>

            <p class="text-lg text-gray-600" style="font-family: 'Seasons', serif;">
                Custom Invitations & Giveaways Crafted with Care.
            </p>

            <div class="flex space-x-4">
    <!-- Order Now -->
    <a href="{{ route('templates.wedding.invitations') }}"
       class="px-6 py-3 text-white font-semibold hover:scale-105 transition-transform rounded-full"
       style="background:#06b6d4; font-family: 'Playfair Display', serif;">
       Order Now
    </a>

    <a href="#categories"  
   class="px-6 py-3 font-semibold text-gray-800 bg-white hover:scale-105 transition-transform"
   style="border: 2px solid #06b6d4; border-radius: 65px; 
          background-clip: padding-box, border-box; background-origin: border-box; 
          background-image: linear-gradient(white, white),  
          linear-gradient(135deg, #06b6d4, #0891b2); 
          font-family: 'Playfair Display', serif;">
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
                    <img src="{{ asset('customerimages/image/invitation.png') }}" alt="Invitation Design" class="w-full h-64 md:h-96 object-cover">
                </div>
            </div>
        </div>
        </div>
    </div>
</main>

{{-- include modals --}}
{{-- Login and Register Modals --}}
@include('auth.customer.login')
@include('auth.customer.register')

{{-- Templates Section --}}
@include('customer.partials.templates')

{{-- Categories Section --}}
@include('customer.partials.categories')

{{-- About Section --}}
@include('customer.partials.about')

{{-- Contact Section --}}
@include('customer.partials.contact')

<!-- Chat bot AI assistance widget -->
<div class="chat-widget" x-data="{ open: false, messages: [{from:'bot', text:'Hi! I\'m InkWise Assistant. How can I help you today?'}], input: '' }" @keydown.window.escape="open=false">
    <!-- Toggle button -->
    <div class="chat-btn" @click="open = !open" aria-label="Open chat">
        <div class="chat-inner">
            <!-- Update filename if your image is different. Currently referencing Customerimages/bot.png -->
            <img src="{{ asset('Customerimages/bots.png') }}" alt="AI Bot">
        </div>
    </div>

    <!-- Chat panel -->
    <div x-show="open" x-cloak x-transition class="chat-panel" @click.away="open = false" aria-hidden="false" role="dialog" aria-label="InkWise Assistant">
        <div class="chat-header">
            <div style="width:40px;height:40px;border-radius:9999px;overflow:hidden;">
                <img src="{{ asset('Customerimages/bots.png') }}" alt="Bot" style="width:100%;height:100%;object-fit:cover;">
            </div>
            <div>
                <h4>InkWise Assistant</h4>
                <div style="font-size:12px;color:#55799a;">AI help for templates & orders</div>
            </div>
        </div>
        <div class="chat-body" x-ref="body">
            <template x-for="(m, idx) in messages" :key="idx">
                <div>
                    <div x-show="m.from === 'bot'" class="msg bot">
                        <div class="avatar"><img :src="'{{ asset('Customerimages/bots.png') }}'" alt="bot" style="width:100%;height:100%;object-fit:cover;"></div>
                        <div x-text="m.text"></div>
                    </div>
                    <div x-show="m.from === 'user'" class="msg user" x-text="m.text"></div>
                </div>
            </template>
        </div>
        <div class="chat-input">
            <input type="text" placeholder="Type a message..." x-model="input" @keydown.enter.prevent="
                if(input && input.trim() !== ''){
                    messages.push({from:'user', text: input});
                    const userMsg = input;
                    input = '';
                    $nextTick(()=>{ $refs.body.scrollTop = $refs.body.scrollHeight; });
                    // simulated bot reply (you can replace with API call)
                    setTimeout(()=>{
                        messages.push({from:'bot', text: 'Thanks! I received: ' + userMsg});
                        $nextTick(()=>{ $refs.body.scrollTop = $refs.body.scrollHeight; });
                    }, 800);
                }
            " />
            <button @click.prevent="
                if(input && input.trim() !== ''){
                    messages.push({from:'user', text: input});
                    const userMsg = input;
                    input = '';
                    $nextTick(()=>{ $refs.body.scrollTop = $refs.body.scrollHeight; });
                    setTimeout(()=>{
                        messages.push({from:'bot', text: 'Thanks! I received: ' + userMsg});
                        $nextTick(()=>{ $refs.body.scrollTop = $refs.body.scrollHeight; });
                    }, 800);
                }
            " class="bg-[#06b6d4] text-white px-4 py-2 rounded-full">Send</button>
        </div>
    </div>
</div>

<!-- Optional: If you want to replace the placeholder bot replies with a real AI API call,
     replace the setTimeout block above with a fetch() to your server endpoint that proxies to OpenAI or another model. -->


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
</script>

</body>
</html>

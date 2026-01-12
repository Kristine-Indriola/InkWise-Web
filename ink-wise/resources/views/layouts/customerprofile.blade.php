<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>customerprofile Dashboard • Inkwise</title>
  

  <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Seasons&display=swap');
        @import url('https://fonts.cdnfonts.com/css/edwardian-script-itc');
        
    </style>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

  <!-- Icon fonts used by invitations header -->
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-bold-rounded/css/uicons-bold-rounded.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customerprofile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customertemplate.css') }}">
    <script src="{{ asset('js/customer/template.js') }}" defer></script>
    <script src="{{ asset('js/customer/customerprofile.js') }}" defer></script>
    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('styles')
</head>

@push('styles')
<style>
    .notification-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
    }
</style>
@endpush
</head>

<body class="bg-gray-50 text-gray-800">
  <!-- Top Bar -->
  <!-- Top Navigation Bar -->
<header class="shadow animate-fade-in-down bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16 w-full">
        <!-- Logo aligned left -->
        <div class="flex items-center animate-bounce-slow flex-shrink-0">
            <span class="text-5xl font-bold logo-i" style="font-family: Edwardian Script ITC; color:#06b6d4;">I</span>
            <span class="text-2xl font-bold" style="font-family: 'Playfair Display', serif; color: #0891b2;">nkwise</span>
        </div>

        <!-- Navigation Links centered -->
        <nav class="hidden md:flex space-x-6 mx-auto">
            <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-[#06b6d4]">Home</a>
            <a href="#categories" class="text-gray-700 hover:text-[#06b6d4]">Categories</a>
            <a href="#templates" class="text-gray-700 hover:text-[#06b6d4]">Templates</a>
            <a href="#about" class="text-gray-700 hover:text-[#06b6d4]">About</a>
            <a href="#contact" class="text-gray-700 hover:text-[#06b6d4]">Contact</a>
        </nav>

        <!-- Sign In / User Dropdown aligned right -->
        <div class="flex items-center space-x-4 relative">
            <form action="{{ url('/search') }}" method="GET" class="hidden md:flex">
                <input type="text" name="query" placeholder="Search..." 
                       class="border rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring focus:ring-[#06b6d4]">
            </form>
            
      <!-- Favorites & Cart icons (copied from invitations topbar) -->
      <div class="hidden md:flex items-center gap-2">
        @php
          $hasFavoritesRoute = \Illuminate\Support\Facades\Route::has('customer.favorites');
          $favoritesLink = [
            'url' => $hasFavoritesRoute ? route('customer.favorites') : '#',
            'disabled' => !$hasFavoritesRoute,
            'label' => 'My favorites',
          ];

          $cartLink = [
            'url' => '/order/addtocart',
            'disabled' => false,
            'label' => 'My cart',
          ];
        @endphp

        <a href="{{ $favoritesLink['url'] }}"
           class="nav-icon-button"
           aria-label="{{ $favoritesLink['label'] }}"
           title="{{ $favoritesLink['label'] }}"
           @if($favoritesLink['disabled']) aria-disabled="true" @endif>
          <i class="fi fi-br-comment-heart" aria-hidden="true"></i>
        </a>
        <a href="{{ route('customer.notifications') }}"
           class="nav-icon-button"
           aria-label="Notifications"
           title="Notifications">
          <i class="fi fi-br-bell" aria-hidden="true"></i>
          @auth
            @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
            @if($unreadCount > 0)
              <span class="notification-badge">{{ $unreadCount }}</span>
            @endif
          @endauth
        </a>
        <a href="{{ $cartLink['url'] }}"
           class="nav-icon-button"
           aria-label="{{ $cartLink['label'] }}"
           title="{{ $cartLink['label'] }}"
           @if($cartLink['disabled']) aria-disabled="true" @endif>
          <i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
        </a>
      </div>
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
                <div class="relative group">
                    <button id="userDropdownBtn" class="flex items-center px-3 py-2 bg-[#e0f7fa] rounded hover:bg-[#06b6d4] hover:text-white">
                        {{ Auth::user()->customer?->first_name ?? Auth::user()->email }}
                    </button>
                    <div id="userDropdownMenu"
                         class="absolute right-0 mt-2 w-48 bg-white rounded shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200 z-50 hidden group-hover:block">
                        <a href="{{ route('customerprofile.index') }}"

                           class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">
                            My Account
                        </a>
                        <form id="customerLogoutForm" action="{{ route('customer.logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="block w-full text-left px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</header>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const storageKey = 'inkwise-finalstep';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
  const icons = Array.from(document.querySelectorAll('.nav-icon-button'));
  if (!icons.length) return;

  icons.forEach((icon) => {
    // If the server rendered this anchor as aria-disabled (pointer-events:none),
    // remove it so our JS can handle clicks. Store original state so we could restore if needed.
    try {
      if (icon.getAttribute && icon.getAttribute('aria-disabled') === 'true') {
        icon.setAttribute('data-was-aria-disabled', 'true');
        icon.removeAttribute('aria-disabled');
        // ensure it's clickable and keyboard accessible
        try { icon.style.pointerEvents = 'auto'; } catch (e) {}
        try { icon.setAttribute('tabindex', '0'); } catch (e) {}
        try { icon.setAttribute('role', 'button'); } catch (e) {}
        // support Enter key
        icon.addEventListener('keydown', (ev) => { if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); icon.click(); } });
      }
    } catch (e) {
      // ignore
    }
    icon.addEventListener('click', (e) => {
      try {
        e.preventDefault();
        const href = icon.getAttribute('href');
        if (href && href !== '#') {
          window.location.href = href;
          return;
        }
        // Default: navigate to order addtocart page (no POST)
        window.location.href = '/order/addtocart';
      } catch (err) {
        window.location.href = '/order/addtocart';
      }
    });
  });
});
</script>
      <!-- Welcome Section -->
<div class="welcome-section">
    <h1>Welcome to InkWise</h1>
    <p>Your Custom Invitations & Giveaways Hub</p>
</div>

  <!-- Layout -->
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 md:grid-cols-5 gap-6">
    <!-- Sidebar -->
    <aside class="sidebar rounded-2xl p-4 md:col-span-1 h-full">
      <nav class="space-y-2">
        <!-- My Account Dropdown -->
        @php
          $myAccountActive = request()->routeIs('customerprofile.*');
        @endphp
        <div x-data="{ open: {{ $myAccountActive ? 'true' : 'false' }} }" class="relative">
          <!-- Dropdown header with link + toggle -->
          <div class="flex items-stretch gap-2 group">
            <a href="{{ route('customerprofile.index') }}"
               class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition w-full font-medium {{ $myAccountActive ? 'bg-[#e0f7fa] text-gray-700' : 'text-gray-700 hover:bg-[#e0f7fa]' }}">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-5 0-9 2.5-9 5.5A1.5 1.5 0 0 0 4.5 21h15a1.5 1.5 0 0 0 1.5-1.5C21 16.5 17 14 12 14Z"/></svg>
              <span>My Account</span>
            </a>
            <button type="button"
                    @click="open = !open"
                    class="px-3 py-3 rounded-xl text-gray-500 hover:bg-[#e0f7fa] group-hover:bg-[#e0f7fa] transition">
              <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
            </button>
          </div>
          <div x-show="open" @click.away="open = false" class="mt-1 ml-6 space-y-1">
            <!-- Profile (with link) -->
            <a href="{{ route('customerprofile.index') }}"

               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
              Profile
            </a>
            <!-- Addresses (with link) -->
            <a href="{{ route('customerprofile.addresses') }}"
               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
              Addresses
            </a>
            <!-- Change Password (with link) -->
            <a href="{{ route('customerprofile.change-password') }}"
               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
              Change Password
            </a>
            <!-- My Favorites removed from dropdown (moved below) -->
          </div>
        </div>
        <!-- Other Sidebar Items -->
        @php
          $myPurchaseActive = request()->routeIs('customer.my_purchase*');
        @endphp
      <a href="{{ route('customer.my_purchase.completed') }}"
        class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition {{ $myPurchaseActive ? 'bg-[#e0f7fa] text-gray-700 font-medium' : 'text-gray-700 hover:bg-[#e0f7fa]' }}">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z"/></svg>
          <span class="font-medium">Purchase</span>
        </a>
        @php $favoritesActive = request()->routeIs('customer.favorites*'); @endphp
        <a href="{{ route('customer.favorites') }}"
          class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition {{ $favoritesActive ? 'bg-[#e0f7fa] text-gray-700 font-medium' : 'text-gray-700 hover:bg-[#e0f7fa]' }}">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6 4 4 6.5 4c1.74 0 3.41.81 4.5 2.09C12.09 4.81 13.76 4 15.5 4 18 4 20 6 20 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
          <span class="font-medium">Favorites</span>
        </a>
        @php $notificationsActive = request()->routeIs('customer.notifications*'); @endphp
        <a href="{{ route('customer.notifications') }}"
          class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition {{ $notificationsActive ? 'bg-[#e0f7fa] text-gray-700 font-medium' : 'text-gray-700 hover:bg-[#e0f7fa]' }}">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C10.896 2 10 2.896 10 4v4.586l-2.707 2.707c-.391.391-.391 1.023 0 1.414.195.195.451.293.707.293s.512-.098.707-.293L10.414 10H14V4c0-1.104-.896-2-2-2zM12 22c1.104 0 2-.896 2-2H10c0 1.104.896 2 2 2z"/></svg>
          <span class="font-medium">Notifications</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition relative group">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
    <path d="M5 4h14v2H5zm0 7h14v2H5zm0 7h9v2H5z"/>
  </svg>
  <span class="font-medium">Settings</span>
  <svg class="w-4 h-4 ml-auto transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
  <!-- Dropdown -->
  <div class="absolute left-0 top-full mt-1 w-48 bg-white rounded shadow-lg z-10 hidden group-hover:block">
    <a href="{{ route('customerprofile.settings', ['tab' => 'account']) }}"

       class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
      Account Settings
    </a>
    <a href="{{ route('customerprofile.settings', ['tab' => 'privacy']) }}"

       class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
      Privacy Settings
    </a>
  </div>
</a>

      </nav>
    </aside>
    <!-- Main Content Area -->
    <section class="md:col-span-4">
      @yield('content')
    </section>
  </main>

<!-- Floating Chat Button & Chat Modal -->
<div id="chatFloatingBtn"
     class="fixed bottom-6 right-6 z-50">
    <button id="openChatBtn" class="bg-[#94b9ff] hover:bg-[#6fa3ff] text-white rounded-full shadow-lg p-4 flex items-center justify-center transition duration-300"
            onclick="document.getElementById('chatModal').classList.remove('hidden'); document.getElementById('chatFloatingBtn').classList.add('hidden');">
        <!-- Chat Icon -->
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <!-- unread badge -->
        <span id="chatBadge" style="display:none; position: absolute; top: -4px; right: -2px; background:#ef4444; color:#fff; font-weight:700; font-size:12px; line-height:18px; padding:0 6px; border-radius:999px;"></span>
    </button>
</div>

<div id="chatModal" class="fixed bottom-6 right-6 z-50 hidden">
    <div id="chatBox"
         class="bg-white rounded-2xl shadow-2xl p-4 relative transition-all duration-300"
         style="width: 380px; height: 500px; display: flex; flex-direction: column;">
        <!-- Minimize button -->
        <button onclick="document.getElementById('chatModal').classList.add('hidden'); document.getElementById('chatFloatingBtn').classList.remove('hidden');"
                class="absolute top-2 right-2 text-gray-400 hover:text-[#94b9ff] transition text-base font-bold z-10" title="Minimize">
            &#8211;
        </button>
        <!-- Expand/Shrink button -->
        <button id="toggleChatSize"
                onclick="toggleChatSize()"
                class="absolute top-2 right-10 text-gray-400 hover:text-[#94b9ff] transition text-base font-bold z-10" title="Expand/Shrink">
            <svg id="expandIcon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 8V4h4M20 16v4h-4M4 16v4h4M20 8V4h-4"/>
            </svg>
        </button>
        <!-- Chat Header -->
        <div class="text-center mb-3 pt-2" style="flex-shrink: 0;">
            <h3 class="text-lg font-bold text-[#94b9ff]">Message Chat</h3>
            <p class="text-xs text-gray-500">Chat with staff for support</p>
        </div>
        <!-- Chat Messages -->
        <div id="chatPlaceholder" class="overflow-y-auto mb-3 flex flex-col gap-2" style="flex: 1; min-height: 0;">
            <!-- messages injected here -->
        </div>
        <!-- Image preview area -->
        <div id="imagePreviewContainer" class="mb-2 hidden" style="flex-shrink: 0;">
            <div class="relative inline-block">
                <img id="imagePreview" src="" alt="Preview" class="max-w-full max-h-24 rounded-lg border border-gray-300">
                <button type="button" onclick="clearImageSelection()"
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors duration-200"
                        title="Remove image">
                    ✕
                </button>
                <p id="imageFileName" class="text-xs text-gray-600 mt-1"></p>
            </div>
        </div>

        <!-- Chat Input + Image Upload -->
        <form id="customerChatForm" class="flex gap-2 items-end" enctype="multipart/form-data" onsubmit="return false;" style="flex-shrink: 0;">
            <input id="customerChatInput" type="text" placeholder="Type your message..."
                   class="flex-1 border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring focus:ring-[#94b9ff]">
            
            <!-- Hidden file input -->
            <input id="customerChatFile" type="file" accept="image/*" class="hidden">

            <!-- Button to open file picker -->
            <button type="button" onclick="document.getElementById('customerChatFile').click()"
                    class="bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-lg transition-colors duration-200 flex-shrink-0"
                    title="Attach an image">
                📷
            </button>

            <!-- Send button -->
            <button id="customerChatSendBtn" type="button"
                    class="bg-[#94b9ff] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#6fa3ff] transition-colors duration-200 flex-shrink-0">
                Send
            </button>
        </form>
    </div>
</div>

<script>
function toggleChatSize() {
    const chatBox = document.getElementById('chatBox');
    const expandIcon = document.getElementById('expandIcon');
    const isExpanded = chatBox.getAttribute('data-expanded') === 'true';
    
    if (isExpanded) {
        // Shrink to normal size
        chatBox.style.width = '380px';
        chatBox.style.height = '500px';
        chatBox.setAttribute('data-expanded', 'false');
        expandIcon.style.transform = 'rotate(0deg)';
    } else {
        // Expand to larger size
        chatBox.style.width = '500px';
        chatBox.style.height = '650px';
        chatBox.setAttribute('data-expanded', 'true');
        expandIcon.style.transform = 'rotate(180deg)';S
    }
}
</script>

<script>
// Function to clear image selection
function clearImageSelection() {
    const fileInput = document.getElementById('customerChatFile');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const preview = document.getElementById('imagePreview');
    const fileName = document.getElementById('imageFileName');
    
    fileInput.value = '';
    preview.src = '';
    fileName.textContent = '';
    previewContainer.classList.add('hidden');
}

(function () {
    const threadUrl = "{{ route('customer.chat.thread') }}";
    const sendUrl   = "{{ route('customer.chat.send') }}";
    const placeholder = document.getElementById('chatPlaceholder');
    const input = document.getElementById('customerChatInput');
    const fileInput = document.getElementById('customerChatFile');
    const sendBtn = document.getElementById('customerChatSendBtn');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const preview = document.getElementById('imagePreview');
    const fileName = document.getElementById('imageFileName');

    function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

    // Handle file input change for preview
    fileInput.addEventListener('change', function(e) {
        console.log('File input changed, files:', this.files);
        const file = this.files[0];
        if (file) {
            console.log('File selected:', file.name, file.type, file.size);
            
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                this.value = '';
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size should not exceed 5MB.');
                this.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                fileName.textContent = file.name;
                previewContainer.classList.remove('hidden');
                console.log('Preview displayed');
            };
            reader.onerror = function(e) {
                console.error('FileReader error:', e);
                alert('Failed to read image file.');
            };
            reader.readAsDataURL(file);
        } else {
            console.log('No file selected, clearing preview');
            clearImageSelection();
        }
    });

    async function loadChatThread(){
      try {
        const res = await fetch(threadUrl, { headers:{ 'Accept':'application/json' }});
        if (!res.ok) return;
        const json = await res.json();
        renderThread(json.thread || []);
      } catch (e) {
        console.error(e);
      }
    }

    function renderThread(items){
      placeholder.innerHTML = '';
      items.forEach(it => {
        const isAdmin = (it.sender_type || '').toLowerCase() === 'user';
        const wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.justifyContent = isAdmin ? 'flex-start' : 'flex-end';
        wrapper.style.marginBottom = '8px';

        const bubble = document.createElement('div');
        bubble.style.maxWidth = '80%';
        bubble.style.padding = '10px 12px';
        bubble.style.borderRadius = '12px';
        bubble.style.background = isAdmin ? '#eef7ff' : '#94b9ff';
        bubble.style.color = isAdmin ? '#111' : '#fff';
        bubble.style.wordWrap = 'break-word';

        const contentParts = [];

        if (it.attachment_url) {
          const isImage = (it.attachment_mime || '').startsWith('image/');
          if (isImage) {
            contentParts.push(
              `<a href="${it.attachment_url}" target="_blank" rel="noopener">
                 <img src="${it.attachment_url}" alt="${escapeHtml(it.attachment_name || 'Attachment')}"
                      style="max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 6px; cursor: pointer; display: block;">
               </a>`
            );
          } else {
            contentParts.push(
              `<a href="${it.attachment_url}" target="_blank" rel="noopener"
                  style="display:inline-block;color:inherit;text-decoration:underline;margin-bottom:6px;">
                 ${escapeHtml(it.attachment_name || 'Download attachment')}
               </a>`
            );
          }
        }

        if (it.message && it.message.trim() && it.message.trim() !== '[image attachment]') {
          contentParts.push(`<p style="margin:0;white-space:pre-wrap;font-size:14px;line-height:1.4;">${escapeHtml(it.message)}</p>`);
        }

        if (!contentParts.length) {
          contentParts.push(`<p style="margin:0;white-space:pre-wrap;font-size:14px;line-height:1.4;">${escapeHtml(it.message || '')}</p>`);
        }

        bubble.innerHTML = contentParts.join('');
        wrapper.appendChild(bubble);
        placeholder.appendChild(wrapper);
      });
      
      // Smooth scroll to bottom
      setTimeout(() => {
        placeholder.scrollTop = placeholder.scrollHeight;
      }, 100);
    }

    async function sendMessage(){
      const msg = input.value.trim();
      const file = fileInput.files[0];
      if (!msg && !file) {
        alert('Please enter a message or select an image.');
        return;
      }

      // Disable send button to prevent double-clicking
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending...';

      try {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const fd = new FormData();
        if (msg) fd.append('message', msg);
        if (file) {
          console.log('Uploading file:', file.name, 'Size:', file.size, 'Type:', file.type);
          fd.append('file', file);
        }

        console.log('Sending to:', sendUrl);
        
        const res = await fetch(sendUrl, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: fd
        });
        
        console.log('Response status:', res.status);
        
        if (!res.ok) {
          const txt = await res.text().catch(()=>null);
          console.error('Send failed:', txt || res.status);
          alert('Send failed: ' + (txt || res.status));
          return;
        }
        
        const responseData = await res.json();
        console.log('Send successful:', responseData);
        
        // Clear inputs and preview
        input.value = '';
        clearImageSelection();
        
        // Reload chat thread
        await loadChatThread();
      } catch (error) {
        console.error('Send error:', error);
        alert('Failed to send message. Please try again.');
      } finally {
        // Re-enable send button
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send';
      }
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function(e){ 
      if (e.key === 'Enter' && !e.shiftKey) { 
        e.preventDefault(); 
        sendMessage(); 
      } 
    });

    // when modal opens, load messages
    window.loadChatThread = loadChatThread;

    // auto-refresh while modal open
    let pollInterval = null;
    const chatModal = document.getElementById('chatModal');
    const startPolling = () => {
      if (pollInterval) return;
      pollInterval = setInterval(()=>{ if (!chatModal.classList.contains('hidden')) loadChatThread(); }, 5000);
    };
    const stopPolling = () => { clearInterval(pollInterval); pollInterval = null; };
    const observer = new MutationObserver(()=> {
      if (!chatModal.classList.contains('hidden')) { loadChatThread(); startPolling(); } else { stopPolling(); }
    });
    observer.observe(chatModal, { attributes: true, attributeFilter: ['class'] });
})();
</script>

<script>
(function () {
    const unreadUrl = "{{ route('customer.chat.unread') }}";
    const markReadUrl = "{{ route('customer.chat.markread') }}";
    const badge = document.getElementById('chatBadge');
    const chatModal = document.getElementById('chatModal');

    async function fetchUnreadCount() {
        try {
            const res = await fetch(unreadUrl, { headers: { 'Accept': 'application/json' }});
            if (!res.ok) return;
            const json = await res.json();
            const count = parseInt(json.count || 0, 10);
            if (count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = count > 99 ? '99+' : count;
            } else {
                badge.style.display = 'none';
            }
        } catch (e) {
            console.error('unread count error', e);
        }
    }

    async function markMessagesRead() {
        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            await fetch(markReadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
            });
            badge.style.display = 'none';
            badge.textContent = '';
        } catch (e) {
            console.error('mark read error', e);
        }
    }

    fetchUnreadCount();
    const pollInterval = setInterval(fetchUnreadCount, 8000);
    const observer = new MutationObserver(() => {
        if (!chatModal.classList.contains('hidden')) {
            markMessagesRead();
        }
    });
    observer.observe(chatModal, { attributes: true, attributeFilter: ['class'] });
    window.addEventListener('beforeunload', () => clearInterval(pollInterval));
})();
</script>
  <script>
  (function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('chat') !== 'open') {
      return;
    }

    function triggerChat() {
      if (typeof window.openCustomerSupportChat === 'function') {
        window.openCustomerSupportChat();
        return;
      }
      const openBtn = document.getElementById('openChatBtn');
      if (openBtn) {
        openBtn.click();
        return;
      }
      const modal = document.getElementById('chatModal');
      if (modal) {
        modal.classList.remove('hidden');
        const floating = document.getElementById('chatFloatingBtn');
        if (floating) {
          floating.classList.add('hidden');
        }
      }
    }

    const kickoff = () => setTimeout(triggerChat, 200);
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', kickoff);
    } else {
      kickoff();
    }
  })();
  </script>
@stack('scripts')
</body>
</html>

<script>
// Accessible click/touch toggle for the user dropdown
document.addEventListener('DOMContentLoaded', function () {
  try {
    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');
    if (!btn || !menu) return;

    // Ensure ARIA attributes
    btn.setAttribute('aria-haspopup', 'true');
    btn.setAttribute('aria-expanded', 'false');

    function hideMenu() {
      menu.classList.add('hidden', 'opacity-0', 'pointer-events-none');
      menu.classList.remove('opacity-100', 'block', 'pointer-events-auto');
      btn.setAttribute('aria-expanded', 'false');
    }

    function showMenu() {
      menu.classList.remove('hidden', 'opacity-0', 'pointer-events-none');
      menu.classList.add('opacity-100', 'block', 'pointer-events-auto');
      btn.setAttribute('aria-expanded', 'true');
    }

    btn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      if (menu.classList.contains('hidden')) {
        showMenu();
      } else {
        hideMenu();
      }
    });

    // Close when clicking outside
    document.addEventListener('click', function (ev) {
      if (!menu.contains(ev.target) && !btn.contains(ev.target)) {
        hideMenu();
      }
    });

    // Close on Escape
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') {
        hideMenu();
      }
    });

    // Make sure keyboard focus toggles also hide when focus leaves
    menu.addEventListener('focusout', function (ev) {
      // if focus moves outside the menu and button, hide
      setTimeout(() => {
        const active = document.activeElement;
        if (!menu.contains(active) && active !== btn) {
          hideMenu();
        }
      }, 10);
    });
  } catch (e) {
    // don't break the page if script errors
    console.warn('user dropdown toggle failed', e);
  }
});
</script>

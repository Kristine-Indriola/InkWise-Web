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

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/customer/customer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customerprofile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/customer/customertemplates.css') }}">
    <script src="{{ asset('js/customer/customertemplate.js') }}" defer></script>
    <script src="{{ asset('js/customer/customerprofile.js') }}" defer></script>
    <!-- Alpine.js for interactivity -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.10.2/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('adminimage/ink.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                        <a href="{{ route('customer.profile.index') }}"

                           class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa]">
                            Profile
                        </a>
                        <form id="logout-form" action="{{ route('customer.logout') }}" method="POST">
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
        <div x-data="{ open: true }" class="relative">
          <!-- Dropdown is open by default (open: true) -->
          <button @click="open = !open"
                  class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition w-full text-left font-medium bg-[#e0f7fa]">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-5 0-9 2.5-9 5.5A1.5 1.5 0 0 0 4.5 21h15a1.5 1.5 0 0 0 1.5-1.5C21 16.5 17 14 12 14Z"/></svg>
            My Account
            <svg class="w-4 h-4 ml-auto transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
          </button>
          <div x-show="open" @click.away="open = false" class="mt-1 ml-6 space-y-1">
            <!-- Profile (with link) -->
            <a href="{{ route('customer.profile.index') }}"

               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
              Profile
            </a>
            <!-- Addresses (with link) -->
            <a href="{{ route('customer.profile.addresses') }}"
               class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
              Addresses
            </a>
            <!-- My Favorites (no link) -->
            <div class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition cursor-pointer">
              My Favorites
            </div>
          </div>
        </div>
        <!-- Other Sidebar Items -->
        <a href="{{ route('customer.my_purchase') }}" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 7h18v2H3zm0 4h18v2H3zm0 4h18v2H3z"/></svg>
          <span class="font-medium">My Purchase</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 3h10a2 2 0 0 1 2 2v14l-7-3-7 3V5a2 2 0 0 1 2-2z"/></svg>
          <span class="font-medium">Order History</span>
        </a>
        <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition relative group">
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
    <path d="M5 4h14v2H5zm0 7h14v2H5zm0 7h9v2H5z"/>
  </svg>
  <span class="font-medium">Settings</span>
  <svg class="w-4 h-4 ml-auto transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"></path></svg>
  <!-- Dropdown -->
  <div class="absolute left-0 top-full mt-1 w-48 bg-white rounded shadow-lg z-10 hidden group-hover:block">
    <a href="{{ route('customer.profile.settings', ['tab' => 'account']) }}"

       class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
      Account Settings
    </a>
    <a href="{{ route('customer.profile.settings', ['tab' => 'privacy']) }}"

       class="block px-4 py-2 text-gray-700 hover:bg-[#e0f7fa] rounded transition">
      Privacy Settings
    </a>
  </div>
</a>

        <form method="POST" action="{{ route('customer.logout')}}">
          @csrf
          <button type="submit" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition w-full text-left">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 13v-2H7V8l-5 4 5 4v-3zM20 3h-8a2 2 0 0 0-2 2v3h2V5h8v14h-8v-3h-2v3a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/></svg>
            <span class="font-medium">Log Out</span>
          </button>
        </form>
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
         class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-4 relative transition-all duration-300 resize"
         style="max-height: 400px; min-height: 300px; min-width: 320px; width: 100%;">
        <!-- Minimize button -->
        <button onclick="document.getElementById('chatModal').classList.add('hidden'); document.getElementById('chatFloatingBtn').classList.remove('hidden');"
                class="absolute top-2 right-2 text-gray-400 hover:text-[#94b9ff] transition text-base font-bold" title="Minimize">
            &#8211;
        </button>
        <!-- Expand/Shrink button -->
        <button id="toggleChatSize"
                onclick="toggleChatSize()"
                class="absolute top-2 right-10 text-gray-400 hover:text-[#94b9ff] transition text-base font-bold" title="Expand/Shrink">
            <svg id="expandIcon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 8V4h4M20 16v4h-4M4 16v4h4M20 8V4h-4"/>
            </svg>
        </button>
        <!-- Chat Header -->
        <div class="text-center mb-2">
            <h3 class="text-lg font-bold text-[#94b9ff]">Message Chat</h3>
            <p class="text-xs text-gray-500">Chat with staff for support</p>
        </div>
        <!-- Chat Messages (placeholder) -->
        <div id="chatPlaceholder" class="overflow-y-auto mb-3 flex flex-col" style="max-height: 320px; min-height: 220px;">
            <!-- messages injected here -->
        </div>
        <!-- Chat Input — add ids to elements -->
        <form id="customerChatForm" class="flex gap-2" onsubmit="return false;">
            <input id="customerChatInput" type="text" placeholder="Type your message..." class="flex-1 border rounded-lg px-4 py-3 text-base focus:outline-none focus:ring focus:ring-[#94b9ff]">
            <button id="customerChatSendBtn" type="button" class="bg-[#94b9ff] text-white px-4 py-2 rounded-lg text-base font-semibold hover:bg-[#6fa3ff]">Send</button>
        </form>
        <!-- Drag Handle for Resizing -->
        <div id="chatResizeHandle"
             class="absolute bottom-2 right-2 w-5 h-5 cursor-nwse-resize z-50"
             style="background: transparent;">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M20 20L4 4"/>
            </svg>
        </div>
    </div>
</div>

<script>
function toggleChatSize() {
    const chatBox = document.getElementById('chatBox');
    const chatPlaceholder = document.getElementById('chatPlaceholder');
    const expandIcon = document.getElementById('expandIcon');
    if (chatBox.classList.contains('max-w-sm')) {
        chatBox.classList.remove('max-w-sm');
        chatBox.classList.add('max-w-xl');
        chatBox.style.maxHeight = '700px';
        chatBox.style.minHeight = '400px';
        chatPlaceholder.style.maxHeight = '600px';
        chatPlaceholder.style.minHeight = '320px';
        expandIcon.style.transform = 'rotate(180deg)';
    } else {
        chatBox.classList.remove('max-w-xl');
        chatBox.classList.add('max-w-sm');
        chatBox.style.maxHeight = '400px';
        chatBox.style.minHeight = '300px';
        chatPlaceholder.style.maxHeight = '320px';
        chatPlaceholder.style.minHeight = '220px';
        expandIcon.style.transform = 'rotate(0deg)';
    }
}

// Mouse drag to resize chat
const chatBox = document.getElementById('chatBox');
const chatResizeHandle = document.getElementById('chatResizeHandle');
let isResizing = false, lastX = 0, lastY = 0, startWidth = 0, startHeight = 0;

chatResizeHandle.addEventListener('mousedown', function(e) {
    isResizing = true;
    lastX = e.clientX;
    lastY = e.clientY;
    startWidth = chatBox.offsetWidth;
    startHeight = chatBox.offsetHeight;
    document.body.style.userSelect = 'none';
});

window.addEventListener('mousemove', function(e) {
    if (!isResizing) return;
    let newWidth = Math.max(320, startWidth + (e.clientX - lastX));
    let newHeight = Math.max(300, startHeight + (e.clientY - lastY));
    chatBox.style.width = newWidth + 'px';
    chatBox.style.maxHeight = newHeight + 'px';
    chatBox.style.minHeight = Math.min(newHeight, 700) + 'px';
});

window.addEventListener('mouseup', function() {
    isResizing = false;
    document.body.style.userSelect = '';
});
</script>
<script>
(function () {
    const threadUrl = "{{ route('customer.chat.thread') }}";
    const sendUrl   = "{{ route('customer.chat.send') }}";
    const placeholder = document.getElementById('chatPlaceholder');
    const input = document.getElementById('customerChatInput');
    const sendBtn = document.getElementById('customerChatSendBtn');

    function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

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
        const bubble = document.createElement('div');
        bubble.style.maxWidth = '85%';
        bubble.style.padding = '8px 10px';
        bubble.style.borderRadius = '10px';
        bubble.style.background = isAdmin ? '#eef7ff' : '#94b9ff';
        bubble.style.color = isAdmin ? '#111' : '#fff';
        bubble.innerHTML = '<div style="font-size:12px;color:#666;margin-bottom:6px;"><strong>' + escapeHtml(it.name || (isAdmin ? 'Staff' : 'You')) + '</strong> <span style="font-size:11px;color:#999"> • ' + new Date(it.created_at).toLocaleString() + '</span></div>' + '<div style="white-space:pre-wrap;">' + escapeHtml(it.message) + '</div>';
        wrapper.appendChild(bubble);
        placeholder.appendChild(wrapper);
      });
      placeholder.scrollTop = placeholder.scrollHeight;
    }

    async function sendMessage(){
      const msg = input.value.trim();
      if (!msg) return;
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const fd = new FormData();
      fd.append('message', msg);
      const res = await fetch(sendUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: fd
      });
      if (!res.ok) {
        const txt = await res.text().catch(()=>null);
        alert('Send failed: ' + (txt || res.status));
        return;
      }
      input.value = '';
      await loadChatThread();
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); sendMessage(); } });

    // when modal opens (floating button onclick already removes 'hidden'), load conversation
    window.loadChatThread = loadChatThread;

    // optional: poll for new messages while modal open
    let pollInterval = null;
    const chatModal = document.getElementById('chatModal');
    const startPolling = () => {
      if (pollInterval) return;
      pollInterval = setInterval(()=>{ if (!chatModal.classList.contains('hidden')) loadChatThread(); }, 5000);
    };
    const stopPolling = () => { clearInterval(pollInterval); pollInterval = null; };
    // start/stop on show/hide
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
        
        // Immediately clear badge when read
        badge.style.display = 'none';
        badge.textContent = '';
    } catch (e) {
        console.error('mark read error', e);
    }
}


    // poll every 8 seconds
    fetchUnreadCount();
    const pollInterval = setInterval(fetchUnreadCount, 8000);

    // when modal opens, call mark-read (if available) and refresh
    const observer = new MutationObserver(() => {
        if (!chatModal.classList.contains('hidden')) {
            // chat opened
            markMessagesRead();
        }
    });
    observer.observe(chatModal, { attributes: true, attributeFilter: ['class'] });

    // clean up on page unload
    window.addEventListener('beforeunload', () => clearInterval(pollInterval));
})();
</script>
</body>
</html>

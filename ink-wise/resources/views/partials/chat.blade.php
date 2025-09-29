<div id="chatFloatingBtn" class="fixed bottom-6 right-6 z-50">
  <button id="openChatBtn" class="bg-[#94b9ff] text-white rounded-full p-3" title="Open chat">
    <!-- icon -->
    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <span id="chatBadge" style="display:none; position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; padding:0 6px; border-radius:999px; font-size:12px;"></span>
  </button>
</div>

<div id="chatModal" class="fixed bottom-6 right-6 z-50 hidden">
  <div class="bg-white rounded-lg shadow p-4 w-80 max-w-full" style="max-height:420px;">
    <div class="flex items-center justify-between mb-2">
      <strong>Support</strong>
      <button id="closeChatBtn" class="text-gray-500">—</button>
    </div>

    <div id="chatPlaceholder" class="overflow-auto mb-2" style="max-height:300px; min-height:120px;"></div>

    <div class="flex gap-2">
      <input id="customerChatInput" class="flex-1 border rounded px-2 py-1" placeholder="Type a message..." />
      <button id="customerChatSendBtn" class="bg-[#94b9ff] text-white rounded px-3">Send</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const threadUrl = "{{ route('customer.chat.thread') }}";
  const sendUrl = "{{ route('customer.chat.send') }}";
  const unreadUrl = "{{ route('customer.chat.unread') }}";
  const markReadUrl = "{{ route('customer.chat.markread') }}";

  const openBtn = document.getElementById('openChatBtn');
  const closeBtn = document.getElementById('closeChatBtn');
  const modal = document.getElementById('chatModal');
  const placeholder = document.getElementById('chatPlaceholder');
  const input = document.getElementById('customerChatInput');
  const sendBtn = document.getElementById('customerChatSendBtn');
  const badge = document.getElementById('chatBadge');

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

  async function fetchUnread() {
    try {
      const r = await fetch(unreadUrl, { headers:{ 'Accept':'application/json' }});
      if (!r.ok) return;
      const j = await r.json();
      const c = parseInt(j.count || 0, 10);
      if (c > 0) { badge.style.display='inline-block'; badge.textContent = c>99 ? '99+' : c; } else { badge.style.display='none'; }
    } catch(e){ console.error(e); }
  }

  async function loadChatThread() {
    try {
      const r = await fetch(threadUrl, { headers:{ 'Accept':'application/json' }});
      if (!r.ok) return;
      const j = await r.json();
      renderThread(j.thread || []);
    } catch(e){ console.error(e); }
  }

  function renderThread(items) {
    placeholder.innerHTML = '';
    items.forEach(it => {
      const isAdmin = (it.sender_type || '').toLowerCase() === 'user';
      const div = document.createElement('div');
      div.style.display = 'flex';
      div.style.justifyContent = isAdmin ? 'flex-start' : 'flex-end';
      const b = document.createElement('div');
      b.style.maxWidth = '85%';
      b.style.padding = '6px 8px';
      b.style.borderRadius = '10px';
      b.style.background = isAdmin ? '#f1f5f9' : '#94b9ff';
      b.style.color = isAdmin ? '#111' : '#fff';
      b.innerHTML = '<div style="font-size:11px;color:#666;margin-bottom:4px;"><strong>' + escapeHtml(it.name || (isAdmin?'Staff':'You')) + '</strong> <span style="font-size:10px;color:#999"> • ' + new Date(it.created_at).toLocaleString() + '</span></div>' + '<div style="white-space:pre-wrap;">' + escapeHtml(it.message) + '</div>';
      div.appendChild(b);
      placeholder.appendChild(div);
    });
    placeholder.scrollTop = placeholder.scrollHeight;
  }

  async function sendMessage() {
    const msg = input.value.trim();
    if (!msg) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const fd = new FormData();
    fd.append('message', msg);
    const res = await fetch(sendUrl, { method:'POST', headers:{ 'X-CSRF-TOKEN': token,'Accept':'application/json' }, body: fd });
    if (!res.ok) { console.error('send failed', await res.text().catch(()=>null)); return; }
    input.value = '';
    await loadChatThread();
  }

  openBtn.addEventListener('click', function () { modal.classList.remove('hidden'); openBtn.style.display='none'; loadChatThread().then(()=> markRead()); });
  closeBtn && closeBtn.addEventListener('click', function () { modal.classList.add('hidden'); openBtn.style.display='inline-block'; });
  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); sendMessage(); } });

  async function markRead() {
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      await fetch(markReadUrl, { method:'POST', headers:{ 'X-CSRF-TOKEN': token, 'Accept':'application/json' } });
      badge.style.display='none';
    } catch(e){ console.error(e); }
  }

  // initial polling for unread badge
  fetchUnread();
  setInterval(fetchUnread, 8000);
});
</script>
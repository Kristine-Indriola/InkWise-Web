@extends('layouts.admin')

@section('title', 'Messages')

@section('content')
<div class="stock">
    <h3>Customer Messages</h3>

    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#6a2ebc; color:white; text-align:left;">
                <th style="padding:10px;">From</th>
                <th style="padding:10px;">Email</th>
                <th style="padding:10px;">Message</th>
                <th style="padding:10px;">Received</th>
                <th style="padding:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($messages as $message)
            <tr style="border-bottom:1px solid #ddd;">
                <td style="padding:10px;">{{ $message->name ?? 'Guest' }}</td>
                <td style="padding:10px;">{{ $message->email ?? '-' }}</td>
                <td style="padding:10px; max-width:400px; white-space:normal;">{{ $message->message }}</td>
                <td style="padding:10px;">{{ $message->created_at->diffForHumans() }}</td>
                <td style="padding:10px;">
                    <button type="button"
                        class="open-thread-btn"
                        data-message-id="{{ $message->getKey() }}"
                        data-sender-type="{{ strtolower($message->sender_type ?? '') }}"
                        data-email="{{ $message->email ?? '' }}"
                        style="background:transparent; border:0; color:#1976d2; cursor:pointer;">
                        Reply / Open Chat
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="padding:10px;">No messages found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Thread Modal / Chatbox -->
<div id="replyModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; z-index:9999;">
    <div style="background:#fff; padding:0; border-radius:8px; width:95%; max-width:900px; position:relative; height:80vh; display:flex; flex-direction:column; overflow:hidden;">
        <div style="padding:12px 16px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong id="threadTitle">Conversation</strong>
                <div id="threadSub" style="font-size:12px; color:#666;"></div>
            </div>
            <button id="closeReplyModal" style="border:0; background:transparent; font-size:18px; cursor:pointer;">✖</button>
        </div>

        <div id="threadMessages" style="padding:16px; overflow-y:auto; flex:1; background:#f7f8fb;"></div>

        <form id="replyForm" style="padding:12px 16px; border-top:1px solid #eee; display:flex; gap:8px; align-items:flex-end;">
            @csrf
            <textarea id="replyMessage" name="message" rows="3" placeholder="Write a reply..." required style="flex:1; padding:10px; border:1px solid #ddd; border-radius:6px;"></textarea>
            <button type="submit" style="padding:10px 14px; background:#1976d2; color:#fff; border:0; border-radius:6px;">Send</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('admin messages script loaded');

    const threadUrlTemplate = "{{ route('admin.messages.thread', ['message' => '__ID__']) }}";
    const replyUrlTemplate  = "{{ route('admin.messages.reply', ['message' => '__ID__']) }}";
    const modal             = document.getElementById('replyModal');
    const threadMessages    = document.getElementById('threadMessages');
    const threadTitle       = document.getElementById('threadTitle');
    const threadSub         = document.getElementById('threadSub');
    const closeBtn          = document.getElementById('closeReplyModal');
    const replyForm         = document.getElementById('replyForm');
    const replyMessage      = document.getElementById('replyMessage');

    let currentMessageId = null;

    function getCsrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    async function fetchThread(messageId) {
        const url = threadUrlTemplate.replace('__ID__', messageId);
        console.log('fetchThread', url);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            console.error('fetchThread failed', res.status);
            return [];
        }
        const json = await res.json().catch(()=>null);
        return (json && json.thread) ? json.thread : [];
    }

    function escapeHtml(text) {
        return (text || '').replace(/[&<>"']/g, function (m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }

    function renderThread(items) {
        threadMessages.innerHTML = '';
        items.forEach(it => {
            const isAdmin = (it.sender_type || '').toLowerCase() === 'user';
            const wrapper = document.createElement('div');
            wrapper.style.marginBottom = '12px';
            wrapper.style.display = 'flex';
            wrapper.style.justifyContent = isAdmin ? 'flex-end' : 'flex-start';

            const bubble = document.createElement('div');
            bubble.style.maxWidth = '75%';
            bubble.style.padding = '10px 12px';
            bubble.style.borderRadius = '10px';
            bubble.style.background = isAdmin ? '#1976d2' : '#fff';
            bubble.style.color = isAdmin ? '#fff' : '#222';
            bubble.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';

            const header = `<div style="font-size:13px; margin-bottom:6px; color:${isAdmin ? '#e6f0ff' : '#666'}">
                                <strong>${escapeHtml(it.name ?? (isAdmin ? 'Admin' : 'Guest'))}</strong>
                                <span style="font-size:11px; color:#999"> • ${new Date(it.created_at).toLocaleString()}</span>
                            </div>`;
            bubble.innerHTML = header + `<div style="white-space:pre-wrap;">${escapeHtml(it.message)}</div>`;
            wrapper.appendChild(bubble);
            threadMessages.appendChild(wrapper);
        });
        threadMessages.scrollTop = threadMessages.scrollHeight;
    }

    async function openThread(messageId, meta) {
        currentMessageId = messageId;
        threadTitle.textContent = meta.title || 'Conversation';
        threadSub.textContent = meta.sub || '';
        modal.style.display = 'flex';
        const items = await fetchThread(messageId);
        renderThread(items);
    }

    // delegated click => works even if DOM changes
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest && e.target.closest('.open-thread-btn');
        if (!btn) return;
        e.preventDefault();

        const messageId = btn.getAttribute('data-message-id');
        const senderType = (btn.getAttribute('data-sender-type') || '').toLowerCase();
        const email = btn.getAttribute('data-email') || '';
        const title = senderType === 'guest' ? `Guest: ${email}` : 'Conversation';

        if (!messageId) {
            console.warn('messageId missing on button', btn);
            return;
        }
        await openThread(messageId, { title, sub: senderType.toUpperCase() });
    });

    // close modal
    closeBtn && closeBtn.addEventListener('click', () => modal.style.display = 'none');

    // reply submit (AJAX)
    replyForm && replyForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!currentMessageId) return alert('Thread not loaded');

        const url = replyUrlTemplate.replace('__ID__', currentMessageId);
        const token = getCsrf();
        if (!token) console.warn('CSRF token missing in page meta');

        const body = new FormData();
        body.append('message', replyMessage.value);

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: body
        });

        if (!res.ok) {
          let text = await res.text();
          try {
            const json = JSON.parse(text);
            alert(json.error || json.message || 'Failed to send reply');
          } catch (e) {
            alert(text || 'Failed to send reply');
          }
          return;
        }

        replyMessage.value = '';
        const items = await fetchThread(currentMessageId);
        renderThread(items);
    });

    // keyboard escape to close
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal.style.display === 'flex') modal.style.display = 'none';
    });
});
</script>
@endsection

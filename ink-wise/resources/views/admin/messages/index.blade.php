 @extends('layouts.admin')

@section('title', 'Messages')

@section('content')
<div class="inbox-container" style="display:flex; height:80vh; border:1px solid #ddd; border-radius:8px; overflow:hidden;">

    <!-- Left Sidebar (Names Only) -->
<div class="inbox-sidebar" style="width:250px; border-right:1px solid #ddd; background:#f7f8fb; display:flex; flex-direction:column;">
    <div style="padding:12px; font-weight:bold; background:#6a2ebc; color:white;">Inbox</div>
    <div id="conversationList" style="flex:1; overflow-y:auto;">
        @php
            // group by customer email so each customer only shows once
            $customerThreads = $messages->where('sender_type', '!=', 'user')
                                        ->unique('email');
        @endphp

        @forelse($customerThreads as $message)
            <div class="conversation-item"
                data-message-id="{{ $message->getKey() }}"
                data-sender-type="{{ strtolower($message->sender_type ?? '') }}"
                data-email="{{ $message->email ?? '' }}"
                style="padding:12px; cursor:pointer; border-bottom:1px solid #eee;">
                <div style="font-weight:600; color:#333;">
                    {{ $message->name ?? 'Guest' }}
                </div>
               
            </div>
        @empty
            <div style="padding:12px; color:#999;">No conversations</div>
        @endforelse
    </div>
</div>


    <!-- Right Panel (Thread + Reply) -->
    <div class="inbox-thread" style="flex:1; display:flex; flex-direction:column;">
        <div id="threadHeader" style="padding:12px; border-bottom:1px solid #eee; font-weight:bold;">
            Select a conversation
        </div>

        <div id="threadMessages" style="flex:1; overflow-y:auto; padding:16px; background:#fafafa;">
            <!-- chat messages load here -->
        </div>

        <form id="replyForm" style="padding:12px; border-top:1px solid #eee; display:flex; gap:8px;">
            @csrf
            <textarea id="replyMessage" name="message" rows="2" placeholder="Write a reply..."
                style="flex:1; padding:10px; border:1px solid #ddd; border-radius:6px;" required></textarea>
            <button type="submit" style="padding:10px 14px; background:#1976d2; color:#fff; border:0; border-radius:6px;">
                Send
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const threadUrlTemplate = "{{ route('admin.messages.thread', ['message' => '__ID__']) }}";
    const replyUrlTemplate  = "{{ route('admin.messages.reply', ['message' => '__ID__']) }}";

    const threadMessages = document.getElementById('threadMessages');
    const threadHeader   = document.getElementById('threadHeader');
    const replyForm      = document.getElementById('replyForm');
    const replyMessage   = document.getElementById('replyMessage');
    let currentMessageId = null;
    let pollInterval = null;

    function getCsrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    async function fetchThread(messageId) {
        const url = threadUrlTemplate.replace('__ID__', messageId);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return [];
        const json = await res.json().catch(()=>null);
        return (json && json.thread) ? json.thread : [];
    }

    function escapeHtml(text) {
        return (text || '').replace(/[&<>"']/g, m => (
            {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]
        ));
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
            bubble.style.maxWidth = '70%';
            bubble.style.padding = '10px 12px';
            bubble.style.borderRadius = '10px';
            bubble.style.background = isAdmin ? '#1976d2' : '#fff';
            bubble.style.color = isAdmin ? '#fff' : '#222';
            bubble.style.boxShadow = '0 1px 3px rgba(0,0,0,0.06)';

            bubble.innerHTML = `<div style="font-size:12px; margin-bottom:4px; color:${isAdmin ? '#e6f0ff' : '#666'}">
                                    <strong>${escapeHtml(it.name ?? (isAdmin ? 'Admin' : 'Guest'))}</strong>
                                    <span style="font-size:11px; color:#aaa"> • ${new Date(it.created_at).toLocaleString()}</span>
                                </div>
                                <div style="white-space:pre-wrap;">${escapeHtml(it.message)}</div>`;
            wrapper.appendChild(bubble);
            threadMessages.appendChild(wrapper);
        });
        threadMessages.scrollTop = threadMessages.scrollHeight;
    }

    async function openThread(messageId, title) {
        currentMessageId = messageId;
        threadHeader.textContent = title;
        const items = await fetchThread(messageId);
        renderThread(items);

        clearInterval(pollInterval);
        pollInterval = setInterval(async () => {
            if (currentMessageId) {
                const items = await fetchThread(currentMessageId);
                renderThread(items);
            }
        }, 3000);
    }

    // Sidebar click -> open conversation
    document.addEventListener('click', e => {
        const item = e.target.closest('.conversation-item');
        if (!item) return;
        const messageId = item.getAttribute('data-message-id');
        const senderName = item.textContent.trim();
        openThread(messageId, senderName);
    });

    // Reply form
    replyForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (!currentMessageId) return alert('Select a conversation first');

        const url = replyUrlTemplate.replace('__ID__', currentMessageId);
        const token = getCsrf();
        const body = new FormData();
        body.append('message', replyMessage.value);

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body
        });

        if (res.ok) {
            replyMessage.value = '';
            const items = await fetchThread(currentMessageId);
            renderThread(items);
        } else {
            const txt = await res.text();
            alert(txt || 'Failed to send reply');
        }
    });
});
</script>
@endsection

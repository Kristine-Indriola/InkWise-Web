@extends($layout ?? 'layouts.staffapp')

@section('title', 'Messages')

@section('content')
@php
    $threadRouteName = $threadRouteName ?? 'staff.messages.thread';
    $replyRouteName = $replyRouteName ?? 'staff.messages.reply';
@endphp
<div class="inbox-container" 
    style="display:flex; height:85vh; border:1px solid #ddd; border-radius:12px; overflow:hidden; font-family:'Segoe UI', sans-serif; background:white;">

    <!-- Left Sidebar -->
    <div class="inbox-sidebar" 
        style="width:280px; border-right:1px solid #ddd; background:#fff; display:flex; flex-direction:column;">
        
        <div style="padding:16px; font-weight:600; font-size:18px; background:#6a2ebc; color:white;">
            Inbox
        </div>

        <div id="conversationList" style="flex:1; overflow-y:auto;">
            @php
                $customerThreads = $messages->where('sender_type', '!=', 'user')->unique('email');
            @endphp

            @forelse($customerThreads as $message)
                <div class="conversation-item"
                    data-message-id="{{ $message->getKey() }}"
                    data-sender-type="{{ strtolower($message->sender_type ?? '') }}"
                    data-email="{{ $message->email ?? '' }}"
                    style="padding:14px 16px; cursor:pointer; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:12px;">
                    
                    <div style="width:40px; height:40px; background:#6a2ebc; color:white; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold;">
                        {{ strtoupper(substr($message->name ?? 'G',0,1)) }}
                    </div>
                    <div>
                        <div style="font-weight:600; font-size:15px; color:#333;">
                            {{ $message->name ?? 'Guest' }}
                        </div>
                        <div style="font-size:12px; color:#777; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:160px;">
                            {{ $message->message ?? '' }}
                        </div>
                    </div>
                </div>
            @empty
                <div style="padding:14px; color:#999; text-align:center;">No conversations</div>
            @endforelse
        </div>
    </div>

    <!-- Right Panel -->
    <div class="inbox-thread" style="flex:1; display:flex; flex-direction:column;">
        
        <!-- Header -->
        <div id="threadHeader" 
            style="padding:14px 16px; border-bottom:1px solid #eee; font-weight:600; font-size:16px; background:#fafafa;">
            Select a conversation
        </div>

        <!-- Messages -->
        <div id="threadMessages" 
            style="flex:1; overflow-y:auto; padding:20px; background:#f5f6f8; display:flex; flex-direction:column; gap:12px;">
            <!-- messages load here -->
        </div>

        <!-- Reply Form -->
        <form id="replyForm" 
            style="padding:14px; border-top:1px solid #eee; display:flex; gap:10px; background:#fff;">
            @csrf
            <textarea id="replyMessage" name="message" rows="2" 
                placeholder="Type a message..."
                style="flex:1; resize:none; padding:10px 12px; border:1px solid #ddd; border-radius:20px; outline:none; font-size:14px;"></textarea>
            <button type="submit" 
                style="padding:0 18px; background:#6a2ebc; color:#fff; border:0; border-radius:20px; font-weight:500; cursor:pointer;">
                Send
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const threadUrlTemplate = "{{ route($threadRouteName, ['message' => '__ID__']) }}";
    const replyUrlTemplate  = "{{ route($replyRouteName, ['message' => '__ID__']) }}";

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
            {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[m]
        ));
    }

    function renderThread(items) {
        threadMessages.innerHTML = '';
        items.forEach(it => {
            const senderType = (it.sender_type || '').toLowerCase();
            const isInternal = ['user', 'staff', 'admin'].includes(senderType);
            const wrapper = document.createElement('div');
            wrapper.style.display = 'flex';
            wrapper.style.justifyContent = isInternal ? 'flex-end' : 'flex-start';

            const bubble = document.createElement('div');
            bubble.style.maxWidth = '65%';
            bubble.style.padding = '10px 14px';
            bubble.style.borderRadius = '18px';
            bubble.style.background = isInternal ? '#6a2ebc' : '#fff';
            bubble.style.color = isInternal ? '#fff' : '#222';
            bubble.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            bubble.style.fontSize = '14px';
            bubble.style.lineHeight = '1.4';

            bubble.innerHTML = `
                <div style="font-size:11px; margin-bottom:4px; color:${isInternal ? '#e0d5f9' : '#888'}">
                    ${escapeHtml(it.name ?? (isInternal ? 'Team' : 'Guest'))}
                    <span style="margin-left:6px; font-size:10px; color:#bbb;">${new Date(it.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                </div>
                <div style="white-space:pre-wrap;">${escapeHtml(it.message)}</div>
            `;

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

    document.addEventListener('click', e => {
        const item = e.target.closest('.conversation-item');
        if (!item) return;
        const messageId = item.getAttribute('data-message-id');
        const senderName = item.querySelector('div:nth-child(2) > div:first-child')?.textContent.trim() ?? 'Conversation';
        openThread(messageId, senderName);
    });

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

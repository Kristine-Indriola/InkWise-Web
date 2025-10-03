@extends($layout ?? 'layouts.admin')

@section('title', 'Messages')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-css/materials.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-css/messages.css') }}">
@endpush

@section('content')
@php
    $threadRouteName = $threadRouteName ?? 'admin.messages.thread';
    $replyRouteName = $replyRouteName ?? 'admin.messages.reply';
@endphp
<main class="admin-page-shell messages-page" role="main">
    <header class="page-header">
        <div>
            <h1 class="page-title">Inbox & Messages</h1>
            <p class="page-subtitle">Review customer conversations and follow up directly from the admin workspace.</p>
        </div>
    </header>

    <section class="messages-layout" aria-label="Messages">
        <aside class="messages-sidebar" aria-label="Conversation list">
            <div class="messages-sidebar__header">
                <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
                <span>Inbox</span>
            </div>

            <div id="conversationList" class="conversation-list">
                @php
                    $customerThreads = $messages->where('sender_type', '!=', 'user')->unique('email');
                @endphp

                @forelse($customerThreads as $message)
                    <article class="conversation-item"
                        data-message-id="{{ $message->getKey() }}"
                        data-sender-type="{{ strtolower($message->sender_type ?? '') }}"
                        data-email="{{ $message->email ?? '' }}"
                        role="button" tabindex="0" aria-pressed="false">
                        <div class="conversation-item__avatar" aria-hidden="true">
                            {{ strtoupper(substr($message->name ?? 'G',0,1)) }}
                        </div>
                        <div class="conversation-item__meta">
                            <p class="conversation-item__name">{{ $message->name ?? 'Guest' }}</p>
                            <p class="conversation-item__preview">{{ $message->message ?? '' }}</p>
                        </div>
                    </article>
                @empty
                    <p class="conversation-empty">No conversations yet.</p>
                @endforelse
            </div>
        </aside>

        <section class="messages-thread" aria-live="polite">
            <header id="threadHeader" class="messages-thread__header">
                Select a conversation
            </header>

            <div id="threadMessages" class="thread-messages" data-empty="Select a conversation to view its messages.">
                <!-- messages load here -->
            </div>

            <form id="replyForm" class="reply-form" novalidate>
                @csrf
                <label for="replyMessage" class="visually-hidden">Reply message</label>
                <textarea id="replyMessage" name="message" rows="2" class="reply-form__input" placeholder="Type a message..." disabled></textarea>
                <button type="submit" class="btn btn-primary reply-form__button" disabled>
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    <span>Send</span>
                </button>
            </form>
        </section>
    </section>
</main>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const threadUrlTemplate = "{{ route($threadRouteName, ['message' => '__ID__']) }}";
    const replyUrlTemplate  = "{{ route($replyRouteName, ['message' => '__ID__']) }}";

    const conversationList = document.getElementById('conversationList');
    const threadMessages = document.getElementById('threadMessages');
    const threadHeader   = document.getElementById('threadHeader');
    const replyForm      = document.getElementById('replyForm');
    const replyMessage   = document.getElementById('replyMessage');
    const replyButton    = replyForm.querySelector('button[type="submit"]');
    let currentMessageId = null;
    let pollInterval = null;
    let activeConversation = null;

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

        if (!items.length) {
            const emptyState = document.createElement('p');
            emptyState.classList.add('thread-empty');
            emptyState.textContent = threadMessages.dataset.empty;
            threadMessages.appendChild(emptyState);
            return;
        }

        items.forEach(it => {
            const senderType = (it.sender_type || '').toLowerCase();
            const isInternal = ['user', 'staff', 'admin'].includes(senderType);
            const wrapper = document.createElement('div');
            wrapper.classList.add('message-row');
            if (isInternal) wrapper.classList.add('message-row--internal');

            const bubble = document.createElement('article');
            bubble.classList.add('message-bubble');
            if (isInternal) bubble.classList.add('message-bubble--internal');

            const meta = document.createElement('div');
            meta.classList.add('message-meta');

            const nameEl = document.createElement('span');
            nameEl.classList.add('message-sender');
            nameEl.textContent = it.name ?? (isInternal ? 'Team' : 'Guest');

            const timeEl = document.createElement('time');
            const createdAt = new Date(it.created_at);
            timeEl.classList.add('message-time');
            if (!Number.isNaN(createdAt.getTime())) {
                timeEl.dateTime = createdAt.toISOString();
                timeEl.textContent = createdAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }

            meta.appendChild(nameEl);
            meta.appendChild(timeEl);

            const body = document.createElement('p');
            body.classList.add('message-text');
            body.textContent = it.message ?? '';

            bubble.appendChild(meta);
            bubble.appendChild(body);
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

        replyMessage.disabled = false;
        replyButton.disabled = false;

        clearInterval(pollInterval);
        pollInterval = setInterval(async () => {
            if (currentMessageId) {
                const items = await fetchThread(currentMessageId);
                renderThread(items);
            }
        }, 3000);
    }

    function setActiveConversation(item) {
        if (activeConversation) {
            activeConversation.classList.remove('is-active');
            activeConversation.setAttribute('aria-pressed', 'false');
        }
        activeConversation = item;
        if (activeConversation) {
            activeConversation.classList.add('is-active');
            activeConversation.setAttribute('aria-pressed', 'true');
        }
    }

    conversationList?.addEventListener('click', e => {
        const item = e.target.closest('.conversation-item');
        if (!item) return;
        const messageId = item.getAttribute('data-message-id');
        const senderName = item.querySelector('.conversation-item__name')?.textContent.trim() ?? 'Conversation';
        setActiveConversation(item);
        openThread(messageId, senderName);
    });

    conversationList?.addEventListener('keydown', e => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const item = e.target.closest('.conversation-item');
        if (!item) return;
        e.preventDefault();
        const messageId = item.getAttribute('data-message-id');
        const senderName = item.querySelector('.conversation-item__name')?.textContent.trim() ?? 'Conversation';
        setActiveConversation(item);
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

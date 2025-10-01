@extends($layout ?? 'layouts.admin')

@section('title', 'Messages')

@section('content')
@php
    $threadRouteName = $threadRouteName ?? 'admin.messages.thread';
    $replyRouteName = $replyRouteName ?? 'admin.messages.reply';
    $customerThreads = $messages->where('sender_type', '!=', 'user')->unique('email');
    $backUrl = url()->previous();
    $messagesIndexUrl = route('admin.messages.index');

    if (!$backUrl || \Illuminate\Support\Str::contains($backUrl, $messagesIndexUrl)) {
        $backUrl = route('admin.dashboard');
    }
@endphp

<style>
    :root {
        --messenger-bg: #f3f4f6;
        --messenger-border: #e2e8f0;
        --messenger-primary: #6a2ebc;
        --messenger-primary-soft: rgba(106, 46, 188, 0.12);
        --messenger-accent: #38bdf8;
        --messenger-text-dark: #1e293b;
        --messenger-text-mid: #475569;
        --messenger-text-light: #94a3b8;
    }

    .messenger-shell {
        display: grid;
        grid-template-columns: minmax(280px, 340px) 1fr;
        height: calc(100vh - 160px);
        min-height: 580px;
        border-radius: 18px;
        border: 1px solid var(--messenger-border);
        background: #fff;
        overflow: hidden;
        box-shadow: 0 35px 60px -30px rgba(15, 23, 42, 0.35);
        font-family: 'Segoe UI', sans-serif;
    }

    .messenger-sidebar {
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        border-right: 1px solid var(--messenger-border);
        min-height: 0;
    }

    .sidebar-header {
        padding: 20px 24px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .sidebar-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--messenger-primary);
    }

    .sidebar-meta {
        font-size: 12px;
        color: var(--messenger-text-light);
    }

    .sidebar-search {
        padding: 0 24px 16px;
    }

    .sidebar-search input {
        width: 100%;
        border-radius: 999px;
        border: 1px solid var(--messenger-border);
        padding: 10px 40px 10px 18px;
        font-size: 13px;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") right 14px center no-repeat;
        background-size: 18px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .sidebar-search input:focus {
        outline: none;
        border-color: rgba(106, 46, 188, 0.6);
        box-shadow: 0 0 0 3px rgba(106, 46, 188, 0.15);
    }

    .conversation-scroll {
        flex: 1;
        overflow-y: auto;
        padding: 8px 0 12px;
    }

    .conversation-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .conversation-scroll::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 999px;
    }

    .conversation-item {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 12px 24px;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
        border-right: 4px solid transparent;
    }

    .conversation-item:hover {
        background: var(--messenger-primary-soft);
    }

    .conversation-item.active {
        background: rgba(106, 46, 188, 0.12);
        border-right-color: var(--messenger-primary);
    }

    .conversation-avatar {
        position: relative;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.85), rgba(79,70,229,0.85));
        color: #fff;
        display: grid;
        place-items: center;
        font-weight: 700;
        font-size: 16px;
        text-transform: uppercase;
        box-shadow: 0 8px 16px -10px rgba(79, 70, 229, 0.6);
    }

    .conversation-meta {
        flex: 1;
        min-width: 0;
    }

    .conversation-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--messenger-text-dark);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .conversation-snippet {
        font-size: 12px;
        color: var(--messenger-text-mid);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .conversation-time {
        font-size: 11px;
        color: var(--messenger-text-light);
        white-space: nowrap;
    }

    .sidebar-empty {
        padding: 32px 24px;
        text-align: center;
        color: var(--messenger-text-light);
        font-size: 14px;
    }

    .messenger-thread {
        display: flex;
        flex-direction: column;
        background: var(--messenger-bg);
        min-height: 0;
    }

    .thread-header {
        padding: 18px 28px;
        border-bottom: 1px solid var(--messenger-border);
        background: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        min-height: 80px;
    }

    .thread-header.thread-header--empty {
        justify-content: center;
        color: var(--messenger-text-light);
        font-weight: 500;
    }

    .thread-status {
        font-size: 12px;
        color: var(--messenger-text-light);
        letter-spacing: 0.4px;
    }

    .thread-participant {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .thread-participant .avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--messenger-primary);
        color: #fff;
        display: grid;
        place-items: center;
        font-weight: 700;
        font-size: 18px;
    }

    .thread-participant .details {
        line-height: 1.4;
    }

    .thread-participant .details strong {
        font-size: 16px;
        color: var(--messenger-text-dark);
    }

    .thread-participant .details span {
        display: block;
        font-size: 12px;
        color: var(--messenger-text-light);
    }

    .thread-body {
        flex: 1;
        overflow-y: auto;
        padding: 28px 36px 32px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        position: relative;
        background: radial-gradient(circle at top left, rgba(106, 46, 188, 0.08), var(--messenger-bg));
        min-height: 0;
    }

    .thread-body::-webkit-scrollbar {
        width: 10px;
    }

    .thread-body::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, 0.18);
        border-radius: 999px;
    }

    .thread-empty {
        margin: auto;
        text-align: center;
        color: var(--messenger-text-light);
        font-size: 15px;
    }

    .message-divider {
        align-self: center;
        padding: 6px 14px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.15);
        color: var(--messenger-text-light);
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0.4px;
    }

    .message-row {
        display: flex;
    }

    .message-row.is-internal {
        justify-content: flex-end;
    }

    .message-bubble {
        max-width: min(70%, 520px);
        padding: 12px 16px 14px;
        border-radius: 20px;
        box-shadow: 0 8px 18px -12px rgba(15, 23, 42, 0.4);
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 6px;
        background: #fff;
        color: var(--messenger-text-dark);
    }

    .message-row.is-internal .message-bubble {
        background: linear-gradient(135deg, var(--messenger-primary), #9f67ff);
        color: #fff;
    }

    .message-author {
        font-size: 12px;
        font-weight: 600;
        color: inherit;
        opacity: 0.85;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .message-text {
        font-size: 14px;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .message-time {
        align-self: flex-end;
        font-size: 11px;
        color: inherit;
        opacity: 0.7;
        letter-spacing: 0.3px;
    }

    .thread-compose {
        padding: 18px 28px;
        border-top: 1px solid var(--messenger-border);
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: flex-end;
        gap: 14px;
    }

    .thread-compose textarea {
        flex: 1;
        resize: none;
        min-height: 48px;
        max-height: 120px;
        border-radius: 18px;
        border: 1px solid var(--messenger-border);
        padding: 14px 16px;
        font-size: 14px;
        line-height: 1.5;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        background: #fff;
    }

    .thread-compose textarea:focus {
        outline: none;
        border-color: rgba(106, 46, 188, 0.6);
        box-shadow: 0 0 0 3px rgba(106, 46, 188, 0.15);
    }

    .thread-compose button {
        border-radius: 16px;
        border: none;
        background: linear-gradient(135deg, var(--messenger-primary), #a855f7);
        color: #fff;
        font-weight: 600;
        padding: 12px 22px;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .thread-compose button:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 12px 25px -18px rgba(106, 46, 188, 0.8);
    }

    .thread-compose button:disabled {
        cursor: not-allowed;
        opacity: 0.55;
        box-shadow: none;
    }

    @media (max-width: 1024px) {
        .messenger-shell {
            grid-template-columns: 320px 1fr;
            height: calc(100vh - 120px);
        }
    }

    @media (max-width: 900px) {
        .messenger-shell {
            grid-template-columns: 1fr;
        }

        .messenger-sidebar {
            border-right: none;
            border-bottom: 1px solid var(--messenger-border);
        }

        .thread-header {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    }
</style>

<div class="messenger-shell">
    <aside class="messenger-sidebar">
        <div class="sidebar-header">
            <div>
                <div class="sidebar-title">Inbox</div>
                <div class="sidebar-meta">Stay in sync with your customer conversations</div>
            </div>
        </div>

        <div class="sidebar-search">
            <input type="text" id="conversationSearch" placeholder="Search by name or email">
        </div>

        <div id="conversationList" class="conversation-scroll">
            @forelse($customerThreads as $message)
             <div class="conversation-item"
                 data-message-id="{{ $message->getKey() }}"
                 data-sender-type="{{ strtolower($message->sender_type ?? '') }}"
                 data-email="{{ $message->email ?? '' }}"
                 data-name="{{ $message->name ?? 'Guest' }}"
                 data-last-message-at="{{ optional($message->created_at)->toIso8601String() }}">
                    <div class="conversation-avatar">
                        {{ strtoupper(substr($message->name ?? ($message->email ?? 'G'), 0, 1)) }}
                    </div>
                    <div class="conversation-meta">
                        <div class="conversation-name">
                            {{ $message->name ?? 'Guest' }}
                        </div>
                        <div class="conversation-snippet">
                            {{ \Illuminate\Support\Str::limit($message->message ?? 'No message preview available yet.', 80) }}
                        </div>
                    </div>
                    <div class="conversation-time">
                        {{ optional($message->created_at)->timezone(config('app.timezone'))->format('M d, Y h:i A') ?? '' }}
                    </div>
                </div>
            @empty
                <div class="sidebar-empty">No conversations yet. Messages will appear here once customers reach out.</div>
            @endforelse
        </div>
        <div id="conversationEmptyState" class="sidebar-empty" style="display:none;">
            No conversations match your search.
        </div>
    </aside>

    <section class="messenger-thread">
        <header id="threadHeader" class="thread-header thread-header--empty">
            Select a conversation to start messaging
        </header>

        <div id="threadMessages" class="thread-body">
            <div class="thread-empty">
                Conversations you open will appear here.
            </div>
        </div>

        <form id="replyForm" class="thread-compose">
            @csrf
            <textarea id="replyMessage" name="message" rows="2" placeholder="Type a reply and press enter to send"></textarea>
            <button type="submit" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13" />
                    <polygon points="22 2 15 22 11 13 2 9 22 2" />
                </svg>
                Send
            </button>
        </form>
    </section>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('messages-view');

    const threadUrlTemplate = "{{ route($threadRouteName, ['message' => '__ID__']) }}";
    const replyUrlTemplate  = "{{ route($replyRouteName, ['message' => '__ID__']) }}";
    const unreadCountUrl    = "{{ route('admin.messages.unread-count') }}";

    const threadMessages = document.getElementById('threadMessages');
    const threadHeader   = document.getElementById('threadHeader');
    const replyForm      = document.getElementById('replyForm');
    const replyMessage   = document.getElementById('replyMessage');
    const conversationList = document.getElementById('conversationList');
    const conversationSearch = document.getElementById('conversationSearch');
    const conversationEmptyState = document.getElementById('conversationEmptyState');
    const sendButton = replyForm.querySelector('button[type="submit"]');
    const inboxToggleLink = document.querySelector('[data-messages-toggle="true"]');
    const backUrl = @json($backUrl);
    let currentMessageId = null;
    let pollInterval = null;
    let unreadPollInterval = null;

    function setUnreadCount(value) {
        if (!inboxToggleLink) {
            return;
        }

        const count = Number.isFinite(value) ? Math.max(0, Math.trunc(value)) : 0;
        let badge = inboxToggleLink.querySelector('[data-role="messages-unread-count"]');

        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notif-badge';
                badge.dataset.role = 'messages-unread-count';
                inboxToggleLink.appendChild(badge);
            }
            badge.textContent = String(count);
        } else if (badge) {
            badge.remove();
        }

        inboxToggleLink.dataset.initialUnread = String(count);
    }

    async function refreshUnreadCount() {
        if (!unreadCountUrl) {
            return;
        }

        try {
            const res = await fetch(unreadCountUrl, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                return;
            }

            const json = await res.json().catch(() => null);
            if (json && typeof json.count === 'number') {
                setUnreadCount(json.count);
            }
        } catch (error) {
            // no-op: network hiccups should not break the inbox
        }
    }

    function startUnreadPolling() {
        if (unreadPollInterval) {
            clearInterval(unreadPollInterval);
        }

        unreadPollInterval = setInterval(refreshUnreadCount, 15000);
    }

    if (inboxToggleLink) {
        const initialUnread = Number(inboxToggleLink.dataset.initialUnread || 0);
        setUnreadCount(initialUnread);
        inboxToggleLink.setAttribute('aria-label', 'Close inbox and return');

        inboxToggleLink.addEventListener('click', event => {
            event.preventDefault();

            if (backUrl) {
                window.location.href = backUrl;
                return;
            }

            if (window.history.length > 1) {
                window.history.back();
                return;
            }

            window.location.href = inboxToggleLink.href;
        });

        refreshUnreadCount();
        startUnreadPolling();
    }

    function getCsrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function escapeHtml(text) {
        return (text || '').replace(/[&<>"']/g, m => (
            {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[m]
        ));
    }

    const TIMEZONE = 'Asia/Manila';
    const TIME_FORMATTER = new Intl.DateTimeFormat('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: TIMEZONE,
    });

    const DATE_LABEL_FORMATTER = new Intl.DateTimeFormat('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        timeZone: TIMEZONE,
    });

    const TOOLTIP_FORMATTER = new Intl.DateTimeFormat('en-PH', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: TIMEZONE,
    });

    function toDate(value) {
        if (!value) return null;
        const d = new Date(value);
        return Number.isNaN(d.getTime()) ? null : d;
    }

    function formatClock(value) {
        const d = toDate(value);
        if (!d) return '';
        return TIME_FORMATTER.format(d);
    }

    function formatDateLabel(value) {
        const d = toDate(value);
        if (!d) return '';
        return DATE_LABEL_FORMATTER.format(d);
    }

    const relativeTimeFormatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

    function formatRelativeTime(value) {
        const date = toDate(value);
        if (!date) return '';

        const diffMs = date.getTime() - Date.now();
        const diffMinutes = Math.round(diffMs / 60000);

        if (Math.abs(diffMinutes) < 1) {
            return 'Just now';
        }

        const diffHours = Math.round(diffMinutes / 60);
        const diffDays = Math.round(diffHours / 24);
        const diffWeeks = Math.round(diffDays / 7);
        const diffMonths = Math.round(diffDays / 30);
        const diffYears = Math.round(diffDays / 365);

        if (Math.abs(diffMinutes) < 60) {
            return relativeTimeFormatter.format(diffMinutes, 'minute');
        }
        if (Math.abs(diffHours) < 24) {
            return relativeTimeFormatter.format(diffHours, 'hour');
        }
        if (Math.abs(diffDays) < 7) {
            return relativeTimeFormatter.format(diffDays, 'day');
        }
        if (Math.abs(diffWeeks) < 5) {
            return relativeTimeFormatter.format(diffWeeks, 'week');
        }
        if (Math.abs(diffMonths) < 12) {
            return relativeTimeFormatter.format(diffMonths, 'month');
        }
        return relativeTimeFormatter.format(diffYears, 'year');
    }

    function updateConversationTime(item, isoString) {
        const timeEl = item?.querySelector('.conversation-time');
        if (!timeEl) return;

        if (!isoString) {
            timeEl.textContent = '';
            timeEl.removeAttribute('title');
            return;
        }

        const date = toDate(isoString);
        if (!date) {
            timeEl.textContent = '';
            timeEl.removeAttribute('title');
            return;
        }

        item.dataset.lastMessageAt = date.toISOString();
        timeEl.textContent = formatRelativeTime(date);
    timeEl.title = TOOLTIP_FORMATTER.format(date);
    }

    function refreshConversationTimestamps() {
        document.querySelectorAll('.conversation-item').forEach(item => {
            updateConversationTime(item, item.dataset.lastMessageAt);
        });
    }

    async function fetchThread(messageId) {
        const url = threadUrlTemplate.replace('__ID__', messageId);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) {
            return { items: [], unread: null };
        }

        const json = await res.json().catch(() => null);
        const items = Array.isArray(json?.thread) ? json.thread : [];
        const unread = typeof json?.unread_count === 'number' ? json.unread_count : null;

        if (typeof unread === 'number') {
            setUnreadCount(unread);
        }

        return { items, unread };
    }

    function clearPoll() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    function setActiveConversation(element) {
        document.querySelectorAll('.conversation-item.active').forEach(item => {
            item.classList.remove('active');
        });
        if (element) {
            element.classList.add('active');
        }
    }

    function updateThreadHeader(meta = null) {
        if (!meta) {
            threadHeader.classList.add('thread-header--empty');
            threadHeader.innerHTML = 'Select a conversation to start messaging';
            return;
        }

        threadHeader.classList.remove('thread-header--empty');
        const initial = (meta.name || meta.email || 'G').trim().charAt(0).toUpperCase();
        threadHeader.innerHTML = `
            <div class="thread-participant">
                <div class="avatar">${escapeHtml(initial)}</div>
                <div class="details">
                    <strong>${escapeHtml(meta.name || 'Guest')}</strong>
                    <span>${escapeHtml(meta.email || 'No email provided')}</span>
                </div>
            </div>
            <div class="thread-status">${meta.status || ''}</div>
        `;
    }

    function renderThread(items) {
        threadMessages.innerHTML = '';

        if (!items.length) {
            threadMessages.appendChild(Object.assign(document.createElement('div'), {
                className: 'thread-empty',
                textContent: 'No messages yet. Start the conversation below.',
            }));
            return;
        }

        let lastDateKey = null;
        items.forEach(it => {
            const createdAt = it.created_at || it.createdAt;
            const dateObj = toDate(createdAt);
            const dateKey = dateObj ? dateObj.toDateString() : null;

            if (dateKey && dateKey !== lastDateKey) {
                lastDateKey = dateKey;
                const divider = document.createElement('div');
                divider.className = 'message-divider';
                divider.textContent = formatDateLabel(createdAt) || 'Recent';
                threadMessages.appendChild(divider);
            }

            const senderType = (it.sender_type || '').toLowerCase();
            const isInternal = ['user', 'staff', 'admin'].includes(senderType);

            const row = document.createElement('div');
            row.className = `message-row${isInternal ? ' is-internal' : ''}`;

            const bubble = document.createElement('div');
            bubble.className = 'message-bubble';
            bubble.innerHTML = `
                <div class="message-author">${escapeHtml(it.name ?? (isInternal ? 'Team' : 'Guest'))}</div>
                <div class="message-text">${escapeHtml(it.message)}</div>
                <div class="message-time">${formatClock(createdAt)}</div>
            `;

            row.appendChild(bubble);
            threadMessages.appendChild(row);
        });

        threadMessages.scrollTop = threadMessages.scrollHeight;
    }

    function setEmptyThread() {
        currentMessageId = null;
        updateThreadHeader();
        threadMessages.innerHTML = '';
        threadMessages.appendChild(Object.assign(document.createElement('div'), {
            className: 'thread-empty',
            textContent: 'Conversations you open will appear here.',
        }));
        replyMessage.value = '';
        sendButton.disabled = true;
        clearPoll();
    }

    async function openThread(messageId, meta) {
        if (!messageId) {
            setEmptyThread();
            return;
        }

        currentMessageId = messageId;
        updateThreadHeader(meta);
        sendButton.disabled = replyMessage.value.trim().length === 0;

        const { items } = await fetchThread(messageId);
        renderThread(items);

        const activeItem = document.querySelector(`.conversation-item[data-message-id="${messageId}"]`);
        if (activeItem && items.length) {
            const latest = items[items.length - 1];
            const latestIso = latest?.created_at || latest?.createdAt || null;
            if (latestIso) {
                updateConversationTime(activeItem, latestIso);
            }
        }

        replyMessage.focus();

        clearPoll();
        pollInterval = setInterval(async () => {
            if (currentMessageId) {
                const { items: refreshedItems } = await fetchThread(currentMessageId);
                renderThread(refreshedItems);
            }
        }, 3000);
    }

    function handleConversationClick(item) {
        if (!item) return;
        const messageId = item.getAttribute('data-message-id');
        const meta = {
            name: item.getAttribute('data-name'),
            email: item.getAttribute('data-email'),
        };
        setActiveConversation(item);
        openThread(messageId, meta);
    }

    conversationList?.addEventListener('click', e => {
        const item = e.target.closest('.conversation-item');
        if (!item) return;
        handleConversationClick(item);
    });

    if (conversationSearch) {
        conversationSearch.addEventListener('input', e => {
            const term = e.target.value.trim().toLowerCase();
            let visibleCount = 0;

            conversationList.querySelectorAll('.conversation-item').forEach(item => {
                const name = (item.getAttribute('data-name') || '').toLowerCase();
                const email = (item.getAttribute('data-email') || '').toLowerCase();
                const matches = !term || name.includes(term) || email.includes(term);
                item.style.display = matches ? '' : 'none';
                if (matches) visibleCount += 1;
            });

            if (conversationEmptyState) {
                conversationEmptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    }

    replyMessage.addEventListener('input', () => {
        const hasMessage = replyMessage.value.trim().length > 0;
        sendButton.disabled = !currentMessageId || !hasMessage;
    });

    replyForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (!currentMessageId) {
            alert('Select a conversation first');
            return;
        }

        const messageText = replyMessage.value.trim();
        if (!messageText) {
            replyMessage.focus();
            return;
        }

        const url = replyUrlTemplate.replace('__ID__', currentMessageId);
        const token = getCsrf();
        const body = new FormData();
        body.append('_token', token);
        body.append('message', messageText);

        sendButton.disabled = true;

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body
        });

        if (res.ok) {
            replyMessage.value = '';
            const { items } = await fetchThread(currentMessageId);
            renderThread(items);
        } else {
            const txt = await res.text();
            alert(txt || 'Failed to send reply');
        }

        sendButton.disabled = replyMessage.value.trim().length === 0;
    });

    // Auto-open the first conversation if available
    const firstConversation = conversationList?.querySelector('.conversation-item');
    if (firstConversation) {
        handleConversationClick(firstConversation);
    }

    refreshConversationTimestamps();
    setInterval(refreshConversationTimestamps, 60000);

    window.addEventListener('beforeunload', () => {
        clearPoll();
        if (unreadPollInterval) {
            clearInterval(unreadPollInterval);
            unreadPollInterval = null;
        }
    });
});
</script>
@endsection

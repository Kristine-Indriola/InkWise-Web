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

    $hasSeenAtColumn = \Illuminate\Support\Facades\Schema::hasColumn('messages', 'seen_at');
    $hasIsReadColumn = ! $hasSeenAtColumn && \Illuminate\Support\Facades\Schema::hasColumn('messages', 'is_read');
    $threadUnreadStates = [];

    if ($hasSeenAtColumn || $hasIsReadColumn) {
        foreach ($messages as $rawMessage) {
            $senderType = strtolower($rawMessage->sender_type ?? '');
            $receiverType = strtolower($rawMessage->receiver_type ?? '');
            $threadKey = null;

            if ($senderType === 'customer' || $receiverType === 'customer') {
                $customerId = $senderType === 'customer' ? $rawMessage->sender_id : $rawMessage->receiver_id;
                if ($customerId) {
                    $threadKey = 'customer:' . $customerId;
                }
            } else {
                $emailKey = strtolower($rawMessage->email ?? '');
                if ($emailKey) {
                    $threadKey = 'guest:' . $emailKey;
                }
            }

            if (! $threadKey) {
                continue;
            }

            $isIncoming = in_array($senderType, ['customer', 'guest'], true);
            $isUnread = false;

            if ($isIncoming) {
                if ($hasSeenAtColumn) {
                    $isUnread = is_null($rawMessage->seen_at);
                } else {
                    $isUnread = (int) ($rawMessage->is_read ?? 0) === 0;
                }
            }

            if ($isUnread) {
                $threadUnreadStates[$threadKey] = true;
            } elseif (! array_key_exists($threadKey, $threadUnreadStates)) {
                $threadUnreadStates[$threadKey] = false;
            }
        }
    }

    $customerAvatars = [];
    if (isset($customers) && $customers instanceof \Illuminate\Support\Collection) {
        foreach ($customers as $customerId => $customer) {
            $photo = $customer->photo ?? null;
            if (! empty($photo)) {
                $customerAvatars[$customerId] = \App\Support\ImageResolver::url($photo);
            }
        }
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

    .conversation-item.is-unread {
        background: linear-gradient(90deg, rgba(106, 46, 188, 0.12) 0%, rgba(56, 189, 248, 0.08) 100%);
        border-right-color: var(--messenger-accent);
        position: relative;
    }

    .conversation-item.is-unread:hover {
        background: linear-gradient(90deg, rgba(106, 46, 188, 0.16) 0%, rgba(56, 189, 248, 0.13) 100%);
    }

    .conversation-item.is-unread .conversation-name {
        color: var(--messenger-primary);
        font-weight: 700;
    }

    .conversation-item.is-unread .conversation-snippet {
        color: var(--messenger-text-dark);
        font-weight: 500;
    }

    .conversation-item.is-unread .conversation-time {
        color: var(--messenger-primary);
        font-weight: 600;
    }

    .conversation-side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
        min-width: 72px;
    }

    .conversation-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--messenger-accent);
        box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.18);
        opacity: 0;
        transform: scale(0.5);
        transition: opacity 0.18s ease, transform 0.18s ease;
    }

    .conversation-item.is-unread .conversation-status-dot {
        opacity: 1;
        transform: scale(1);
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
        overflow: hidden;
    }

    .conversation-avatar span {
        display: block;
    }

    .conversation-avatar--photo {
        background: #fff;
        box-shadow: 0 6px 14px -8px rgba(15, 23, 42, 0.35);
        border: 1px solid rgba(148, 163, 184, 0.35);
        color: transparent;
    }

    .conversation-avatar--photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
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
        overflow: hidden;
        box-shadow: 0 10px 22px -18px rgba(79, 70, 229, 0.75);
    }

    .thread-participant .avatar.avatar--photo {
        background: #fff;
        border: 1px solid rgba(148, 163, 184, 0.45);
        color: transparent;
    }

    .thread-participant .avatar.avatar--photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
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

    .message-attachment {
        margin-top: 6px;
        border-radius: 14px;
        overflow: hidden;
        background: rgba(148, 163, 184, 0.12);
        border: 1px solid rgba(148, 163, 184, 0.25);
    }

    .message-row.is-internal .message-attachment {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .message-attachment img {
        display: block;
        max-width: 100%;
        height: auto;
    }

    .message-attachment__meta {
        padding: 8px 12px;
        font-size: 12px;
        color: inherit;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
    }

    .message-attachment__meta span {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .message-attachment__meta a {
        color: inherit;
        text-decoration: underline;
        font-weight: 600;
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

    .thread-compose .compose-attach {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        border: 1px dashed rgba(106, 46, 188, 0.4);
        background: rgba(106, 46, 188, 0.1);
        color: var(--messenger-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease, border-color 0.2s ease;
    }

    .thread-compose .compose-attach svg {
        width: 22px;
        height: 22px;
        stroke: currentColor;
        fill: none;
        display: block;
    }

    .thread-compose .compose-attach svg path {
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
        fill: none;
    }

    .thread-compose .compose-attach:hover {
        background: rgba(106, 46, 188, 0.2);
        border-color: rgba(106, 46, 188, 0.6);
    }

    .thread-compose .compose-attach:focus-visible {
        outline: 3px solid rgba(106, 46, 188, 0.4);
        outline-offset: 2px;
    }

    .thread-compose .compose-field {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .thread-compose textarea {
        width: 100%;
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

    .attachment-preview {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(56, 189, 248, 0.14);
        color: var(--messenger-text-mid);
        font-size: 12px;
    }

    .attachment-preview.is-visible {
        display: flex;
    }

    .attachment-preview .attachment-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 600;
    }

    .attachment-clear {
        border: none;
        background: transparent;
        color: inherit;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .attachment-clear svg {
        width: 16px;
        height: 16px;
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
             @php
                 $previewText = $message->message ?? 'No message preview available yet.';
                 $attachmentName = $message->attachment_path ? basename($message->attachment_path) : null;
                 if (trim($previewText) === '[image attachment]' && $attachmentName) {
                     $previewText = '📎 ' . $attachmentName;
                 }
                 $senderType = strtolower((string) ($message->sender_type ?? ''));
                 $receiverType = strtolower((string) ($message->receiver_type ?? ''));
                 $threadKey = null;
                 $customerId = null;

                 if ($senderType === 'customer' && $message->sender_id) {
                     $customerId = $message->sender_id;
                     $threadKey = 'customer:' . $message->sender_id;
                 } elseif ($receiverType === 'customer' && $message->receiver_id) {
                     $customerId = $message->receiver_id;
                     $threadKey = 'customer:' . $message->receiver_id;
                 } else {
                     $emailKey = strtolower((string) ($message->email ?? ''));
                     if ($emailKey !== '') {
                         $threadKey = 'guest:' . $emailKey;
                     }
                 }

                 $isUnread = $threadKey ? ($threadUnreadStates[$threadKey] ?? false) : false;
                 $itemClasses = 'conversation-item' . ($isUnread ? ' is-unread' : '');
                 $avatarUrl = ($customerId !== null && isset($customerAvatars[$customerId])) ? $customerAvatars[$customerId] : null;

                 $customerName = null;
                 if ($customerId !== null && isset($customers[$customerId])) {
                     $customerModel = $customers[$customerId];
                     $nameParts = array_filter([
                         trim((string) ($customerModel->first_name ?? '')),
                         trim((string) ($customerModel->middle_name ?? '')),
                         trim((string) ($customerModel->last_name ?? '')),
                     ], fn ($part) => $part !== '');

                     if (! empty($nameParts)) {
                         $customerName = implode(' ', $nameParts);
                     }
                 }

                 $displayLabel = $customerName ?: ($message->name ?? ($message->email ?? 'Guest'));
                 $avatarInitial = strtoupper(substr($displayLabel, 0, 1));
             @endphp
             <div class="{{ $itemClasses }}"
                 data-message-id="{{ $message->getKey() }}"
                 data-sender-type="{{ strtolower($message->sender_type ?? '') }}"
                 data-email="{{ $message->email ?? '' }}"
                 data-name="{{ $displayLabel }}"
                 data-thread-key="{{ $threadKey ?? '' }}"
                 data-avatar-url="{{ $avatarUrl ?? '' }}"
                 data-unread="{{ $isUnread ? '1' : '0' }}"
                 data-last-message-at="{{ optional($message->created_at)->toIso8601String() }}">
                    <div class="conversation-avatar{{ $avatarUrl ? ' conversation-avatar--photo' : '' }}">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $displayLabel }} profile photo">
                        @else
                            <span aria-hidden="true">{{ $avatarInitial }}</span>
                        @endif
                    </div>
                    <div class="conversation-meta">
                        <div class="conversation-name">
                            <span>{{ $displayLabel }}</span>
                        </div>
                        <div class="conversation-snippet">
                            {{ \Illuminate\Support\Str::limit($previewText, 80) }}
                        </div>
                    </div>
                    <div class="conversation-side">
                        <div class="conversation-time">
                            {{ optional($message->created_at)->timezone(config('app.timezone'))->format('M d, Y h:i A') ?? '' }}
                        </div>
                        <span class="conversation-status-dot" role="status" aria-label="Unread conversation"></span>
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

        <form id="replyForm" class="thread-compose" enctype="multipart/form-data">
            @csrf
            <input type="file" id="replyAttachment" name="attachment" accept="image/*" hidden>
            <label for="replyAttachment" class="compose-attach" id="attachmentButton" role="button" tabindex="0" aria-label="Attach image">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" role="img">
                    <path d="M21.44 11.05l-9.19 9.19a5.5 5.5 0 0 1-7.78-7.78l9.19-9.19a3.5 3.5 0 0 1 5 5L8.12 19.31"></path>
                </svg>
            </label>
            <div class="compose-field">
                <textarea id="replyMessage" name="message" rows="2" placeholder="Type a reply and press enter to send"></textarea>
                <div class="attachment-preview" id="attachmentPreview" aria-live="polite">
                    <span class="attachment-name" id="attachmentName"></span>
                    <button type="button" class="attachment-clear" id="attachmentRemove" aria-label="Remove attachment">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 8.586l4.95-4.95a1 1 0 1 1 1.414 1.414L11.414 10l4.95 4.95a1 1 0 0 1-1.414 1.414L10 11.414l-4.95 4.95a1 1 0 0 1-1.414-1.414L8.586 10l-4.95-4.95A1 1 0 1 1 5.05 3.636L10 8.586z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
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
    const replyAttachment = document.getElementById('replyAttachment');
    const attachmentButton = document.getElementById('attachmentButton');
    const attachmentPreview = document.getElementById('attachmentPreview');
    const attachmentNameEl = document.getElementById('attachmentName');
    const attachmentRemove = document.getElementById('attachmentRemove');
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

    function hasSelectedAttachment() {
        return !!(replyAttachment && replyAttachment.files && replyAttachment.files.length > 0);
    }

    function updateAttachmentPreview() {
        if (!attachmentPreview || !replyAttachment) {
            return;
        }

        const file = replyAttachment.files && replyAttachment.files[0];
        if (file) {
            if (attachmentNameEl) {
                attachmentNameEl.textContent = file.name;
            }
            attachmentPreview.classList.add('is-visible');
        } else {
            attachmentPreview.classList.remove('is-visible');
            if (attachmentNameEl) {
                attachmentNameEl.textContent = '';
            }
        }
    }

    function clearAttachmentPreview() {
        if (replyAttachment) {
            replyAttachment.value = '';
        }
        if (attachmentPreview) {
            attachmentPreview.classList.remove('is-visible');
        }
        if (attachmentNameEl) {
            attachmentNameEl.textContent = '';
        }
    }

    function updateSendButtonState() {
        const hasMessage = replyMessage.value.trim().length > 0;
        const hasFile = hasSelectedAttachment();
        sendButton.disabled = !currentMessageId || (!hasMessage && !hasFile);
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
        const avatarUrl = (meta.avatarUrl || '').trim();
        const safeAvatar = escapeHtml(avatarUrl);
        const avatarHtml = avatarUrl
            ? `<div class="avatar avatar--photo"><img src="${safeAvatar}" alt="${escapeHtml(meta.name || 'Guest')} profile photo"></div>`
            : `<div class="avatar">${escapeHtml(initial)}</div>`;

        threadHeader.innerHTML = `
            <div class="thread-participant">
                ${avatarHtml}
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
            const displayName = escapeHtml(it.name ?? (isInternal ? 'Team' : 'Guest'));
            const messageText = (it.message ?? '').toString();
            const trimmedMessage = messageText.trim();
            const hasAttachment = Boolean(it.attachment_url);
            const attachmentMime = (it.attachment_mime || '').toString().toLowerCase();
            const isImageAttachment = hasAttachment && attachmentMime.startsWith('image/');
            const attachmentUrl = hasAttachment ? escapeHtml(it.attachment_url) : '';
            const attachmentName = hasAttachment ? escapeHtml(it.attachment_name || 'Attachment') : '';
            const shouldRenderText = trimmedMessage !== '' && !(hasAttachment && trimmedMessage === '[image attachment]');

            let bubbleHtml = `<div class="message-author">${displayName}</div>`;

            if (shouldRenderText) {
                bubbleHtml += `<div class="message-text">${escapeHtml(messageText)}</div>`;
            }

            if (hasAttachment) {
                bubbleHtml += '<div class="message-attachment">';
                if (isImageAttachment) {
                    bubbleHtml += `<a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer"><img src="${attachmentUrl}" alt="${attachmentName}"></a>`;
                }
                bubbleHtml += `<div class="message-attachment__meta"><span>${attachmentName}</span><a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer">View</a></div>`;
                bubbleHtml += '</div>';
            }

            bubbleHtml += `<div class="message-time">${formatClock(createdAt)}</div>`;
            bubble.innerHTML = bubbleHtml;

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
        clearAttachmentPreview();
        updateSendButtonState();
        clearPoll();
    }

    async function openThread(messageId, meta) {
        if (!messageId) {
            setEmptyThread();
            return;
        }

        currentMessageId = messageId;
        updateThreadHeader(meta);
        updateSendButtonState();

        const activeItem = document.querySelector(`.conversation-item[data-message-id="${messageId}"]`);
        if (activeItem) {
            activeItem.classList.remove('is-unread');
            activeItem.dataset.unread = '0';
        }

        const { items } = await fetchThread(messageId);
        renderThread(items);

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
            avatarUrl: item.getAttribute('data-avatar-url') || '',
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

    replyMessage.addEventListener('input', updateSendButtonState);

    if (attachmentButton && replyAttachment) {
        attachmentButton.addEventListener('click', event => {
            event.preventDefault();
            replyAttachment.click();
        });

        attachmentButton.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                replyAttachment.click();
            }
        });
    }

    if (replyAttachment) {
        replyAttachment.addEventListener('change', () => {
            updateAttachmentPreview();
            updateSendButtonState();
        });
    }

    if (attachmentRemove) {
        attachmentRemove.addEventListener('click', () => {
            clearAttachmentPreview();
            updateSendButtonState();
            replyMessage.focus();
        });
    }

    replyForm.addEventListener('submit', async e => {
        e.preventDefault();
        if (!currentMessageId) {
            alert('Select a conversation first');
            return;
        }

        const messageText = replyMessage.value.trim();
        const hasFile = hasSelectedAttachment();

        if (!messageText && !hasFile) {
            replyMessage.focus();
            return;
        }

        const url = replyUrlTemplate.replace('__ID__', currentMessageId);
        const token = getCsrf();
        const body = new FormData();
        body.append('_token', token);
        body.append('message', messageText);
        if (hasFile && replyAttachment && replyAttachment.files[0]) {
            body.append('attachment', replyAttachment.files[0]);
        }

        sendButton.disabled = true;

        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body
        });

        if (res.ok) {
            replyMessage.value = '';
            clearAttachmentPreview();
            const { items } = await fetchThread(currentMessageId);
            renderThread(items);
        } else {
            let message = 'Failed to send reply';
            try {
                const data = await res.clone().json();
                if (data && typeof data.error === 'string') {
                    message = data.error;
                } else if (data && typeof data.message === 'string') {
                    message = data.message;
                } else if (data && data.errors) {
                    const first = Object.values(data.errors)[0];
                    if (Array.isArray(first) && first.length) {
                        message = first[0];
                    }
                }
            } catch (jsonError) {
                const txt = await res.text();
                if (txt) {
                    message = txt;
                }
            }
            alert(message);
        }

        updateSendButtonState();
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

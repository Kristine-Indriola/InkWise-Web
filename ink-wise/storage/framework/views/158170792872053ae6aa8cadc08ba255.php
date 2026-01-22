<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@flaticon/flaticon-uicons/css/all/all.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-css/materials.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/staff-css/messages.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('title', 'Messages'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $threadRouteName = $threadRouteName ?? 'staff.messages.thread';
    $replyRouteName = $replyRouteName ?? 'staff.messages.reply';
    $customerThreads = $messages->where('sender_type', '!=', 'user')->unique('email');
    $backUrl = url()->previous();
    $messagesIndexUrl = route('staff.messages.index');

    if (! $backUrl || \Illuminate\Support\Str::contains($backUrl, $messagesIndexUrl)) {
        $backUrl = route('staff.dashboard');
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

    $unreadCountRoute = \Illuminate\Support\Facades\Route::has('staff.messages.unread-count')
        ? route('staff.messages.unread-count')
        : null;
?>



<main class="materials-page admin-page-shell staff-messages-page" role="main">
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
            <?php $__empty_1 = true; $__currentLoopData = $customerThreads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
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
                ?>
                <div class="<?php echo e($itemClasses); ?>"
                    data-message-id="<?php echo e($message->getKey()); ?>"
                    data-sender-type="<?php echo e(strtolower($message->sender_type ?? '')); ?>"
                    data-email="<?php echo e($message->email ?? ''); ?>"
                    data-name="<?php echo e($displayLabel); ?>"
                    data-thread-key="<?php echo e($threadKey ?? ''); ?>"
                    data-avatar-url="<?php echo e($avatarUrl ?? ''); ?>"
                    data-unread="<?php echo e($isUnread ? '1' : '0'); ?>"
                    data-last-message-at="<?php echo e(optional($message->created_at)->toIso8601String()); ?>">
                    <div class="conversation-avatar<?php echo e($avatarUrl ? ' conversation-avatar--photo' : ''); ?>">
                        <?php if($avatarUrl): ?>
                            <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($displayLabel); ?> profile photo">
                        <?php else: ?>
                            <span aria-hidden="true"><?php echo e($avatarInitial); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="conversation-meta">
                        <div class="conversation-name">
                            <span><?php echo e($displayLabel); ?></span>
                        </div>
                        <div class="conversation-snippet">
                            <?php echo e(\Illuminate\Support\Str::limit($previewText, 80)); ?>

                        </div>
                    </div>
                    <div class="conversation-side">
                        <div class="conversation-time">
                            <?php echo e(optional($message->created_at)->timezone(config('app.timezone'))->format('M d, Y h:i A') ?? ''); ?>

                        </div>
                        <span class="conversation-status-dot" role="status" aria-label="Unread conversation"></span>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="sidebar-empty">No conversations yet. Messages will appear here once customers reach out.</div>
            <?php endif; ?>
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
            <?php echo csrf_field(); ?>
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
        </main>
<?php $__env->stopSection(); ?>

        <?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('messages-view');

    const threadUrlTemplate = "<?php echo e(route($threadRouteName, ['message' => '__ID__'])); ?>";
    const replyUrlTemplate  = "<?php echo e(route($replyRouteName, ['message' => '__ID__'])); ?>";
    const unreadCountUrl    = <?php echo json_encode($unreadCountRoute, 15, 512) ?>;

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
    const backUrl = <?php echo json_encode($backUrl, 15, 512) ?>;
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make($layout ?? 'layouts.staffapp', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\leanne\xampp\htdocs\InkWise-Web\ink-wise\resources\views/staff/messages/index.blade.php ENDPATH**/ ?>
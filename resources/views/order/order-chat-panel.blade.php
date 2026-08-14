@php
    $orderChatSupportId = $orderChatSupport->id ?? null;
@endphp

@if((Auth::user()->user_type === 'admin' || Auth::user()->hasRole(['admin', 'demo_admin'])) && !empty($data->client_id))
<div class="col-12 mb-3 pds-order-block">
    <div class="card pds-page-card pds-order-chat-card">
        <div class="card-body pds-messenger-chat">
            <div class="pds-messenger-header">
                <div class="pds-messenger-user">
                    <img src="{{ getSingleMedia($data->client, 'profile_image', null) ?: asset('images/default.png') }}"
                         alt="{{ optional($data->client)->name }}"
                         class="pds-messenger-avatar">
                    <div>
                        <h4 class="pds-messenger-name mb-0">{{ optional($data->client)->name ?? '-' }}</h4>
                        <span class="pds-messenger-status">{{ __('message.chat_with_customer') }}</span>
                    </div>
                </div>
            </div>

            <div id="order-chat-panel"
                 class="pds-messenger-panel"
                 data-order-id="{{ $data->id }}"
                 data-support-id="{{ $orderChatSupportId }}">
                <div id="order-chat-body" class="pds-messenger-body">
                    <div class="pds-messenger-empty order-chat-empty">
                        <i class="far fa-comments"></i>
                        <p>{{ __('message.writeAMessage') ?? 'Write a message to start chat...' }}</p>
                    </div>
                </div>

                <div id="orderChatImagePreview" class="pds-messenger-image-preview d-none">
                    <button type="button" class="pds-messenger-image-remove" id="orderChatImageRemove" aria-label="Remove image">
                        <i class="fas fa-times"></i>
                    </button>
                    <img id="orderChatImageThumb" src="" alt="preview">
                    <span id="orderChatImageName" class="pds-messenger-image-name"></span>
                </div>

                <form id="orderChatForm" class="pds-messenger-composer" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="support_id" id="orderChatSupportId" value="{{ $orderChatSupportId }}">
                    <input type="file" name="chat_image" id="orderChatImage" accept="image/*" class="d-none">

                    <div class="pds-messenger-composer-inner">
                        <div class="dropdown pds-messenger-emoji-wrap">
                            <button type="button"
                                    class="pds-messenger-icon-btn"
                                    id="orderChatEmojiBtn"
                                    data-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    title="Emoji">
                                <i class="far fa-smile"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-left pds-messenger-emoji-menu">
                                <emoji-picker data-target-input="#orderChatMessage"></emoji-picker>
                            </div>
                        </div>

                        <button type="button" class="pds-messenger-icon-btn" id="orderChatImageBtn" title="Image">
                            <i class="far fa-image"></i>
                        </button>

                        <textarea name="message"
                                  id="orderChatMessage"
                                  class="pds-messenger-input"
                                  rows="1"
                                  placeholder="{{ __('message.enter_name', ['name' => 'message...']) }}"></textarea>

                        <button type="submit" class="pds-messenger-send-btn" id="orderChatSendBtn" title="Send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@section('bottom_script')
@parent
<script>
(function () {
    const panel = document.getElementById('order-chat-panel');
    if (!panel) return;

    let supportId = panel.dataset.supportId || '';
    const chatBody = document.getElementById('order-chat-body');
    const chatForm = document.getElementById('orderChatForm');
    const messageInput = document.getElementById('orderChatMessage');
    const imageInput = document.getElementById('orderChatImage');
    const imagePreview = document.getElementById('orderChatImagePreview');
    const imageThumb = document.getElementById('orderChatImageThumb');
    const imageName = document.getElementById('orderChatImageName');
    const imageRemove = document.getElementById('orderChatImageRemove');
    const supportIdInput = document.getElementById('orderChatSupportId');
    const ensureChatUrl = "{{ route('order.ensure-chat', $data->id) }}";
    const chatStoreUrl = "{{ route('supportchathistory.store') }}";
    const defaultAvatar = "{{ asset('images/default.png') }}";
    let lastFingerprint = '';
    let pollTimer = null;
    let imageObjectUrl = null;
    let isPolling = false;

    function chatMessagesUrl() {
        return supportId ? "{{ url('customersupport') }}/" + supportId + "/chat-messages" : null;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function isSentMessage(item) {
        return item.current_user_class === 'mm-current-user';
    }

    function messagesFingerprint(messages) {
        return messages.map(function (item) {
            return [item.id, item.message || '', item.chat_image || '', item.time_label || ''].join(':');
        }).join('|');
    }

    function renderMessageContent(item) {
        if (item.chat_image) {
            return `<a href="${item.chat_image}" target="_blank" rel="noopener" class="pds-messenger-image-link">
                <img src="${item.chat_image}" alt="image" class="pds-messenger-msg-image" loading="lazy">
            </a>`;
        }
        const text = escapeHtml(item.message || '').replace(/\n/g, '<br>');
        return `<div class="pds-messenger-bubble-text">${text}</div>`;
    }

    function buildMessageHtml(item, showMeta) {
        const sent = isSentMessage(item);
        const isMedia = !!item.chat_image;

        return `
            <div class="pds-messenger-msg ${sent ? 'pds-messenger-msg--sent' : 'pds-messenger-msg--received'} ${showMeta ? 'pds-messenger-msg--group-start' : 'pds-messenger-msg--grouped'} ${isMedia ? 'pds-messenger-msg--media' : ''}"
                 data-message-id="${item.id}">
                ${!sent && showMeta ? `<img src="${item.profile_image || defaultAvatar}" alt="" class="pds-messenger-msg-avatar" loading="lazy">` : ''}
                ${!sent && !showMeta ? '<span class="pds-messenger-msg-avatar-spacer"></span>' : ''}
                <div class="pds-messenger-msg-content">
                    ${showMeta ? `<div class="pds-messenger-msg-meta">${escapeHtml(item.user_name || '')} · ${escapeHtml(item.time_label || '')}</div>` : ''}
                    <div class="pds-messenger-bubble ${isMedia ? 'pds-messenger-bubble--media' : ''}">
                        ${renderMessageContent(item)}
                    </div>
                    ${!showMeta ? `<span class="pds-messenger-msg-time">${escapeHtml(item.time_label || '')}</span>` : ''}
                </div>
            </div>`;
    }

    function renderMessages(messages, force) {
        if (!chatBody || !Array.isArray(messages)) return;

        const fingerprint = messagesFingerprint(messages);
        if (!force && fingerprint === lastFingerprint) {
            return;
        }
        lastFingerprint = fingerprint;

        if (!messages.length) {
            chatBody.innerHTML = `<div class="pds-messenger-empty order-chat-empty">
                <i class="far fa-comments"></i>
                <p>{{ __('message.writeAMessage') ?? 'Write a message to start chat...' }}</p>
            </div>`;
            return;
        }

        const wasAtBottom = chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight < 48;
        let html = '';
        let prevSender = null;

        messages.forEach(function (item) {
            const sent = isSentMessage(item);
            const senderKey = sent ? 'me' : 'other';
            const showMeta = senderKey !== prevSender;
            prevSender = senderKey;
            html += buildMessageHtml(item, showMeta);
        });

        chatBody.innerHTML = html;

        if (wasAtBottom) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }

    function refreshChatMessages(force) {
        const url = chatMessagesUrl();
        if (!url || isPolling) return;

        isPolling = true;
        $.get(url, function (res) {
            if (res.status && res.messages) {
                renderMessages(res.messages, !!force);
            }
        }).always(function () {
            isPolling = false;
        });
    }

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(function () {
            if (document.hidden) return;
            refreshChatMessages(false);
        }, 5000);
    }

    function ensureChat(callback) {
        if (supportId) {
            callback();
            return;
        }
        $.post(ensureChatUrl, {_token: '{{ csrf_token() }}'}, function (res) {
            if (res.status && res.support_id) {
                supportId = String(res.support_id);
                panel.dataset.supportId = supportId;
                supportIdInput.value = supportId;
                callback();
            }
        });
    }

    function clearImagePreview() {
        if (imageObjectUrl) {
            URL.revokeObjectURL(imageObjectUrl);
            imageObjectUrl = null;
        }
        imageInput.value = '';
        imageThumb.src = '';
        imageName.textContent = '';
        imagePreview.classList.add('d-none');
    }

    function autoResizeTextarea() {
        messageInput.style.height = 'auto';
        messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
    }

    document.getElementById('orderChatImageBtn')?.addEventListener('click', function () {
        imageInput.click();
    });

    imageInput?.addEventListener('change', function () {
        if (imageObjectUrl) {
            URL.revokeObjectURL(imageObjectUrl);
            imageObjectUrl = null;
        }
        if (!imageInput.files.length) {
            imageThumb.src = '';
            imageName.textContent = '';
            imagePreview.classList.add('d-none');
            return;
        }

        const file = imageInput.files[0];
        imageObjectUrl = URL.createObjectURL(file);
        imageThumb.src = imageObjectUrl;
        imageName.textContent = file.name;
        imagePreview.classList.remove('d-none');
        messageInput.focus();
    });

    imageRemove?.addEventListener('click', function () {
        clearImagePreview();
    });

    messageInput?.addEventListener('input', autoResizeTextarea);

    messageInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            chatForm.requestSubmit();
        }
    });

    // Emoji picker (same as global chat in app.js)
    $(document).on('emoji-click', 'emoji-picker[data-target-input="#orderChatMessage"]', function (e) {
        const input = document.querySelector(e.target.dataset.targetInput);
        if (!input) return;
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? input.value.length;
        const emoji = e.detail.unicode || '';
        input.value = input.value.slice(0, start) + emoji + input.value.slice(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + emoji.length;
        autoResizeTextarea();
    });

    $('.pds-messenger-emoji-menu').on('click', function (event) {
        event.stopPropagation();
    });

    chatForm?.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = messageInput.value.trim();
        const hasImage = imageInput.files.length > 0;
        if (!message && !hasImage) return;

        const sendBtn = document.getElementById('orderChatSendBtn');
        sendBtn.disabled = true;

        ensureChat(function () {
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('support_id', supportId);
            if (message) formData.append('message', message);
            if (hasImage) formData.append('chat_image', imageInput.files[0]);

            $.ajax({
                url: chatStoreUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function () {
                    messageInput.value = '';
                    autoResizeTextarea();
                    clearImagePreview();
                    refreshChatMessages(true);
                    if (typeof getNotificationCounts === 'function') getNotificationCounts();
                },
                error: function (xhr) {
                    alert(xhr.responseJSON?.message || 'Something went wrong');
                },
                complete: function () {
                    sendBtn.disabled = false;
                }
            });
        });
    });

    if (supportId) {
        refreshChatMessages(true);
    }
    startPolling();
    autoResizeTextarea();
})();
</script>
@endsection
@endif

<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-12 mb-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="pds-page-title mb-1">{{ $pageTitle }}</h4>
                        <p class="pds-support-page-subtitle mb-0">{{ __('message.customersupport.support_id', ['id' => $data->id]) }}</p>
                    </div>
                    <a href="{{ route('customersupport.index') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-angle-double-left"></i> {{ __('message.back') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8 col-lg-7 mb-4 mb-lg-0">
                <div class="card pds-page-card pds-support-chat-card h-100">
                    <div class="card-body pds-messenger-chat p-0">
                        <div class="pds-messenger-header pds-support-chat-header">
                            <div class="pds-messenger-user">
                                <img src="{{ getSingleMedia($data->user, 'profile_image', null) ?: asset('images/default.png') }}"
                                     alt="{{ optional($data->user)->name }}"
                                     class="pds-messenger-avatar">
                                <div>
                                    <h4 class="pds-messenger-name mb-0">{{ optional($data->user)->name ?? '-' }}</h4>
                                    <span class="pds-messenger-status">{{ $data->support_type ?? __('message.customer_support') }}</span>
                                </div>
                            </div>
                        </div>

                        <div id="support-chat-panel" class="pds-messenger-panel" data-support-id="{{ $data->id }}">
                            <div id="support-chat-body" class="pds-messenger-body">
                                <div class="pds-messenger-empty">
                                    <i class="far fa-comments"></i>
                                    <p>{{ __('message.writeAMessage') ?? 'Write a message to start chat...' }}</p>
                                </div>
                            </div>

                            <div id="supportChatImagePreview" class="pds-messenger-image-preview d-none">
                                <button type="button" class="pds-messenger-image-remove" id="supportChatImageRemove" aria-label="Remove image">
                                    <i class="fas fa-times"></i>
                                </button>
                                <img id="supportChatImageThumb" src="" alt="preview">
                                <span id="supportChatImageName" class="pds-messenger-image-name"></span>
                            </div>

                            {{ html()->form('POST', route('supportchathistory.store'))->id('supportChatForm')->class('pds-messenger-composer')->attribute('enctype', 'multipart/form-data')->open() }}
                                {{ html()->hidden('support_id', $data->id) }}
                                <input type="file" name="chat_image" id="supportChatImage" accept="image/*" class="d-none">

                                <div class="pds-messenger-composer-inner">
                                    <div class="dropdown pds-messenger-emoji-wrap">
                                        <button type="button"
                                                class="pds-messenger-icon-btn"
                                                id="supportChatEmojiBtn"
                                                data-toggle="dropdown"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                title="Emoji">
                                            <i class="far fa-smile"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-left pds-messenger-emoji-menu">
                                            <emoji-picker data-target-input="#supportChatMessage"></emoji-picker>
                                        </div>
                                    </div>

                                    <button type="button" class="pds-messenger-icon-btn" id="supportChatImageBtn" title="Image">
                                        <i class="far fa-image"></i>
                                    </button>

                                    <textarea name="message"
                                              id="supportChatMessage"
                                              class="pds-messenger-input"
                                              rows="1"
                                              placeholder="{{ __('message.enter_name', ['name' => 'message...']) }}"></textarea>

                                    <button type="submit" class="pds-messenger-send-btn" id="supportChatSendBtn" title="Send">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            {{ html()->form()->close() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                <div class="card pds-page-card pds-support-detail-card h-100">
                    <div class="card-header pds-page-header border-0 pb-0">
                        <h5 class="pds-page-title mb-0">{{ __('message.detail_form_title', ['form' => __('message.customer_support')]) }}</h5>
                    </div>
                    <div class="card-body pds-support-detail-body">
                        <div class="pds-support-detail-item">
                            <span class="pds-support-detail-label">{{ __('message.support_id') }}</span>
                            <span class="pds-support-detail-value">#{{ $data->id }}</span>
                        </div>
                        <div class="pds-support-detail-item">
                            <span class="pds-support-detail-label">{{ __('message.created_at') }}</span>
                            <span class="pds-support-detail-value">{{ dateAgoFormate($data->created_at, true) }}</span>
                        </div>
                        <div class="pds-support-detail-item">
                            <span class="pds-support-detail-label">{{ __('message.support_type') }}</span>
                            <span class="pds-support-detail-value">
                                <span class="pds-support-type-badge">{{ $data->support_type ?? '-' }}</span>
                            </span>
                        </div>
                        @if($data->order_id)
                            <div class="pds-support-detail-item">
                                <span class="pds-support-detail-label">{{ __('message.order_id') }}</span>
                                <span class="pds-support-detail-value">
                                    <a href="{{ route('order.show', $data->order_id) }}" class="pds-support-order-link">#{{ $data->order_id }}</a>
                                </span>
                            </div>
                        @endif
                        <div class="pds-support-detail-item">
                            <span class="pds-support-detail-label">{{ __('message.user') }}</span>
                            <span class="pds-support-detail-value">
                                @if(optional($data->user)->id)
                                    <a href="{{ route('users.show', $data->user->id) }}" class="pds-support-user-link">{{ $data->user->name }}</a>
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <div class="pds-support-detail-item">
                            <span class="pds-support-detail-label">{{ __('message.email_address') }}</span>
                            <span class="pds-support-detail-value">{{ optional($data->user)->email ?? '-' }}</span>
                        </div>
                        @if($data->message)
                            <div class="pds-support-detail-note">
                                <span class="pds-support-detail-label">{{ __('message.message') }}</span>
                                <p class="pds-support-detail-message mb-0">{{ $data->message }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
    <script>
        (function () {
            const supportId = {{ $data->id }};
            const chatBody = document.getElementById('support-chat-body');
            const chatForm = document.getElementById('supportChatForm');
            const messageInput = document.getElementById('supportChatMessage');
            const imageInput = document.getElementById('supportChatImage');
            const imagePreview = document.getElementById('supportChatImagePreview');
            const imageThumb = document.getElementById('supportChatImageThumb');
            const imageName = document.getElementById('supportChatImageName');
            const imageRemove = document.getElementById('supportChatImageRemove');
            const chatMessagesUrl = "{{ route('customersupport.chat-messages', $data->id) }}";
            const defaultAvatar = "{{ asset('images/default.png') }}";
            let lastFingerprint = '';
            let pollTimer = null;
            let imageObjectUrl = null;
            let isPolling = false;

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
                if (!force && fingerprint === lastFingerprint) return;
                lastFingerprint = fingerprint;

                if (!messages.length) {
                    chatBody.innerHTML = `<div class="pds-messenger-empty">
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

                if (wasAtBottom || force) {
                    chatBody.scrollTop = chatBody.scrollHeight;
                }
            }

            function refreshChatMessages(force) {
                if (isPolling) return;
                isPolling = true;
                $.get(chatMessagesUrl, function (res) {
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
                }, 3000);
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

            document.getElementById('supportChatImageBtn')?.addEventListener('click', function () {
                imageInput.click();
            });

            imageInput?.addEventListener('change', function () {
                if (imageObjectUrl) {
                    URL.revokeObjectURL(imageObjectUrl);
                    imageObjectUrl = null;
                }
                if (!imageInput.files.length) {
                    clearImagePreview();
                    return;
                }

                const file = imageInput.files[0];
                imageObjectUrl = URL.createObjectURL(file);
                imageThumb.src = imageObjectUrl;
                imageName.textContent = file.name;
                imagePreview.classList.remove('d-none');
                messageInput.focus();
            });

            imageRemove?.addEventListener('click', clearImagePreview);

            messageInput?.addEventListener('input', autoResizeTextarea);
            messageInput?.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    chatForm.requestSubmit();
                }
            });

            $(document).on('emoji-click', 'emoji-picker[data-target-input="#supportChatMessage"]', function (e) {
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

                const sendBtn = document.getElementById('supportChatSendBtn');
                sendBtn.disabled = true;

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('support_id', supportId);
                if (message) formData.append('message', message);
                if (hasImage) formData.append('chat_image', imageInput.files[0]);

                $.ajax({
                    url: chatForm.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function () {
                        messageInput.value = '';
                        autoResizeTextarea();
                        clearImagePreview();
                        refreshChatMessages(true);
                        if (typeof getNotificationCounts === 'function') {
                            getNotificationCounts();
                        }
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.message || 'Something went wrong');
                    },
                    complete: function () {
                        sendBtn.disabled = false;
                    }
                });
            });

            refreshChatMessages(true);
            startPolling();
            autoResizeTextarea();
        })();
    </script>
    @endsection
</x-master-layout>

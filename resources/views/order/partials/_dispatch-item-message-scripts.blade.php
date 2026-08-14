<script>
    (function () {
        if (window.__pdsDispatchItemMessageBound) {
            return;
        }
        window.__pdsDispatchItemMessageBound = true;

        var msgState = {
            listUrl: '',
            storeUrl: '',
            pollTimer: null,
            lastCount: 0,
            $triggerBtn: null,
            imageObjectUrl: null,
            didMutate: false
        };
        var csrf = $('meta[name="csrf-token"]').attr('content');

        function escapeHtml(text) {
            return $('<div>').text(text || '').html();
        }

        function clearImagePreview() {
            var input = document.getElementById('dispatchItemMessageImage');
            if (msgState.imageObjectUrl) {
                URL.revokeObjectURL(msgState.imageObjectUrl);
                msgState.imageObjectUrl = null;
            }
            if (input) input.value = '';
            $('#dispatchItemMessageImageThumb').attr('src', '');
            $('#dispatchItemMessageImageName').text('');
            $('#dispatchItemMessageImagePreview').addClass('d-none');
        }

        function stopPolling() {
            if (msgState.pollTimer) {
                clearInterval(msgState.pollTimer);
                msgState.pollTimer = null;
            }
        }

        function startPolling() {
            stopPolling();
            msgState.pollTimer = setInterval(function () {
                if (!$('#dispatchItemMessageModal').hasClass('show')) {
                    stopPolling();
                    return;
                }
                loadMessages({ silent: true });
            }, 4000);
        }

        function renderMessages(messages) {
            var $list = $('#dispatchItemMessageList');
            $list.empty();
            if (!messages || !messages.length) {
                $list.html('<p class="pds-item-msg-empty">{{ __('message.no_messages_yet') }}</p>');
                msgState.lastCount = 0;
                return;
            }

            messages.forEach(function (m) {
                var side = m.is_admin ? 'admin' : 'client';
                var label = m.is_admin
                    ? (m.sender_name || 'Admin')
                    : (m.sender_name || 'OS');
                var bodyHtml = '';
                if (m.chat_image) {
                    bodyHtml += '<a href="' + escapeHtml(m.chat_image) + '" target="_blank" rel="noopener">' +
                        '<img src="' + escapeHtml(m.chat_image) + '" class="pds-item-msg__image" alt="image" loading="lazy">' +
                        '</a>';
                }
                if (m.message) {
                    bodyHtml += '<div class="pds-item-msg__text">' + escapeHtml(m.message) + '</div>';
                }
                if (!bodyHtml) {
                    bodyHtml = '<div class="pds-item-msg__text text-muted">—</div>';
                }

                $list.append(
                    '<div class="pds-item-msg pds-item-msg--' + side + '">' +
                    '<div class="pds-item-msg__bubble">' +
                    '<div class="pds-item-msg__meta">' + escapeHtml(label) + ' · ' + escapeHtml(m.created_at || '') + '</div>' +
                    bodyHtml +
                    '</div></div>'
                );
            });

            var shouldStick = msgState.lastCount === 0 || messages.length >= msgState.lastCount;
            msgState.lastCount = messages.length;
            if (shouldStick) {
                $list.scrollTop($list[0].scrollHeight);
            }
        }

        function clearUnreadBadge() {
            if (msgState.$triggerBtn && msgState.$triggerBtn.length) {
                msgState.$triggerBtn.find('.pds-dispatch-msg-badge').remove();
            }
        }

        function loadMessages(opts) {
            opts = opts || {};
            if (!msgState.listUrl) return;
            $.getJSON(msgState.listUrl)
                .done(function (res) {
                    renderMessages(res.messages || []);
                    clearUnreadBadge();
                    if (res.os_name || res.customer_name) {
                        var titleBits = [];
                        if (res.os_name) titleBits.push(res.os_name);
                        if (res.customer_name) titleBits.push(res.customer_name);
                        $('#dispatchItemMessageCustomer').text(titleBits.length ? ' — ' + titleBits.join(' · ') : '');
                    }
                    if (res.meta_line) {
                        $('#dispatchItemMessageMeta').text(res.meta_line);
                    }
                })
                .fail(function () {
                    if (!opts.silent) {
                        $('#dispatchItemMessageList').html('<p class="pds-item-msg-empty text-danger">Unable to load messages.</p>');
                    }
                });
        }

        function sendMessage() {
            var text = $.trim($('#dispatchItemMessageInput').val() || '');
            var imageInput = document.getElementById('dispatchItemMessageImage');
            var hasImage = imageInput && imageInput.files && imageInput.files.length > 0;
            if ((!text && !hasImage) || !msgState.storeUrl) return;

            var formData = new FormData();
            formData.append('_token', csrf);
            if (text) formData.append('message', text);
            if (hasImage) formData.append('chat_image', imageInput.files[0]);

            var $btn = $('#dispatchItemMessageSend').prop('disabled', true);
            $.ajax({
                url: msgState.storeUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#dispatchItemMessageInput').val('');
                    clearImagePreview();
                    msgState.didMutate = true;
                    loadMessages();
                    if (res.message && typeof showMessage === 'function') {
                        showMessage(res.message);
                    }
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : ((xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.message)
                            ? xhr.responseJSON.errors.message[0]
                            : ((xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.chat_image)
                                ? xhr.responseJSON.errors.chat_image[0]
                                : 'Unable to send message'));
                    if (typeof errorMessage === 'function') {
                        errorMessage(msg);
                    } else {
                        alert(msg);
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    $('#dispatchItemMessageInput').focus();
                }
            });
        }

        $(document).on('click', '.js-dispatch-item-message', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            msgState.listUrl = $btn.data('list-url');
            msgState.storeUrl = $btn.data('store-url');
            msgState.$triggerBtn = $btn;
            msgState.lastCount = 0;
            msgState.didMutate = false;

            var osName = $btn.data('os-name') || '';
            var customer = $btn.data('customer') || '';
            var titleBits = [];
            if (osName) titleBits.push(osName);
            if (customer) titleBits.push(customer);
            $('#dispatchItemMessageCustomer').text(titleBits.length ? ' — ' + titleBits.join(' · ') : '');
            $('#dispatchItemMessageMeta').text($btn.data('meta') || '');
            $('#dispatchItemMessageInput').val('');
            clearImagePreview();
            renderMessages([]);
            $('#dispatchItemMessageModal').modal('show');
            loadMessages();
            startPolling();
            setTimeout(function () {
                $('#dispatchItemMessageInput').focus();
            }, 250);
        });

        $(document).on('click', '#dispatchItemMessageSend', function () {
            sendMessage();
        });

        $(document).on('click', '#dispatchItemMessageImageBtn', function () {
            $('#dispatchItemMessageImage').trigger('click');
        });

        $(document).on('change', '#dispatchItemMessageImage', function () {
            var input = this;
            if (msgState.imageObjectUrl) {
                URL.revokeObjectURL(msgState.imageObjectUrl);
                msgState.imageObjectUrl = null;
            }
            if (!input.files || !input.files.length) {
                clearImagePreview();
                return;
            }
            var file = input.files[0];
            msgState.imageObjectUrl = URL.createObjectURL(file);
            $('#dispatchItemMessageImageThumb').attr('src', msgState.imageObjectUrl);
            $('#dispatchItemMessageImageName').text(file.name);
            $('#dispatchItemMessageImagePreview').removeClass('d-none');
            $('#dispatchItemMessageInput').focus();
        });

        $(document).on('click', '#dispatchItemMessageImageRemove', function () {
            clearImagePreview();
        });

        $(document).on('keydown', '#dispatchItemMessageInput', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        $(document).on('emoji-click', 'emoji-picker[data-target-input="#dispatchItemMessageInput"]', function (e) {
            var input = document.querySelector(e.target.dataset.targetInput);
            if (!input) return;
            var start = input.selectionStart != null ? input.selectionStart : input.value.length;
            var end = input.selectionEnd != null ? input.selectionEnd : input.value.length;
            var emoji = (e.originalEvent && e.originalEvent.detail && e.originalEvent.detail.unicode)
                || (e.detail && e.detail.unicode)
                || '';
            input.value = input.value.slice(0, start) + emoji + input.value.slice(end);
            input.focus();
            input.selectionStart = input.selectionEnd = start + emoji.length;
        });

        $(document).on('click', '.pds-item-chat-emoji-menu', function (event) {
            event.stopPropagation();
        });

        $('#dispatchItemMessageModal').on('hidden.bs.modal', function () {
            stopPolling();
            clearImagePreview();
            msgState.listUrl = '';
            msgState.storeUrl = '';
            msgState.$triggerBtn = null;
            msgState.didMutate = false;
            // Refresh sort/tabs: unread clears on open; replied threads sink to end.
            if (typeof window.reloadDispatchItemsTable === 'function') {
                window.reloadDispatchItemsTable();
            }
        });
    })();
</script>

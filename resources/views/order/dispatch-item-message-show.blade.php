@php
    $parcelLabel = $item->code ? '#'.$item->code : '#'.$item->id;
    $chatTitleName = $osName ?: ($item->customer_name ?: $parcelLabel);
    $osAvatar = $osProfileImage ?: asset('images/default.png');
    $metaLine = trim($parcelLabel.' · '.($item->customer_phone ?: ''));
    $riderName = optional($item->deliveryMan)->name;
    $canEditInfo = !empty($canEditInfo);
@endphp
<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-support-page">
        <div class="row pds-support-page-head">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="pds-page-title mb-1">{{ $pageTitle }}</h4>
                        <p class="pds-support-page-subtitle mb-0">{{ $metaLine !== '' ? $metaLine : $parcelLabel }}</p>
                    </div>
                    <div class="pds-support-page-actions">
                        <a href="{{ route('order.dispatch.item.rider-preview', [$order->id, $item->id]) }}"
                           class="btn btn-sm btn-outline-primary loadRemoteModel pds-support-page-action"
                           title="{{ __('message.rider_app_view') }}">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($canEditInfo)
                            <a href="{{ route('order.dispatch.item.edit', [$order->id, $item->id]) }}"
                               class="btn btn-sm btn-outline-primary loadRemoteModel pds-support-page-action"
                               title="{{ __('message.edit') }}">
                                <i class="fas fa-pen"></i>
                            </a>
                        @endif
                        <a href="{{ route('order.dispatch.assigned-items') }}" class="btn btn-sm btn-primary pds-support-page-action pds-support-page-action--back">
                            <i class="fa fa-angle-double-left"></i> {{ __('message.back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row pds-support-layout">
            <div class="col-xl-8 col-lg-7">
                <div class="card pds-page-card pds-support-chat-card">
                    <div class="card-body pds-messenger-chat p-0">
                        <div class="pds-messenger-header pds-support-chat-header">
                            <div class="pds-messenger-user">
                                <img src="{{ $osAvatar }}"
                                     alt="{{ $chatTitleName }}"
                                     class="pds-messenger-avatar">
                                <div>
                                    <h4 class="pds-messenger-name mb-0">{{ $chatTitleName }}</h4>
                                    <span class="pds-messenger-status">
                                        <span class="pds-support-online-dot"></span>
                                        {{ $item->customer_name ?: __('message.chat') }}
                                        @if($item->customer_name && $osName)
                                            · {{ $osName }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div id="support-chat-panel" class="pds-messenger-panel" data-item-id="{{ $item->id }}">
                            <div id="support-chat-body" class="pds-messenger-body" tabindex="0">
                                <div class="pds-messenger-empty">
                                    <i class="far fa-comments"></i>
                                    <p>Write a message to start chat...</p>
                                </div>
                            </div>

                            <div id="supportChatImagePreview" class="pds-messenger-image-preview d-none">
                                <div class="pds-messenger-image-preview-card">
                                    <div class="pds-messenger-image-preview-thumb">
                                        <img id="supportChatImageThumb" src="" alt="preview">
                                    </div>
                                    <div class="pds-messenger-image-preview-meta">
                                        <span class="pds-messenger-image-preview-label">Ready to send</span>
                                        <span id="supportChatImageName" class="pds-messenger-image-name"></span>
                                    </div>
                                    <button type="button" class="pds-messenger-image-remove" id="supportChatImageRemove" aria-label="Remove image">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div id="supportChatGifPanel" class="pds-support-gif-panel d-none">
                                <div class="pds-support-gif-panel-head">
                                    <div class="pds-support-gif-tabs">
                                        <button type="button" class="pds-support-gif-tab is-active" data-gif-tab="gif">GIF</button>
                                        <button type="button" class="pds-support-gif-tab" data-gif-tab="sticker">Sticker</button>
                                    </div>
                                    <button type="button" class="pds-messenger-icon-btn" id="supportChatGifClose" title="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="pds-support-gif-grid" id="supportChatGifGrid"></div>
                            </div>

                            <div id="supportChatAttachMenu" class="pds-support-attach-menu d-none">
                                <button type="button" class="pds-support-attach-item" data-attach="gallery">
                                    <span class="pds-support-attach-icon" style="--attach-color:#0891B2"><i class="fas fa-images"></i></span>
                                    <span>
                                        <strong>Image</strong>
                                        <small>Photo / PNG / JPG / GIF</small>
                                    </span>
                                </button>
                                <button type="button" class="pds-support-attach-item" data-attach="gif">
                                    <span class="pds-support-attach-icon" style="--attach-color:#DB2777"><i class="fas fa-gift"></i></span>
                                    <span>
                                        <strong>GIF / Sticker</strong>
                                        <small>Animated media</small>
                                    </span>
                                </button>
                                <button type="button" class="pds-support-attach-item" data-attach="audio">
                                    <span class="pds-support-attach-icon" style="--attach-color:#7C3AED"><i class="fas fa-microphone"></i></span>
                                    <span>
                                        <strong>Audio</strong>
                                        <small>Voice / music file</small>
                                    </span>
                                </button>
                                <button type="button" class="pds-support-attach-item" data-attach="video">
                                    <span class="pds-support-attach-icon" style="--attach-color:#EA580C"><i class="fas fa-video"></i></span>
                                    <span>
                                        <strong>Video</strong>
                                        <small>MP4 / MOV / WEBM</small>
                                    </span>
                                </button>
                            </div>

                            {{ html()->form('POST', route('order.dispatch.item.messages.store', [$order->id, $item->id]))->id('supportChatForm')->class('pds-messenger-composer pds-support-composer')->attribute('enctype', 'multipart/form-data')->open() }}
                                <input type="hidden" name="message_type" id="supportChatMessageType" value="text">
                                <input type="file" name="chat_image" id="supportChatImage" accept="image/*,.gif,.webp" class="d-none">
                                <input type="file" name="chat_media" id="supportChatAudio" accept="audio/*,.m4a,.mp3,.aac,.ogg,.wav,.webm" class="d-none">
                                <input type="file" name="chat_media" id="supportChatVideo" accept="video/*,.mp4,.mov,.webm,.m4v" class="d-none">

                                <div id="supportChatVoiceBar" class="pds-support-voice-bar d-none" aria-live="polite">
                                    <button type="button" class="pds-support-voice-cancel" id="supportChatVoiceCancel" title="Delete recording" aria-label="Delete recording">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <div class="pds-support-voice-live">
                                        <span class="pds-support-voice-dot" aria-hidden="true"></span>
                                        <span class="pds-support-voice-label">Recording</span>
                                    </div>
                                    <div class="pds-support-voice-wave" id="supportChatVoiceWave" aria-hidden="true"></div>
                                    <span class="pds-support-voice-timer" id="supportChatVoiceTimer">00:00</span>
                                    <button type="button" class="pds-support-voice-send" id="supportChatVoiceSend" title="Send voice message" aria-label="Send voice message">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>

                                <div class="pds-messenger-composer-inner" id="supportChatComposerInner">
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

                                    <button type="button" class="pds-messenger-icon-btn" id="supportChatAttachBtn" title="Attach">
                                        <i class="fas fa-plus"></i>
                                    </button>

                                    <textarea name="message"
                                              id="supportChatMessage"
                                              class="pds-messenger-input"
                                              rows="1"
                                              placeholder="{{ __('message.enter_name', ['name' => 'message...']) }}"></textarea>

                                    <button type="button" class="pds-messenger-icon-btn pds-support-mic-btn" id="supportChatMicBtn" title="Voice message">
                                        <i class="fas fa-microphone"></i>
                                    </button>

                                    <button type="submit" class="pds-messenger-send-btn d-none" id="supportChatSendBtn" title="Send">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            {{ html()->form()->close() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                @php
                    $customerPhone = trim((string) ($item->customer_phone ?? ''));
                    if ($customerPhone === '') {
                        $customerPhone = null;
                    }
                    $orderStatus = preg_replace('/[^a-z0-9_-]+/', '-', strtolower((string) ($order->status ?? 'open'))) ?: 'open';
                @endphp
                <aside class="card pds-page-card pds-support-detail-card">
                    <div class="pds-support-detail-hero">
                        <div class="pds-support-detail-hero-top">
                            <span class="pds-support-detail-kicker">{{ __('message.assigned_item_list') }}</span>
                            <span class="pds-support-id-chip">{{ $parcelLabel }}</span>
                        </div>
                        <div class="pds-support-detail-profile">
                            <div class="pds-support-detail-avatar-wrap">
                                <img src="{{ $osAvatar }}"
                                     alt="{{ $chatTitleName }}"
                                     class="pds-support-detail-avatar">
                            </div>
                            <div class="pds-support-detail-profile-copy">
                                @if(optional($order->client)->id)
                                    <a href="{{ route('users.show', $order->client->id) }}" class="pds-support-detail-name">{{ $osName ?: $order->client->name }}</a>
                                @else
                                    <span class="pds-support-detail-name">{{ $osName ?: ($item->customer_name ?: '-') }}</span>
                                @endif
                                <div class="pds-support-detail-badges">
                                    <span class="pds-support-type-badge">{{ __('message.chat') }}</span>
                                    <span class="pds-support-status-badge pds-support-status-badge--{{ $orderStatus }}">{{ ucfirst(str_replace('-', ' ', $orderStatus)) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pds-support-detail-body">
                        <div class="pds-support-detail-meta">
                            <div class="pds-support-detail-meta-row">
                                <span class="pds-support-detail-meta-icon"><i class="fas fa-box"></i></span>
                                <div class="pds-support-detail-meta-copy">
                                    <span class="pds-support-detail-label">{{ __('message.order_id') }}</span>
                                    <span class="pds-support-detail-value">
                                        <a href="{{ route('order.show', $order->id) }}" class="pds-support-order-link">#{{ $order->id }}</a>
                                    </span>
                                </div>
                            </div>
                            @if($item->customer_name)
                                <div class="pds-support-detail-meta-row">
                                    <span class="pds-support-detail-meta-icon"><i class="fas fa-user"></i></span>
                                    <div class="pds-support-detail-meta-copy">
                                        <span class="pds-support-detail-label">{{ __('message.customer_name') }}</span>
                                        <span class="pds-support-detail-value">{{ $item->customer_name }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($customerPhone)
                                <div class="pds-support-detail-meta-row">
                                    <span class="pds-support-detail-meta-icon"><i class="fas fa-phone-alt"></i></span>
                                    <div class="pds-support-detail-meta-copy">
                                        <span class="pds-support-detail-label">{{ __('message.contact_number') }}</span>
                                        <span class="pds-support-detail-value">{{ $customerPhone }}</span>
                                    </div>
                                </div>
                            @endif
                            @if($riderName)
                                <div class="pds-support-detail-meta-row">
                                    <span class="pds-support-detail-meta-icon"><i class="fas fa-motorcycle"></i></span>
                                    <div class="pds-support-detail-meta-copy">
                                        <span class="pds-support-detail-label">{{ __('message.delivery_man') }}</span>
                                        <span class="pds-support-detail-value">{{ $riderName }}</span>
                                    </div>
                                </div>
                            @endif
                            <div class="pds-support-detail-meta-row">
                                <span class="pds-support-detail-meta-icon"><i class="far fa-calendar-alt"></i></span>
                                <div class="pds-support-detail-meta-copy">
                                    <span class="pds-support-detail-label">{{ __('message.created_at') }}</span>
                                    <span class="pds-support-detail-value">{{ dateAgoFormate($item->created_at ?? $order->created_at, true) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pds-support-detail-note">
                            <div class="pds-support-detail-note-head">
                                <span class="pds-support-detail-meta-icon pds-support-detail-meta-icon--sm"><i class="far fa-map"></i></span>
                                <span class="pds-support-detail-label mb-0">{{ __('message.address') }}</span>
                            </div>
                            <p class="pds-support-detail-message mb-0">{{ $item->customer_address ?: ($item->remark ?: '—') }}</p>
                        </div>

                        <div class="pds-support-detail-footer">
                            <div class="pds-support-detail-tips">
                                <strong>Quick tips</strong>
                                <ul>
                                    <li>Reply with text, emoji, GIF, sticker or voice</li>
                                    <li>Keep answers clear and polite</li>
                                    <li>Confirm parcel details before closing</li>
                                </ul>
                            </div>
                            @if(optional($order->client)->id)
                                <a href="{{ route('users.show', $order->client->id) }}" class="pds-support-detail-cta">
                                    <i class="fas fa-user"></i> View OS profile
                                </a>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    @php
        $initialChatMessages = collect($messages ?? [])
            ->map(fn ($chat) => app(\App\Http\Controllers\OrderController::class)->formatDispatchItemChatMessage($chat, auth()->id()))
            ->values();
    @endphp
    {{-- Inline boot: wait for jQuery (slot scripts run before footer assets). --}}
    <script>
        (function bootOrderItemChatPage() {
            function withJquery(cb) {
                if (window.jQuery) return cb(window.jQuery);
                setTimeout(function () { withJquery(cb); }, 40);
            }

            withJquery(function ($) {
            const orderId = {{ (int) $order->id }};
            const itemId = {{ (int) $item->id }};
            const chatBody = document.getElementById('support-chat-body');
            const chatForm = document.getElementById('supportChatForm');
            const messageInput = document.getElementById('supportChatMessage');
            const messageTypeInput = document.getElementById('supportChatMessageType');
            const imageInput = document.getElementById('supportChatImage');
            const audioInput = document.getElementById('supportChatAudio');
            const videoInput = document.getElementById('supportChatVideo');
            const imagePreview = document.getElementById('supportChatImagePreview');
            const imageThumb = document.getElementById('supportChatImageThumb');
            const imageName = document.getElementById('supportChatImageName');
            const imageRemove = document.getElementById('supportChatImageRemove');
            const attachBtn = document.getElementById('supportChatAttachBtn');
            const attachMenu = document.getElementById('supportChatAttachMenu');
            const gifPanel = document.getElementById('supportChatGifPanel');
            const gifGrid = document.getElementById('supportChatGifGrid');
            const gifClose = document.getElementById('supportChatGifClose');
            const micBtn = document.getElementById('supportChatMicBtn');
            const voiceBar = document.getElementById('supportChatVoiceBar');
            const voiceWave = document.getElementById('supportChatVoiceWave');
            const voiceTimer = document.getElementById('supportChatVoiceTimer');
            const voiceCancel = document.getElementById('supportChatVoiceCancel');
            const voiceSend = document.getElementById('supportChatVoiceSend');
            const composerInner = document.getElementById('supportChatComposerInner');
            const chatMessagesUrl = @json(route('order.dispatch.item.messages', [$order->id, $item->id]));
            const defaultAvatar = @json($osAvatar);
            const csrfToken = @json(csrf_token());
            const initialMessages = @json($initialChatMessages);

            let lastFingerprint = '';
            let pollTimer = null;
            let imageObjectUrl = null;
            let pendingImageFile = null;
            let isPolling = false;
            let activeGifTab = 'gif';
            let mediaRecorder = null;
            let recordedChunks = [];
            let recordStartedAt = 0;
            let recordTimer = null;
            let recordedBlob = null;

            const READY_GIFS = [
                { url: 'https://media.giphy.com/media/3o7aCTPPm4OHfRLSH6/giphy.gif', label: 'hello' },
                { url: 'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy.gif', label: 'thanks' },
                { url: 'https://media.giphy.com/media/26u4cqiYI30juCOGY/giphy.gif', label: 'ok' },
                { url: 'https://media.giphy.com/media/xT9IgG50Fb7Mi0prBC/giphy.gif', label: 'clap' },
                { url: 'https://media.giphy.com/media/artj92V8o75VPL7ATN/giphy.gif', label: 'love' },
                { url: 'https://media.giphy.com/media/l3q2K5jinAlChoCLS/giphy.gif', label: 'yes' },
                { url: 'https://media.giphy.com/media/3oz8xIsloV7zOmt81G/giphy.gif', label: 'wow' },
                { url: 'https://media.giphy.com/media/l0MYC0LajbaPoEADu/giphy.gif', label: 'party' },
                { url: 'https://media.giphy.com/media/5GoVLqeAOo6PK/giphy.gif', label: 'happy' },
                { url: 'https://media.giphy.com/media/111ebonMs90YLu/giphy.gif', label: 'thumbs' },
                { url: 'https://media.giphy.com/media/3oEjI6SIIHBdRxXI40/giphy.gif', label: 'loading' },
                { url: 'https://media.giphy.com/media/26BRv0ThaiuhsbVWU/giphy.gif', label: 'done' },
            ];

            const READY_STICKERS = [
                { url: 'https://media.giphy.com/media/MDJ9IbxxvHCNyCrCwH/giphy.gif', label: 'wave' },
                { url: 'https://media.giphy.com/media/jUwpNzg9JcyrO/giphy.gif', label: 'dance' },
                { url: 'https://media.giphy.com/media/3o6Zt6ML6BklcajjsA/giphy.gif', label: 'heart' },
                { url: 'https://media.giphy.com/media/l0ExncehJzuyqzs9O/giphy.gif', label: 'smile' },
                { url: 'https://media.giphy.com/media/IcGkqdUmYLFGE/giphy.gif', label: 'cool' },
                { url: 'https://media.giphy.com/media/l41lGvinEgARjB2HC/giphy.gif', label: 'hi' },
                { url: 'https://media.giphy.com/media/3oEjI5VtIhHvK37WYo/giphy.gif', label: 'star' },
                { url: 'https://media.giphy.com/media/xUPGcguWZHRC2HyBRS/giphy.gif', label: 'fire' },
            ];

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            }

            function isSentMessage(item) {
                return item.current_user_class === 'mm-current-user';
            }

            function msgType(item) {
                return String(item.message_type || 'text').toLowerCase();
            }

            function isVideoItem(item) {
                const t = msgType(item);
                if (t === 'video') return true;
                if (t === 'audio') return false;
                const url = String(item.chat_media || '');
                return /\.(mp4|mov|m4v|mkv)(\?|$)/i.test(url);
            }

            function isAudioItem(item) {
                const t = msgType(item);
                if (t === 'video') return false;
                if (t === 'audio') return true;
                const url = String(item.chat_media || '');
                if (/\.(mp4|mov|m4v|mkv)(\?|$)/i.test(url)) return false;
                return /\.(m4a|mp3|aac|wav|ogg|webm)(\?|$)/i.test(url);
            }

            function isImageLike(item) {
                const t = msgType(item);
                return ['image', 'gif', 'sticker'].includes(t) || !!item.chat_image;
            }

            function videoUrl(item) {
                const media = String(item.chat_media || '');
                if (msgType(item) === 'video') return media || '';
                if (media && /\.(mp4|mov|m4v|mkv)(\?|$)/i.test(media)) return media;
                return '';
            }

            function audioUrl(item) {
                if (isVideoItem(item) && msgType(item) === 'video') return '';
                const media = String(item.chat_media || '');
                if (media && /\.(m4a|mp3|aac|wav|ogg)(\?|$)/i.test(media)) return media;
                if (media && /\.webm(\?|$)/i.test(media) && msgType(item) !== 'video') return media;
                if (msgType(item) === 'audio') return media || item.chat_image || '';
                return '';
            }

            function imageUrl(item) {
                return item.chat_image || (!isAudioItem(item) && !isVideoItem(item) ? item.chat_media : '') || '';
            }

            function classifyMediaFile(file) {
                if (!file) return null;
                const type = String(file.type || '').toLowerCase();
                const name = String(file.name || 'pasted').toLowerCase();
                if (type.startsWith('image/') || /\.(gif|png|jpe?g|webp|bmp|heic)$/i.test(name)) return 'image';
                if (type.startsWith('video/') || /\.(mp4|mov|m4v|mkv)$/i.test(name)) return 'video';
                if (type.startsWith('audio/') || /\.(m4a|mp3|aac|ogg|wav)$/i.test(name)) return 'audio';
                if (/\.webm$/i.test(name)) return type.startsWith('video/') ? 'video' : 'audio';
                if (type.startsWith('image')) return 'image';
                return null;
            }

            function fileFromBlob(blob, fallbackName) {
                if (!blob) return null;
                const type = blob.type || 'application/octet-stream';
                let name = fallbackName || 'pasted-file';
                if (blob instanceof File && blob.name) name = blob.name;
                else if (type.startsWith('image/')) {
                    const ext = (type.split('/')[1] || 'png').replace('jpeg', 'jpg');
                    name = 'pasted-image.' + ext;
                } else if (type.startsWith('audio/')) {
                    name = 'pasted-audio.' + ((type.split('/')[1] || 'webm').split(';')[0]);
                } else if (type.startsWith('video/')) {
                    name = 'pasted-video.' + ((type.split('/')[1] || 'mp4').split(';')[0]);
                }
                return blob instanceof File ? blob : new File([blob], name, { type: type });
            }

            function collectClipboardFiles(clip) {
                const files = [];
                if (!clip) return files;
                if (clip.files && clip.files.length) {
                    Array.from(clip.files).forEach(function (f) {
                        if (f) files.push(f);
                    });
                }
                if (clip.items && clip.items.length) {
                    Array.from(clip.items).forEach(function (item) {
                        if (!item) return;
                        if (item.kind === 'file' || String(item.type || '').startsWith('image/') ||
                            String(item.type || '').startsWith('audio/') || String(item.type || '').startsWith('video/')) {
                            const f = item.getAsFile && item.getAsFile();
                            if (f) files.push(fileFromBlob(f));
                        }
                    });
                }
                const seen = new Set();
                return files.filter(function (f) {
                    const key = [f.name, f.size, f.type].join(':');
                    if (seen.has(key)) return false;
                    seen.add(key);
                    return true;
                });
            }

            function looksLikeMediaFileName(text) {
                return /^[^\s\\/]+\.(png|jpe?g|gif|webp|bmp|heic|mp4|mov|webm|m4v|m4a|mp3|aac|wav|ogg)$/i.test(String(text || '').trim());
            }

            async function readClipboardMediaAsync() {
                if (!navigator.clipboard || !navigator.clipboard.read) return false;
                try {
                    const items = await navigator.clipboard.read();
                    const files = [];
                    for (let i = 0; i < items.length; i++) {
                        const item = items[i];
                        const types = item.types || [];
                        for (let t = 0; t < types.length; t++) {
                            const type = types[t];
                            if (!type.startsWith('image/') && !type.startsWith('audio/') && !type.startsWith('video/')) continue;
                            try {
                                const blob = await item.getType(type);
                                files.push(fileFromBlob(blob));
                            } catch (e) { /* skip */ }
                        }
                    }
                    if (!files.length) return false;
                    return handleIncomingFiles(files);
                } catch (e) {
                    return false;
                }
            }

            function assignFileToInput(input, file) {
                if (!input || !file) return;
                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                } catch (e) {
                    /* DataTransfer may be unavailable */
                }
            }

            function sendMediaFile(file, type) {
                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('message_type', type);
                setFormDataFile(formData, 'chat_media', file);
                postChat(formData, function () {
                    if (audioInput) audioInput.value = '';
                    if (videoInput) videoInput.value = '';
                });
            }

            function queueImageFile(file) {
                if (!file) return;
                pendingImageFile = file;
                if (imageObjectUrl) {
                    URL.revokeObjectURL(imageObjectUrl);
                    imageObjectUrl = null;
                }
                assignFileToInput(imageInput, file);
                imageObjectUrl = URL.createObjectURL(file);
                imageThumb.src = imageObjectUrl;
                imageName.textContent = file.name || 'Pasted image';
                imagePreview.classList.remove('d-none');
                messageTypeInput.value = 'image';
                messageInput.focus();
                updateComposerMode();
            }

            function handleIncomingFiles(fileList) {
                const files = Array.from(fileList || []).filter(Boolean);
                if (!files.length) return false;
                let handled = false;
                files.forEach(function (file) {
                    const kind = classifyMediaFile(file);
                    if (!kind) return;
                    handled = true;
                    if (kind === 'image') {
                        queueImageFile(file);
                    } else {
                        sendMediaFile(file, kind);
                    }
                });
                return handled;
            }

            function messagesFingerprint(messages) {
                return messages.map(function (item) {
                    return [item.id, item.message || '', item.message_type || '', item.chat_image || '', item.chat_media || '', item.time_label || ''].join(':');
                }).join('|');
            }

            function formatClock(sec) {
                const m = String(Math.floor(sec / 60)).padStart(2, '0');
                const s = String(Math.floor(sec % 60)).padStart(2, '0');
                return m + ':' + s;
            }

            function renderAudioBubble(item, sent) {
                const url = escapeHtml(audioUrl(item));
                return `
                    <div class="pds-support-audio ${sent ? 'pds-support-audio--sent' : 'pds-support-audio--received'}" data-audio-url="${url}">
                        <button type="button" class="pds-support-audio-play" aria-label="Play">
                            <i class="fas fa-play"></i>
                        </button>
                        <div class="pds-support-audio-meta">
                            <div class="pds-support-audio-bars" aria-hidden="true">
                                ${Array.from({ length: 22 }).map(function (_, i) {
                                    const h = 28 + ((i * 37) % 52);
                                    return `<span style="--h:${h}%"></span>`;
                                }).join('')}
                            </div>
                            <div class="pds-support-audio-times">
                                <span class="pds-support-audio-pos">00:00</span>
                                <span class="pds-support-audio-dur">00:00</span>
                            </div>
                        </div>
                        <audio preload="metadata" src="${url}"></audio>
                    </div>`;
            }

            function renderVideoBubble(item) {
                const url = escapeHtml(videoUrl(item));
                return `<div class="pds-messenger-video-wrap">
                    <video class="pds-messenger-msg-video" src="${url}" controls playsinline preload="metadata"></video>
                </div>`;
            }

            function renderMessageContent(item, sent) {
                if (isVideoItem(item) && videoUrl(item)) {
                    return renderVideoBubble(item);
                }

                if (isAudioItem(item) && audioUrl(item)) {
                    return renderAudioBubble(item, sent);
                }

                if (isImageLike(item) && imageUrl(item)) {
                    const url = escapeHtml(imageUrl(item));
                    const stickerClass = msgType(item) === 'sticker' ? ' pds-messenger-msg-image--sticker' : '';
                    return `<button type="button" class="pds-messenger-image-link" data-full-src="${url}" title="View image">
                        <img src="${url}" alt="image" class="pds-messenger-msg-image${stickerClass}" loading="lazy">
                    </button>`;
                }

                const text = escapeHtml(item.message || '').replace(/\n/g, '<br>');
                if (!text) return '';
                const emojiOnly = /^[\p{Extended_Pictographic}\uFE0F\u200D\s]+$/u.test(item.message || '');
                return `<div class="pds-messenger-bubble-text${emojiOnly ? ' pds-messenger-bubble-text--emoji' : ''}">${text}</div>`;
            }

            function buildMessageHtml(item, showMeta) {
                const sent = isSentMessage(item);
                const audio = isAudioItem(item) && !!audioUrl(item);
                const video = isVideoItem(item) && !!videoUrl(item);
                const media = (!audio && !video && isImageLike(item) && !!imageUrl(item));
                const bare = audio || video || media;

                return `
                    <div class="pds-messenger-msg ${sent ? 'pds-messenger-msg--sent' : 'pds-messenger-msg--received'} ${showMeta ? 'pds-messenger-msg--group-start' : 'pds-messenger-msg--grouped'} ${bare ? 'pds-messenger-msg--media' : ''} ${audio ? 'pds-messenger-msg--audio' : ''} ${video ? 'pds-messenger-msg--video' : ''}"
                         data-message-id="${item.id}">
                        ${!sent && showMeta ? `<img src="${item.profile_image || defaultAvatar}" alt="" class="pds-messenger-msg-avatar" loading="lazy">` : ''}
                        ${!sent && !showMeta ? '<span class="pds-messenger-msg-avatar-spacer"></span>' : ''}
                        <div class="pds-messenger-msg-content">
                            ${showMeta ? `<div class="pds-messenger-msg-meta">${escapeHtml(item.user_name || '')} · ${escapeHtml(item.time_label || '')}</div>` : ''}
                            <div class="pds-messenger-bubble ${bare ? 'pds-messenger-bubble--media' : ''} ${audio ? 'pds-messenger-bubble--audio' : ''} ${video ? 'pds-messenger-bubble--video' : ''}">
                                ${renderMessageContent(item, sent)}
                            </div>
                            ${!showMeta ? `<span class="pds-messenger-msg-time">${escapeHtml(item.time_label || '')}</span>` : ''}
                        </div>
                    </div>`;
            }

            function bindAudioPlayers(root) {
                (root || chatBody).querySelectorAll('.pds-support-audio').forEach(function (wrap) {
                    if (wrap.dataset.bound === '1') return;
                    wrap.dataset.bound = '1';
                    const audio = wrap.querySelector('audio');
                    const btn = wrap.querySelector('.pds-support-audio-play');
                    const icon = btn.querySelector('i');
                    const posEl = wrap.querySelector('.pds-support-audio-pos');
                    const durEl = wrap.querySelector('.pds-support-audio-dur');
                    const bars = wrap.querySelector('.pds-support-audio-bars');

                    function syncDuration() {
                        if (isFinite(audio.duration) && audio.duration > 0) {
                            durEl.textContent = formatClock(audio.duration);
                        }
                    }

                    audio.addEventListener('loadedmetadata', syncDuration);
                    audio.addEventListener('durationchange', syncDuration);
                    audio.addEventListener('timeupdate', function () {
                        posEl.textContent = formatClock(audio.currentTime || 0);
                        const p = audio.duration > 0 ? (audio.currentTime / audio.duration) : 0;
                        bars.style.setProperty('--progress', (p * 100).toFixed(1) + '%');
                        const spans = bars.querySelectorAll('span');
                        spans.forEach(function (span, idx) {
                            const played = (idx / Math.max(spans.length - 1, 1)) <= p;
                            span.style.opacity = played ? '1' : '0.32';
                        });
                    });
                    audio.addEventListener('play', function () {
                        icon.className = 'fas fa-pause';
                        wrap.classList.add('is-playing');
                        chatBody.querySelectorAll('.pds-support-audio audio').forEach(function (other) {
                            if (other !== audio && !other.paused) other.pause();
                        });
                    });
                    audio.addEventListener('pause', function () {
                        icon.className = 'fas fa-play';
                        wrap.classList.remove('is-playing');
                    });
                    audio.addEventListener('ended', function () {
                        icon.className = 'fas fa-play';
                        wrap.classList.remove('is-playing');
                        bars.style.setProperty('--progress', '0%');
                        posEl.textContent = '00:00';
                    });

                    btn.addEventListener('click', function () {
                        if (audio.paused) {
                            audio.play().catch(function () {
                                alert('Audio play failed');
                            });
                        } else {
                            audio.pause();
                        }
                    });
                });
            }

            function renderMessages(messages, force) {
                if (!chatBody || !Array.isArray(messages)) return;

                const fingerprint = messagesFingerprint(messages);
                if (!force && fingerprint === lastFingerprint) return;
                lastFingerprint = fingerprint;

                if (!messages.length) {
                    chatBody.innerHTML = `<div class="pds-messenger-empty">
                        <i class="far fa-comments"></i>
                        <p>Write a message to start chat...</p>
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
                bindAudioPlayers(chatBody);

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
                pendingImageFile = null;
                imageInput.value = '';
                imageThumb.src = '';
                imageName.textContent = '';
                imagePreview.classList.add('d-none');
                messageTypeInput.value = 'text';
            }

            function autoResizeTextarea() {
                messageInput.style.height = 'auto';
                messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
                updateComposerMode();
            }

            function updateComposerMode() {
                const hasText = messageInput.value.trim().length > 0;
                const hasImage = imageInput.files.length > 0 || !!pendingImageFile;
                micBtn.classList.toggle('d-none', hasText || hasImage);
                document.getElementById('supportChatSendBtn').classList.toggle('d-none', !(hasText || hasImage));
            }

            function hidePanels() {
                attachMenu.classList.add('d-none');
                gifPanel.classList.add('d-none');
            }

            function renderGifGrid() {
                const items = activeGifTab === 'sticker' ? READY_STICKERS : READY_GIFS;
                gifGrid.innerHTML = items.map(function (item) {
                    return `<button type="button" class="pds-support-gif-item" data-url="${escapeHtml(item.url)}" data-type="${activeGifTab === 'sticker' ? 'sticker' : 'gif'}" title="${escapeHtml(item.label)}">
                        <img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.label)}" loading="lazy">
                    </button>`;
                }).join('');
            }

            function setFormDataFile(formData, field, file) {
                formData.append(field, file);
            }

            function postChat(formData, onDone) {
                const sendBtn = document.getElementById('supportChatSendBtn');
                sendBtn.disabled = true;
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
                        if (audioInput) audioInput.value = '';
                        if (videoInput) videoInput.value = '';
                        hidePanels();
                        refreshChatMessages(true);
                        if (typeof getNotificationCounts === 'function') {
                            getNotificationCounts();
                        }
                        if (onDone) onDone(true);
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON?.message || 'Something went wrong');
                        if (onDone) onDone(false);
                    },
                    complete: function () {
                        sendBtn.disabled = false;
                    }
                });
            }

            async function sendRemoteGif(url, type) {
                try {
                    const res = await fetch(url);
                    const blob = await res.blob();
                    const ext = type === 'sticker' ? 'webp' : 'gif';
                    const file = new File([blob], type + '-' + Date.now() + '.' + ext, { type: blob.type || 'image/gif' });
                    const formData = new FormData();
                    formData.append('_token', csrfToken);
                    formData.append('message_type', type);
                    setFormDataFile(formData, 'chat_image', file);
                    postChat(formData);
                } catch (e) {
                    alert('Failed to send GIF/Sticker');
                }
            }

            function stopRecording(send) {
                if (recordTimer) {
                    clearInterval(recordTimer);
                    recordTimer = null;
                }
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.onstop = function () {
                        recordedBlob = recordedChunks.length
                            ? new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'audio/webm' })
                            : null;
                        recordedChunks = [];
                        if (send && recordedBlob && recordedBlob.size > 0) {
                            const file = new File([recordedBlob], 'voice-' + Date.now() + '.webm', {
                                type: recordedBlob.type || 'audio/webm',
                            });
                            const formData = new FormData();
                            formData.append('_token', csrfToken);
                            formData.append('message_type', 'audio');
                            setFormDataFile(formData, 'chat_media', file);
                            postChat(formData, function () {
                                exitVoiceMode();
                            });
                        } else {
                            exitVoiceMode();
                        }
                    };
                    mediaRecorder.stop();
                    mediaRecorder.stream.getTracks().forEach(function (t) { t.stop(); });
                } else {
                    exitVoiceMode();
                }
            }

            function exitVoiceMode() {
                chatForm.classList.remove('is-recording');
                voiceBar.classList.add('d-none');
                composerInner.classList.remove('d-none');
                voiceTimer.textContent = '00:00';
                recordedBlob = null;
                recordedChunks = [];
                mediaRecorder = null;
                updateComposerMode();
            }

            async function startRecording() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Microphone not supported in this browser');
                    return;
                }
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    recordedChunks = [];
                    const mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                        ? 'audio/webm;codecs=opus'
                        : (MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '');
                    mediaRecorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
                    mediaRecorder.ondataavailable = function (e) {
                        if (e.data && e.data.size > 0) recordedChunks.push(e.data);
                    };
                    mediaRecorder.start(200);
                    recordStartedAt = Date.now();
                    voiceTimer.textContent = '00:00';
                    chatForm.classList.add('is-recording');
                    voiceBar.classList.remove('d-none');
                    composerInner.classList.add('d-none');
                    hidePanels();
                    recordTimer = setInterval(function () {
                        const sec = Math.floor((Date.now() - recordStartedAt) / 1000);
                        voiceTimer.textContent = formatClock(sec);
                    }, 250);
                } catch (e) {
                    alert('Microphone permission denied');
                }
            }

            attachBtn?.addEventListener('click', function (e) {
                e.stopPropagation();
                gifPanel.classList.add('d-none');
                attachMenu.classList.toggle('d-none');
            });

            attachMenu?.querySelectorAll('[data-attach]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const action = btn.getAttribute('data-attach');
                    attachMenu.classList.add('d-none');
                    if (action === 'gallery') {
                        messageTypeInput.value = 'image';
                        imageInput.click();
                    } else if (action === 'gif') {
                        activeGifTab = 'gif';
                        document.querySelectorAll('.pds-support-gif-tab').forEach(function (tab) {
                            tab.classList.toggle('is-active', tab.getAttribute('data-gif-tab') === 'gif');
                        });
                        renderGifGrid();
                        gifPanel.classList.remove('d-none');
                    } else if (action === 'audio') {
                        audioInput.click();
                    } else if (action === 'video') {
                        videoInput.click();
                    }
                });
            });

            document.querySelectorAll('.pds-support-gif-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activeGifTab = tab.getAttribute('data-gif-tab') || 'gif';
                    document.querySelectorAll('.pds-support-gif-tab').forEach(function (t) {
                        t.classList.toggle('is-active', t === tab);
                    });
                    renderGifGrid();
                });
            });

            gifClose?.addEventListener('click', function () {
                gifPanel.classList.add('d-none');
            });

            gifGrid?.addEventListener('click', function (e) {
                const btn = e.target.closest('.pds-support-gif-item');
                if (!btn) return;
                sendRemoteGif(btn.getAttribute('data-url'), btn.getAttribute('data-type') || 'gif');
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
                if (messageTypeInput.value === 'text') messageTypeInput.value = 'image';
                messageInput.focus();
                updateComposerMode();
            });

            audioInput?.addEventListener('change', function () {
                if (!audioInput.files.length) return;
                sendMediaFile(audioInput.files[0], 'audio');
            });

            videoInput?.addEventListener('change', function () {
                if (!videoInput.files.length) return;
                sendMediaFile(videoInput.files[0], 'video');
            });

            imageRemove?.addEventListener('click', function () {
                clearImagePreview();
                updateComposerMode();
            });

            micBtn?.addEventListener('click', startRecording);
            voiceCancel?.addEventListener('click', function () { stopRecording(false); });
            voiceSend?.addEventListener('click', function () { stopRecording(true); });

            function onPasteMedia(event) {
                const clip = event.clipboardData;
                const files = collectClipboardFiles(clip).filter(function (f) {
                    return !!classifyMediaFile(f);
                });
                if (files.length) {
                    event.preventDefault();
                    event.stopPropagation();
                    hidePanels();
                    handleIncomingFiles(files);
                    return;
                }

                const text = (clip && clip.getData('text/plain')) ? clip.getData('text/plain').trim() : '';
                const html = (clip && clip.getData('text/html')) ? clip.getData('text/html') : '';
                const hasImageItem = clip && Array.from(clip.items || []).some(function (item) {
                    return String(item.type || '').startsWith('image/') ||
                        String(item.type || '').startsWith('audio/') ||
                        String(item.type || '').startsWith('video/');
                });

                if (hasImageItem || looksLikeMediaFileName(text) || /<img\s/i.test(html)) {
                    event.preventDefault();
                    event.stopPropagation();
                    hidePanels();
                    readClipboardMediaAsync().then(function (ok) {
                        if (ok) return;
                        if (/<img\s[^>]*src=["'](data:image\/[^"']+)["']/i.test(html)) {
                            const match = html.match(/<img\s[^>]*src=["'](data:image\/[^"']+)["']/i);
                            if (match && match[1]) {
                                fetch(match[1]).then(function (res) { return res.blob(); }).then(function (blob) {
                                    handleIncomingFiles([fileFromBlob(blob)]);
                                }).catch(function () {});
                                return;
                            }
                        }
                        if (looksLikeMediaFileName(text)) {
                            alert('Copy/Paste file မရပါ။ + Attach သို့မဟုတ် chat ပေါ် drag & drop လုပ်ပါ။');
                        }
                    });
                }
            }

            const pasteTargets = [messageInput, chatBody, chatForm, document.getElementById('supportChatComposerInner')];
            pasteTargets.forEach(function (el) {
                el?.addEventListener('paste', onPasteMedia, true);
            });
            document.addEventListener('paste', function (event) {
                const panel = document.querySelector('.pds-support-chat-card, .pds-messenger-panel');
                if (!panel) return;
                const active = document.activeElement;
                if (active && panel.contains(active)) onPasteMedia(event);
            }, true);

            ['dragenter', 'dragover'].forEach(function (evtName) {
                chatBody?.addEventListener(evtName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    chatBody.classList.add('is-drag-over');
                });
            });
            ['dragleave', 'drop'].forEach(function (evtName) {
                chatBody?.addEventListener(evtName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    chatBody.classList.remove('is-drag-over');
                });
            });
            chatBody?.addEventListener('drop', function (e) {
                const files = e.dataTransfer && e.dataTransfer.files;
                if (!files || !files.length) return;
                hidePanels();
                handleIncomingFiles(files);
            });

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

            document.addEventListener('click', function (e) {
                if (!attachMenu || !attachBtn) return;
                if (!attachMenu.contains(e.target) && e.target !== attachBtn && !attachBtn.contains(e.target)) {
                    attachMenu.classList.add('d-none');
                }
            });

            chatForm?.addEventListener('submit', function (event) {
                event.preventDefault();
                const message = messageInput.value.trim();
                const imageFile = (imageInput.files && imageInput.files[0]) || pendingImageFile;
                const hasImage = !!imageFile;
                if (!message && !hasImage) return;

                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('message_type', hasImage ? (messageTypeInput.value || 'image') : 'text');
                if (message) formData.append('message', message);
                if (hasImage) formData.append('chat_image', imageFile);
                postChat(formData);
            });

            // Seed voice wave bars
            if (voiceWave) {
                voiceWave.innerHTML = Array.from({ length: 26 }).map(function () {
                    return '<span></span>';
                }).join('');
            }

            function ensureChatLightbox() {
                let box = document.getElementById('pdsChatImageLightbox');
                if (box) return box;
                box = document.createElement('div');
                box.id = 'pdsChatImageLightbox';
                box.className = 'pds-chat-lightbox';
                box.setAttribute('role', 'dialog');
                box.setAttribute('aria-modal', 'true');
                box.setAttribute('aria-label', 'Image preview');
                box.innerHTML = `
                    <div class="pds-chat-lightbox__shell">
                        <button type="button" class="pds-chat-lightbox__close" aria-label="Close"><i class="fas fa-times"></i></button>
                        <div class="pds-chat-lightbox__stage">
                            <img class="pds-chat-lightbox__img" alt="Chat image">
                        </div>
                        <a class="pds-chat-lightbox__open" href="#" target="_blank" rel="noopener">Open original</a>
                    </div>`;
                document.body.appendChild(box);

                function closeLightbox() {
                    box.classList.remove('is-open');
                    document.body.classList.remove('pds-chat-lightbox-open');
                    const img = box.querySelector('.pds-chat-lightbox__img');
                    if (img) {
                        img.classList.remove('is-ready');
                        img.removeAttribute('src');
                    }
                }

                box.querySelector('.pds-chat-lightbox__close')?.addEventListener('click', closeLightbox);
                box.addEventListener('click', function (e) {
                    if (e.target === box) closeLightbox();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && box.classList.contains('is-open')) closeLightbox();
                });
                return box;
            }

            function openChatLightbox(url) {
                if (!url) return;
                const box = ensureChatLightbox();
                const img = box.querySelector('.pds-chat-lightbox__img');
                const openLink = box.querySelector('.pds-chat-lightbox__open');
                img.classList.remove('is-ready');
                img.onload = function () { img.classList.add('is-ready'); };
                img.src = url;
                if (openLink) openLink.href = url;
                box.classList.add('is-open');
                document.body.classList.add('pds-chat-lightbox-open');
            }

            chatBody?.addEventListener('click', function (e) {
                const btn = e.target.closest('.pds-messenger-image-link');
                if (!btn || !chatBody.contains(btn)) return;
                e.preventDefault();
                const url = btn.getAttribute('data-full-src') || btn.querySelector('img')?.getAttribute('src');
                openChatLightbox(url);
            });

            updateComposerMode();
            renderGifGrid();
            if (Array.isArray(initialMessages) && initialMessages.length) {
                renderMessages(initialMessages, true);
            }
            refreshChatMessages(true);
            startPolling();
            autoResizeTextarea();
            }); // withJquery
        })();
    </script>
</x-master-layout>

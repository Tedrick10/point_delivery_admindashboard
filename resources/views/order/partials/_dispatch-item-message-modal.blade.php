<div class="modal fade" id="dispatchItemMessageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content pds-item-chat-modal">
            <div class="modal-header pds-item-chat-modal__header">
                <div>
                    <h5 class="modal-title mb-0">
                        <i class="fab fa-facebook-messenger" aria-hidden="true"></i>
                        {{ __('message.chat') }}
                        <small class="text-muted" id="dispatchItemMessageCustomer"></small>
                    </h5>
                    <div class="pds-item-chat-modal__meta" id="dispatchItemMessageMeta"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pds-item-chat-modal__body">
                <div id="dispatchItemMessageList" class="pds-item-msg-list"></div>

                <div id="dispatchItemMessageImagePreview" class="pds-item-chat-image-preview d-none">
                    <button type="button" class="pds-item-chat-image-remove" id="dispatchItemMessageImageRemove" aria-label="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                    <img id="dispatchItemMessageImageThumb" src="" alt="preview">
                    <span id="dispatchItemMessageImageName" class="pds-item-chat-image-name"></span>
                </div>

                <div class="pds-item-chat-compose">
                    <div class="dropdown pds-item-chat-emoji-wrap">
                        <button type="button"
                                class="pds-item-chat-icon-btn"
                                id="dispatchItemMessageEmojiBtn"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                                title="Emoji">
                            <i class="far fa-smile"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-left pds-item-chat-emoji-menu">
                            <emoji-picker data-target-input="#dispatchItemMessageInput"></emoji-picker>
                        </div>
                    </div>

                    <button type="button" class="pds-item-chat-icon-btn" id="dispatchItemMessageImageBtn" title="Image">
                        <i class="far fa-image"></i>
                    </button>
                    <input type="file" id="dispatchItemMessageImage" accept="image/*" class="d-none">

                    <textarea
                        id="dispatchItemMessageInput"
                        class="form-control"
                        rows="2"
                        placeholder="{{ __('message.type_message') }}"
                    ></textarea>
                    <button type="button" class="btn btn-primary pds-item-chat-send" id="dispatchItemMessageSend" title="{{ __('message.send') }}">
                        <i class="fas fa-paper-plane"></i>
                        <span>{{ __('message.send') }}</span>
                    </button>
                </div>
                <div class="pds-item-chat-hint">Enter = send · Shift+Enter = new line · Emoji / Image supported</div>
            </div>
        </div>
    </div>
</div>

<style>
    .pds-item-chat-modal__header {
        align-items: flex-start;
        border-bottom: 1px solid #e5e7eb;
    }
    .pds-item-chat-modal__header .modal-title {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 700;
    }
    .pds-item-chat-modal__meta {
        margin-top: 0.25rem;
        font-size: 0.78rem;
        color: #64748b;
    }
    .pds-item-chat-modal__body {
        padding: 0.85rem 1rem 1rem;
    }
    .pds-item-msg-list {
        height: 340px;
        overflow: auto;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .pds-item-msg-empty {
        color: #94a3b8;
        text-align: center;
        margin: 4rem 0 0;
        font-size: 0.9rem;
    }
    .pds-item-msg {
        display: flex;
        margin-bottom: 0.65rem;
    }
    .pds-item-msg--admin { justify-content: flex-end; }
    .pds-item-msg--client { justify-content: flex-start; }
    .pds-item-msg__bubble {
        max-width: 78%;
        padding: 0.55rem 0.75rem;
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
    }
    .pds-item-msg--admin .pds-item-msg__bubble {
        background: #fff4eb;
        border-bottom-right-radius: 4px;
    }
    .pds-item-msg--client .pds-item-msg__bubble {
        background: #eef2ff;
        border-bottom-left-radius: 4px;
    }
    .pds-item-msg__meta {
        font-size: 0.7rem;
        color: #64748b;
        margin-bottom: 0.2rem;
    }
    .pds-item-msg__text {
        color: #0f172a;
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.4;
    }
    .pds-item-msg__image {
        display: block;
        max-width: 220px;
        max-height: 220px;
        border-radius: 10px;
        margin-top: 0.25rem;
        object-fit: cover;
    }
    .pds-item-chat-compose {
        display: flex;
        gap: 0.4rem;
        align-items: flex-end;
    }
    .pds-item-chat-compose textarea {
        flex: 1;
        resize: vertical;
        min-height: 44px;
        max-height: 120px;
    }
    .pds-item-chat-icon-btn {
        width: 40px;
        height: 40px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .pds-item-chat-icon-btn:hover {
        background: #f8fafc;
        color: #f97316;
    }
    .pds-item-chat-emoji-menu {
        padding: 0.25rem;
        border-radius: 12px;
        overflow: hidden;
    }
    .pds-item-chat-emoji-menu emoji-picker {
        --num-columns: 8;
        width: 320px;
        height: 280px;
    }
    .pds-item-chat-send {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        white-space: nowrap;
        background: #f97316;
        border-color: #ea580c;
    }
    .pds-item-chat-send:hover {
        background: #ea580c;
        border-color: #c2410c;
    }
    .pds-item-chat-hint {
        margin-top: 0.35rem;
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .pds-item-chat-image-preview {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
        padding: 0.55rem 0.75rem;
        border: 1px dashed #fdba74;
        border-radius: 10px;
        background: #fff7ed;
    }
    .pds-item-chat-image-preview img {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 8px;
    }
    .pds-item-chat-image-name {
        font-size: 0.8rem;
        color: #9a3412;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .pds-item-chat-image-remove {
        position: absolute;
        top: 6px;
        right: 6px;
        border: 0;
        background: #fff;
        color: #ef4444;
        width: 24px;
        height: 24px;
        border-radius: 999px;
        box-shadow: 0 1px 3px rgba(0,0,0,.12);
    }
    .pds-dispatch-action-message {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        color: #0b5fff;
        background: rgba(11, 95, 255, 0.08);
    }
    .pds-dispatch-msg-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        line-height: 16px;
        text-align: center;
        font-weight: 700;
    }
</style>

<div class="modal fade" id="dispatchItemMessageModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content pds-item-chat-modal">
            <div class="pds-item-chat-modal__header">
                <div class="pds-item-chat-modal__heading">
                    <span class="pds-item-chat-modal__icon" aria-hidden="true">
                        <i class="fas fa-comments"></i>
                    </span>
                    <div class="pds-item-chat-modal__titles">
                        <h5 class="pds-item-chat-modal__title">
                            {{ __('message.chat') }}
                            <span class="pds-item-chat-modal__customer" id="dispatchItemMessageCustomer"></span>
                        </h5>
                        <div class="pds-item-chat-modal__meta" id="dispatchItemMessageMeta"></div>
                    </div>
                </div>
                <button type="button" class="pds-item-chat-modal__close" data-dismiss="modal" aria-label="{{ __('message.close') }}">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="pds-item-chat-modal__body">
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
                        class="pds-item-chat-input"
                        rows="1"
                        placeholder="{{ __('message.type_message') }}"
                    ></textarea>
                    <button type="button" class="pds-item-chat-send" id="dispatchItemMessageSend" title="{{ __('message.send') }}">
                        <i class="fas fa-paper-plane"></i>
                        <span>{{ __('message.send') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body.pds-admin .pds-item-chat-modal,
    body.pds-admin .pds-item-chat-modal h5,
    body.pds-admin .pds-item-chat-modal button,
    body.pds-admin .pds-item-chat-modal textarea,
    body.pds-admin .pds-item-chat-modal span,
    body.pds-admin .pds-item-chat-modal div {
        font-family: 'Outfit', 'Noto Sans Myanmar', system-ui, sans-serif !important;
    }
    body.pds-admin .pds-item-chat-modal {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 56px rgba(28, 25, 23, 0.16);
    }
    body.pds-admin .pds-item-chat-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.15rem;
        background: linear-gradient(180deg, #FFF8F1 0%, #fff 100%);
        border-bottom: 1px solid rgba(254, 111, 7, 0.12);
    }
    body.pds-admin .pds-item-chat-modal__heading {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }
    body.pds-admin .pds-item-chat-modal__icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        background: #FE6F07;
        color: #fff;
        font-size: 1rem;
        box-shadow: 0 6px 14px rgba(254, 111, 7, 0.28);
    }
    body.pds-admin .pds-item-chat-modal__titles {
        min-width: 0;
    }
    body.pds-admin .pds-item-chat-modal__title {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.35rem;
        font-size: 1rem;
        font-weight: 750;
        color: #1c1917 !important;
        letter-spacing: -0.01em;
    }
    body.pds-admin .pds-item-chat-modal__customer {
        color: #57534e !important;
        font-weight: 650;
        font-size: 0.92rem;
    }
    body.pds-admin .pds-item-chat-modal__meta {
        margin-top: 0.15rem;
        font-size: 0.76rem;
        color: #a8a29e;
        font-weight: 600;
    }
    body.pds-admin .pds-item-chat-modal__close {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 999px;
        background: rgba(28, 25, 23, 0.05);
        color: #78716c;
        display: grid;
        place-items: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    body.pds-admin .pds-item-chat-modal__close:hover {
        background: rgba(254, 111, 7, 0.12);
        color: #c2410c;
    }
    body.pds-admin .pds-item-chat-modal__body {
        padding: 0.95rem 1.05rem 1.1rem;
        background: #fff;
    }
    body.pds-admin .pds-item-msg-list {
        height: 360px;
        overflow: auto;
        padding: 0.85rem;
        margin-bottom: 0.85rem;
        background: #fafaf9;
        border: 1px solid #f0ebe6;
        border-radius: 14px;
    }
    body.pds-admin .pds-item-msg-empty {
        color: #a8a29e;
        text-align: center;
        margin: 5rem 0 0;
        font-size: 0.9rem;
        font-weight: 600;
    }
    body.pds-admin .pds-item-msg {
        display: flex;
        margin-bottom: 0.75rem;
        align-items: flex-end;
        gap: 0.55rem;
    }
    body.pds-admin .pds-item-msg--admin { justify-content: flex-end; }
    body.pds-admin .pds-item-msg--client { justify-content: flex-start; }
    body.pds-admin .pds-item-msg__avatar {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        object-fit: cover;
        flex-shrink: 0;
        background: linear-gradient(135deg, #FE6F07 0%, #F59E0B 100%);
    }
    body.pds-admin .pds-item-msg__avatar--letter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 800;
        font-size: 0.82rem;
    }
    body.pds-admin .pds-item-msg__bubble {
        max-width: 72%;
        padding: 0.65rem 0.85rem;
        border-radius: 16px;
        box-shadow: none;
    }
    body.pds-admin .pds-item-msg--admin .pds-item-msg__bubble {
        background: #FE6F07;
        color: #fff;
        border-bottom-right-radius: 5px;
    }
    body.pds-admin .pds-item-msg--admin .pds-item-msg__meta,
    body.pds-admin .pds-item-msg--admin .pds-item-msg__text {
        color: #fff;
    }
    body.pds-admin .pds-item-msg--admin .pds-item-msg__meta {
        opacity: 0.85;
    }
    body.pds-admin .pds-item-msg--client .pds-item-msg__bubble {
        background: #fff;
        border: 1px solid #f0ebe6;
        border-bottom-left-radius: 5px;
    }
    body.pds-admin .pds-item-msg__meta {
        font-size: 0.7rem;
        color: #a8a29e;
        margin-bottom: 0.2rem;
        font-weight: 600;
    }
    body.pds-admin .pds-item-msg__text {
        color: #1c1917;
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.45;
        font-size: 0.9rem;
        font-weight: 500;
    }
    body.pds-admin .pds-item-msg__image {
        display: block;
        max-width: 220px;
        max-height: 220px;
        border-radius: 10px;
        margin-top: 0.35rem;
        object-fit: cover;
        background: transparent;
    }
    body.pds-admin .pds-item-chat-compose {
        display: grid;
        grid-template-columns: auto auto minmax(0, 1fr) auto;
        gap: 0.45rem;
        align-items: end;
        width: 100%;
    }
    body.pds-admin .pds-item-chat-input {
        width: 100%;
        min-height: 44px;
        max-height: 120px;
        resize: none;
        padding: 0.7rem 0.9rem;
        border: 1px solid #e7e5e4;
        border-radius: 12px;
        background: #FFFCF8;
        color: #1c1917;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1.4;
    }
    body.pds-admin .pds-item-chat-input:focus {
        outline: none;
        border-color: #FE6F07;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(254, 111, 7, 0.12);
    }
    body.pds-admin .pds-item-chat-icon-btn {
        width: 44px;
        height: 44px;
        border: 1px solid #e7e5e4;
        border-radius: 12px;
        background: #fff;
        color: #57534e;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        cursor: pointer;
        transition: border-color 0.15s ease, color 0.15s ease, background 0.15s ease;
    }
    body.pds-admin .pds-item-chat-icon-btn:hover {
        background: #FFF7ED;
        border-color: #fdba74;
        color: #FE6F07;
    }
    body.pds-admin .pds-item-chat-emoji-menu {
        padding: 0.35rem;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(254, 111, 7, 0.16);
        box-shadow: 0 16px 40px rgba(28, 25, 23, 0.14);
        background: #fff;
    }
    body.pds-admin .pds-item-chat-emoji-menu emoji-picker {
        --num-columns: 8;
        --background: #FFFCF8;
        --border-color: #f0ebe6;
        --border-radius: 12px;
        --button-active-background: #FFEDD5;
        --button-hover-background: #FFF7ED;
        --category-emoji-padding: 0.4rem;
        --category-font-color: #44403c;
        --indicator-color: #FE6F07;
        --indicator-height: 3px;
        --input-border-color: #e7e5e4;
        --input-border-radius: 10px;
        --input-font-color: #1c1917;
        --input-placeholder-color: #a8a29e;
        --outline-color: #FE6F07;
        width: 320px;
        height: 280px;
    }
    body.pds-admin .pds-item-chat-send {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        min-height: 44px;
        padding: 0 1.1rem;
        border: 0;
        border-radius: 12px;
        white-space: nowrap;
        background: #FE6F07;
        color: #fff;
        font-weight: 750;
        font-size: 0.88rem;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(254, 111, 7, 0.28);
        transition: background 0.15s ease, transform 0.12s ease;
    }
    body.pds-admin .pds-item-chat-send:hover {
        background: #e86306;
        color: #fff;
        transform: translateY(-1px);
    }
    body.pds-admin .pds-item-chat-image-preview {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.65rem;
        padding: 0.65rem 0.85rem;
        border: 1px solid rgba(254, 111, 7, 0.2);
        border-radius: 12px;
        background: #FFF8F1;
    }
    body.pds-admin .pds-item-chat-image-preview img {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 10px;
        background: transparent;
    }
    body.pds-admin .pds-item-chat-image-name {
        font-size: 0.8rem;
        color: #9a3412;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 600;
    }
    body.pds-admin .pds-item-chat-image-remove {
        position: absolute;
        top: 6px;
        right: 6px;
        border: 0;
        background: #FE6F07;
        color: #fff;
        width: 24px;
        height: 24px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(254, 111, 7, 0.28);
    }
</style>

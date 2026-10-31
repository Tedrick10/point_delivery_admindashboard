<x-master-layout :assets="$assets ?? []">

<style>
/* Photo gallery — also used inside edit modal (often moved outside .iar-page) */
.iar-page .iar-photos,
.iar-edit-modal .iar-photos {
    display: flex; flex-wrap: wrap; gap: .65rem; align-items: stretch;
}
.iar-page .iar-ph,
.iar-edit-modal .iar-ph {
    width: 5.5rem; height: 5.5rem; border-radius: 14px; overflow: hidden;
    position: relative; border: 1px solid #e2e8f0; background: #fff;
    box-shadow: 0 4px 12px rgba(15,23,42,.06);
}
.iar-page .iar-ph img,
.iar-edit-modal .iar-ph img { width: 100%; height: 100%; object-fit: cover; display: block; }
.iar-page .iar-ph button,
.iar-edit-modal .iar-ph button {
    position: absolute; top: .35rem; right: .35rem; width: 1.35rem; height: 1.35rem;
    border: 0; border-radius: 999px; background: rgba(15,23,42,.7); color: #fff;
    font-size: .75rem; line-height: 1; cursor: pointer;
}
.iar-page .iar-ph-add,
.iar-edit-modal .iar-ph-add {
    flex: 1 1 100%;
    min-height: 7.5rem;
    border-radius: 16px;
    border: 1.5px dashed rgba(var(--brand-rgb), .4);
    background:
        radial-gradient(circle at 18% 20%, rgba(var(--brand-rgb), .12), transparent 55%),
        linear-gradient(180deg, #fffaf5 0%, #fff 100%);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .35rem; color: #334155; cursor: pointer; position: relative;
    transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
}
.iar-page .iar-ph-add.has-files,
.iar-edit-modal .iar-ph-add.has-files {
    flex: 0 0 5.5rem;
    width: 5.5rem;
    min-height: 5.5rem;
    gap: .15rem;
}
.iar-page .iar-ph-add.has-files .iar-ph-add__title,
.iar-edit-modal .iar-ph-add.has-files .iar-ph-add__title { font-size: .72rem; }
.iar-page .iar-ph-add.has-files .iar-ph-add__hint,
.iar-edit-modal .iar-ph-add.has-files .iar-ph-add__hint { display: none; }
.iar-page .iar-ph-add.has-files i,
.iar-edit-modal .iar-ph-add.has-files i { font-size: 1.1rem; margin-bottom: 0; }
.iar-page .iar-ph-add:hover,
.iar-page .iar-ph-add.is-dragover,
.iar-edit-modal .iar-ph-add:hover,
.iar-edit-modal .iar-ph-add.is-dragover {
    border-color: var(--site-color);
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(var(--brand-rgb), .12);
}
.iar-page .iar-ph-add.is-dragover,
.iar-edit-modal .iar-ph-add.is-dragover {
    background: rgba(var(--brand-rgb), .14);
}
.iar-page .iar-ph-add i,
.iar-edit-modal .iar-ph-add i {
    width: 2.5rem; height: 2.5rem; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(var(--brand-rgb), .12); color: var(--site-color);
    font-size: 1.05rem; margin-bottom: .15rem;
}
.iar-page .iar-ph-add__title,
.iar-edit-modal .iar-ph-add__title {
    font-size: .9rem; font-weight: 800; color: #0f172a; letter-spacing: -.01em;
}
.iar-page .iar-ph-add__hint,
.iar-edit-modal .iar-ph-add__hint {
    font-size: .75rem; color: #64748b; font-weight: 500;
}
.iar-page .iar-ph-add input,
.iar-edit-modal .iar-ph-add input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.iar-page .iar-unit {
    position: relative;
    padding: 1rem;
    border: 1px solid #eef2f7;
    border-radius: 14px;
    background: #f8fafc;
    margin-bottom: .85rem;
}
.iar-page .iar-unit:last-of-type { margin-bottom: 0; }
.iar-page .iar-unit .iar-rm {
    position: absolute; top: .5rem; right: .5rem; width: 1.75rem; height: 1.75rem;
    border: 0; border-radius: 8px; background: #fff; color: #94a3b8; cursor: pointer;
    box-shadow: 0 1px 4px rgba(15,23,42,.06);
}
.iar-page .iar-unit .iar-rm:hover { color: #dc2626; }
/* Kill the outer white panel behind cards */
.iar-page.pds-dispatch-to-assign-page .pds-dispatch-to-assign-screen,
.iar-page .pds-dispatch-to-assign-screen,
.iar-page .pds-rider-screen {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    overflow: visible !important;
}
.iar-page .pds-form-card { border-radius: 18px; overflow: hidden; }
.iar-page .pds-form-card .card-header {
    background: #fff;
    border-bottom: 1px solid #f1e7dc;
}
.iar-page .iar-compose-toggle {
    width: 100%;
    border: 0;
    background: transparent;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0;
}
.iar-page .iar-compose-toggle:focus { outline: none; }
.iar-page .iar-compose-chevron {
    width: 2rem; height: 2rem; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    background: rgba(var(--brand-rgb), .08); color: var(--site-color);
    transition: transform .2s ease;
    flex-shrink: 0;
}
.iar-page .pds-form-card.is-collapsed .iar-compose-chevron { transform: rotate(-90deg); }
.iar-page .pds-form-card.is-collapsed .card-header { border-bottom: 0; }
.iar-page .pds-form-card.is-collapsed .card-body { display: none; }
.iar-page .modal-content { border: 0; border-radius: 16px; overflow: hidden; }

/* Edit popup */
.iar-edit-modal .modal-dialog { max-width: 560px; }
.iar-edit-modal .modal-content {
    border: 0; border-radius: 20px; overflow: hidden;
    box-shadow: 0 24px 64px rgba(15, 23, 42, .2);
}
.iar-edit-modal .modal-header {
    border-bottom: 1px solid #f1e7dc;
    padding: 1rem 1.25rem;
    background: #fff;
    align-items: center;
}
.iar-edit-modal .modal-title {
    font-weight: 800; font-size: 1.05rem; color: #0f172a; letter-spacing: -.02em;
}
.iar-edit-modal .modal-header .close {
    padding: .35rem; margin: 0; opacity: 1;
    width: 2rem; height: 2rem; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    background: #f8fafc; color: #64748b; font-size: 1.1rem;
}
.iar-edit-modal .modal-header .close:hover { background: #f1f5f9; color: #0f172a; }
.iar-edit-modal .modal-body {
    padding: 1.15rem 1.25rem 1rem;
    background: #fafaf9;
}
.iar-edit-modal .iar-edit-panel {
    background: #fff;
    border: 1px solid #e8e2db;
    border-radius: 16px;
    padding: 1rem 1.05rem;
}
.iar-edit-modal .form-control {
    background: #fff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 12px !important;
    box-shadow: none !important;
}
.iar-edit-modal .form-control:focus {
    border-color: rgba(254,111,7,.55) !important;
    box-shadow: 0 0 0 3px rgba(254,111,7,.12) !important;
}
.iar-edit-modal .modal-footer {
    border-top: 1px solid #f1e7dc;
    background: #fff;
    padding: .85rem 1.25rem;
    display: flex; align-items: center; justify-content: space-between;
}
.iar-edit-modal .iar-edit-actions {
    display: flex; align-items: center; gap: .45rem;
}
.iar-edit-modal .btn-icon {
    width: 2.4rem; height: 2.4rem; padding: 0; border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #e2e8f0; background: #fff; color: #64748b;
    box-shadow: none !important;
    transition: background .15s, border-color .15s, color .15s;
}
.iar-edit-modal .btn-icon:hover { transform: none; }
.iar-edit-modal .btn-icon--del { color: #dc2626; border-color: #fecaca; }
.iar-edit-modal .btn-icon--del:hover { background: #fef2f2; border-color: #fca5a5; }
.iar-edit-modal .btn-icon--cancel:hover { background: #f8fafc; color: #0f172a; }
.iar-edit-modal .btn-icon--save {
    background: var(--site-color, #FE6F07); border-color: var(--site-color, #FE6F07); color: #fff !important;
}
.iar-edit-modal .btn-icon--save:hover {
    filter: brightness(1.05); color: #fff !important;
}
.iar-edit-modal .btn-primary { box-shadow: none !important; }
.iar-edit-modal .iar-active-toggle {
    display: inline-flex; align-items: center; gap: .65rem;
    user-select: none; cursor: pointer; margin: 0;
}
.iar-edit-modal .iar-active-toggle input {
    position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;
}
.iar-edit-modal .iar-active-toggle__ui {
    position: relative;
    width: 2.75rem; height: 1.55rem;
    border-radius: 999px;
    background: #e2e8f0;
    border: 1px solid #cbd5e1;
    transition: background .2s ease, border-color .2s ease;
    flex-shrink: 0;
}
.iar-edit-modal .iar-active-toggle__ui::after {
    content: '';
    position: absolute; top: 2px; left: 2px;
    width: 1.15rem; height: 1.15rem;
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(15,23,42,.2);
    transition: transform .2s ease;
}
.iar-edit-modal .iar-active-toggle input:checked + .iar-active-toggle__ui {
    background: var(--site-color, #FE6F07);
    border-color: var(--site-color, #FE6F07);
}
.iar-edit-modal .iar-active-toggle input:checked + .iar-active-toggle__ui::after {
    transform: translateX(1.15rem);
}
.iar-edit-modal .iar-active-toggle__text {
    font-size: .88rem; font-weight: 700; color: #64748b;
}
.iar-edit-modal .iar-active-toggle input:checked ~ .iar-active-toggle__text {
    color: #0f172a;
}
.iar-edit-modal .iar-active-toggle__text .on { display: none; }
.iar-edit-modal .iar-active-toggle__text .off { display: inline; }
.iar-edit-modal .iar-active-toggle input:checked ~ .iar-active-toggle__text .on { display: inline; }
.iar-edit-modal .iar-active-toggle input:checked ~ .iar-active-toggle__text .off { display: none; }

/* Delete popup — outside .iar-page (Bootstrap moves modals), so unscoped */
.iar-delete-modal .modal-dialog { max-width: 380px; }
.iar-delete-modal .modal-content {
    border: 0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 64px rgba(15, 23, 42, .22);
}
.iar-delete-modal .modal-body {
    text-align: center;
    padding: 1.75rem 1.5rem 1rem;
    background: linear-gradient(180deg, #fff5f5 0%, #fff 42%);
}
.iar-delete-modal .iar-delete-icon {
    width: 3.75rem; height: 3.75rem; margin: 0 auto 1rem;
    border-radius: 999px;
    background: linear-gradient(180deg, #fecaca, #fee2e2);
    color: #dc2626;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem;
    box-shadow: 0 8px 20px rgba(220, 38, 38, .18);
}
.iar-delete-modal h5 {
    font-weight: 800; font-size: 1.15rem; color: #0f172a;
    margin: 0 0 .45rem; letter-spacing: -.02em;
}
.iar-delete-modal p {
    color: #64748b; margin: 0 auto; font-size: .92rem; line-height: 1.5;
    max-width: 28ch;
}
.iar-delete-modal .modal-footer {
    border-top: 0;
    justify-content: center;
    gap: .55rem;
    padding: .85rem 1.5rem 1.5rem;
    background: #fff;
}
.iar-delete-modal .btn-cancel {
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-weight: 700;
    border-radius: 999px;
    padding: .55rem 1.25rem;
    min-width: 6rem;
}
.iar-delete-modal .btn-cancel:hover { background: #f8fafc; color: #0f172a; }
.iar-delete-modal .btn-delete {
    border: 0;
    background: linear-gradient(180deg, #f87171, #dc2626);
    color: #fff !important;
    font-weight: 800;
    border-radius: 999px;
    padding: .55rem 1.35rem;
    min-width: 6rem;
    box-shadow: 0 8px 18px rgba(220, 38, 38, .28);
}
.iar-delete-modal .btn-delete:hover {
    filter: brightness(1.04);
    color: #fff !important;
}

/* Saved reply cards */
.iar-page .iar-card-list {
    display: flex;
    flex-direction: column;
    gap: .75rem;
}
.iar-page .iar-card {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: .85rem;
    align-items: start;
    padding: 1rem 1.1rem 1.15rem 1rem;
    margin-bottom: 0;
    background: #fff;
    border: 1px solid rgba(15, 23, 42, .08);
    border-left: 4px solid var(--site-color);
    border-radius: 18px;
    box-shadow: 0 1px 2px rgba(15,23,42,.03), 0 10px 28px rgba(15,23,42,.04);
    overflow: visible;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    cursor: grab;
    touch-action: none;
    user-select: none;
}
.iar-page .iar-card:active { cursor: grabbing; }
.iar-page .iar-card.is-dragging {
    opacity: .92;
    box-shadow: 0 18px 40px rgba(var(--brand-rgb), .18);
    border-color: rgba(var(--brand-rgb), .4);
    z-index: 5;
}
.iar-page .iar-card.sortable-ghost {
    opacity: .35;
}
.iar-page .iar-card.sortable-chosen {
    border-color: rgba(var(--brand-rgb), .45);
}
.iar-page .iar-card:hover {
    transform: translateY(-2px);
    border-color: rgba(var(--brand-rgb), .28);
    border-left-color: var(--site-color);
    box-shadow: 0 14px 36px rgba(var(--brand-rgb), .1);
}
.iar-page .iar-card.is-off { border-left-color: #cbd5e1; }
.iar-page .iar-card__media,
.iar-page .iar-card__action {
    align-self: center;
}
.iar-page .iar-card__action,
.iar-page .iar-card__action .btn-icon {
    cursor: pointer;
    touch-action: manipulation;
    user-select: auto;
}
.iar-page .iar-card__media {
    width: 3.6rem; height: 3.6rem;
    border-radius: 14px;
    overflow: hidden;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    box-shadow: inset 0 0 0 1px rgba(15,23,42,.06);
}
.iar-page .iar-card__media i { font-size: 1.15rem; }
.iar-page .iar-card__media.tone-0 { background: #FFF1E6; color: #FE6F07; }
.iar-page .iar-card__media.tone-1 { background: #E8F4FF; color: #0284C7; }
.iar-page .iar-card__media.tone-2 { background: #ECFDF5; color: #059669; }
.iar-page .iar-card__media.tone-3 { background: #F5F3FF; color: #7C3AED; }
.iar-page .iar-card__media.tone-4 { background: #FEF3C7; color: #D97706; }
.iar-page .iar-card__media.tone-5 { background: #FCE7F3; color: #DB2777; }
.iar-page .iar-card__body { min-width: 0; }
.iar-page .iar-card__meta {
    display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem;
}
.iar-page .iar-card__status {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .2rem .55rem;
    border-radius: 999px;
    font-size: .68rem; font-weight: 750;
    background: #ecfdf5; color: #047857;
}
.iar-page .iar-card__status::before {
    content: ''; width: 6px; height: 6px; border-radius: 50%; background: #22c55e;
}
.iar-page .iar-card__status.off {
    background: #f1f5f9; color: #64748b;
}
.iar-page .iar-card__status.off::before { background: #94a3b8; }
.iar-page .iar-card__id {
    font-size: .7rem; font-weight: 650; color: #94a3b8; font-variant-numeric: tabular-nums;
}
.iar-page .iar-card__title {
    margin: 0;
    font-size: 1rem; font-weight: 800; color: #0f172a; letter-spacing: -.015em;
    line-height: 1.75;
    word-break: break-word;
    overflow-wrap: anywhere;
    /* Myanmar glyphs need room — avoid clamp clipping ascenders/descenders */
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    padding-bottom: .1rem;
}
.iar-page .iar-card__snip {
    margin: .35rem 0 0;
    font-size: .82rem; color: #64748b; line-height: 1.85;
    word-break: break-word;
    overflow-wrap: anywhere;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    padding-bottom: .15rem;
}
.iar-page .iar-card__action {
    display: flex; flex-direction: row; gap: .4rem; align-items: center;
}
.iar-page .iar-card__action .btn-icon {
    width: 2.25rem; height: 2.25rem; padding: 0;
    border-radius: 999px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    transition: background .15s, border-color .15s, color .15s, transform .15s;
}
.iar-page .iar-card__action .btn-icon:hover {
    transform: translateY(-1px);
}
.iar-page .iar-card__action .btn-icon--edit:hover {
    border-color: rgba(var(--brand-rgb), .45);
    color: var(--site-color);
    background: rgba(var(--brand-rgb), .08);
}
.iar-page .iar-card__action .btn-icon--del:hover {
    border-color: rgba(220, 38, 38, .35);
    color: #dc2626;
    background: rgba(220, 38, 38, .06);
}
.iar-page .iar-card__action form { margin: 0; }
@media (max-width: 640px) {
    .iar-page .iar-card {
        grid-template-columns: auto auto minmax(0, 1fr);
        gap: .7rem;
    }
    .iar-page .iar-card__action {
        grid-column: 1 / -1;
        justify-self: end;
    }
}
</style>

<div class="container-fluid pds-page-wrap pds-motion-enter pds-dispatch-to-assign-page pds-dispatch-assigned-items-page iar-page">
    <div class="pds-dispatch-to-assign-screen pds-rider-screen">
        <div class="pds-rider-hero" style="border-radius:18px;border:1px solid #f1e7dc;margin-bottom:1.1rem;background:#fff;box-shadow:0 8px 28px rgba(15,23,42,.04);">
            <div class="pds-rider-hero__copy">
                <div class="pds-rider-hero__eyebrow">
                    <i class="fas fa-robot" aria-hidden="true"></i>
                    <span>Inquiry</span>
                </div>
                <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                <p class="pds-rider-hero__subtitle">
                    User က ခလုတ်နှိပ်ရင် အဖြေစာ + ပုံများ chat ထဲ ပို့မည်။ ခလုတ် အများကြီး ထည့်နိုင်သည်။
                </p>
                <div class="pds-rider-meta-row">
                    <span class="pds-rider-meta-pill pds-rider-meta-pill--count">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        {{ $items->count() }} saved
                    </span>
                    <a href="{{ route('inquiry.inbox') }}" class="pds-rider-meta-pill" style="text-decoration:none;">
                        <i class="fas fa-comments" aria-hidden="true"></i>
                        Inquiry list
                    </a>
                </div>
            </div>
            <div class="pds-rider-hero__stat">
                <span class="pds-rider-hero__stat-value">{{ $items->count() }}</span>
                <span class="pds-rider-hero__stat-label">Replies</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @php
            $composeOpen = $errors->any() || $items->isEmpty();
        @endphp
        <div class="card pds-form-card mb-4 {{ $composeOpen ? '' : 'is-collapsed' }}" id="iarComposeCard">
            <div class="card-header py-3">
                <button type="button" class="iar-compose-toggle" id="iarComposeToggle" aria-expanded="{{ $composeOpen ? 'true' : 'false' }}">
                    <div>
                        <h5 class="mb-0 font-weight-bold">New reply</h5>
                        <small class="text-muted">ခလုတ်စာ · အဖြေ · ပုံများ — နှိပ်၍ ဖွင့်/ပိတ်</small>
                    </div>
                    <span class="iar-compose-chevron" aria-hidden="true">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                </button>
            </div>
            <div class="card-body" id="iarComposeBody" @if(!$composeOpen) style="display:none" @endif>
                <form method="POST" action="{{ route('inquiry-auto-replies.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div id="iar-create-rows">
                        <div class="iar-unit" data-iar-row>
                            <input type="hidden" name="items[0][sort_order]" value="{{ $items->count() }}">
                            <input type="hidden" name="items[0][is_active]" value="1">
                            <div class="form-group">
                                <label class="font-weight-bold small text-muted">ခလုတ်စာ</label>
                                <input type="text" name="items[0][label]" class="form-control" required maxlength="255"
                                       placeholder="ဥပမာ — ဆိုင်လိပ်စာသိချင်ပါတယ်">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold small text-muted">Bot အဖြေစာ</label>
                                <textarea name="items[0][reply_message]" class="form-control" rows="3"
                                          placeholder="နှိပ်ပြီး chat ထဲ ပို့မည့် အဖြေ"></textarea>
                            </div>
                            <div>
                                <label class="font-weight-bold small text-muted d-block">ပုံများ</label>
                                <div class="iar-photos" data-gallery>
                                    <label class="iar-ph-add" data-drop-zone tabindex="0">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span class="iar-ph-add__title">ပုံများ ရွေးရန် / ဆွဲထည့်ရန်</span>
                                        <span class="iar-ph-add__hint">Click · Drag & drop · Ctrl+V paste</span>
                                        <input type="file" name="items[0][reply_images][]" accept="image/*" multiple data-multi-file>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center mt-3 pt-3 border-top">
                        <button type="button" class="btn btn-link text-primary font-weight-bold p-0" id="iar-add-row">
                            + ခလုတ် ထပ်ထည့်ရန်
                        </button>
                        <button type="submit" class="btn btn-primary ml-auto">Save all</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex align-items-baseline justify-content-between mb-2 px-1">
            <h5 class="mb-0 font-weight-bold">Saved replies</h5>
            <span class="text-muted small">
                {{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'items' }}
                @if($items->isNotEmpty())
                    · drag to reorder
                @endif
            </span>
        </div>

        <div class="iar-card-list" id="iarSortableList"
             data-reorder-url="{{ route('inquiry-auto-replies.reorder') }}">
            @forelse($items as $item)
                @php
                    $urls = $item->replyImageUrls();
                    $msg = trim($item->reply_message);
                    if ($msg === '' || $msg === ' ') { $msg = ''; }
                    $photoCount = count($urls);
                    // Icons for chip/list only — reply images show in chat after user taps.
                    $text = mb_strtolower($item->label.' '.$msg);
                    $icon = 'fa-comment-dots';
                    if (str_contains($text, 'လိပ်စာ') || str_contains($text, 'address') || str_contains($text, 'location')) {
                        $icon = 'fa-map-marker-alt';
                    } elseif (str_contains($text, 'ဖုန်း') || str_contains($text, 'phone') || str_contains($text, 'call')) {
                        $icon = 'fa-mobile-alt';
                    } elseif (str_contains($text, 'စျေး') || str_contains($text, 'price') || str_contains($text, 'ကျသင့်')) {
                        $icon = 'fa-tags';
                    } elseif (str_contains($text, 'discount') || str_contains($text, 'လျှော့') || str_contains($text, 'promo')) {
                        $icon = 'fa-percent';
                    } elseif (str_contains($text, 'ဆိုင်') || str_contains($text, 'store') || str_contains($text, 'shop')) {
                        $icon = 'fa-store';
                    } elseif (str_contains($text, 'အချိန်') || str_contains($text, 'time') || str_contains($text, 'နာရီ')) {
                        $icon = 'fa-clock';
                    } elseif (str_contains($text, 'ပို့') || str_contains($text, 'delivery') || str_contains($text, 'deliver')) {
                        $icon = 'fa-shipping-fast';
                    } elseif (str_contains($text, 'ငွေ') || str_contains($text, 'pay') || str_contains($text, 'payment')) {
                        $icon = 'fa-wallet';
                    } else {
                        $pool = ['fa-comments', 'fa-question-circle', 'fa-info-circle', 'fa-lightbulb', 'fa-star', 'fa-hand-point-right'];
                        $icon = $pool[crc32((string) $item->label) % count($pool)];
                    }
                    $tone = abs(crc32((string) $item->label)) % 6;
                @endphp
                <div class="iar-card {{ $item->is_active ? '' : 'is-off' }}" data-id="{{ $item->id }}" title="Drag to reorder">
                    <div class="iar-card__media tone-{{ $tone }}" aria-hidden="true" title="{{ $photoCount ? $photoCount.' photos send on reply' : '' }}">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div class="iar-card__body">
                        <div class="iar-card__meta">
                            <span class="iar-card__status {{ $item->is_active ? '' : 'off' }}">
                                {{ $item->is_active ? 'Active' : 'Off' }}
                            </span>
                            <span class="iar-card__id">#{{ $item->id }}</span>
                            @if($photoCount > 0)
                                <span class="iar-card__id"><i class="far fa-image"></i> {{ $photoCount }}</span>
                            @endif
                        </div>
                        <h3 class="iar-card__title">{{ $item->label }}</h3>
                        <p class="iar-card__snip">
                            {{ $msg !== '' ? $msg : '—' }}
                        </p>
                    </div>
                    <div class="iar-card__action">
                        <button type="button" class="btn-icon btn-icon--edit" title="Edit"
                                data-toggle="modal" data-target="#editReply{{ $item->id }}">
                            <i class="fas fa-pen" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn-icon btn-icon--del" title="Delete"
                                data-iar-delete
                                data-id="{{ $item->id }}"
                                data-label="{{ e($item->label) }}"
                                data-url="{{ route('inquiry-auto-replies.destroy', $item->id) }}">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="pds-dispatch-to-assign-empty pds-msg-empty">
                    <span class="pds-msg-empty__icon" aria-hidden="true"><i class="far fa-comments"></i></span>
                    <p>No saved replies yet.</p>
                </div>
            @endforelse
        </div>

        @foreach($items as $item)
            @php
                $msg = trim($item->reply_message);
                if ($msg === '' || $msg === ' ') { $msg = ''; }
            @endphp
                <div class="modal fade iar-edit-modal" id="editReply{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('inquiry-auto-replies.update', $item->id) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="sort_order" value="{{ $item->sort_order }}">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit reply</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="iar-edit-panel">
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small text-muted">ခလုတ်စာ</label>
                                            <input type="text" name="label" class="form-control" required value="{{ $item->label }}">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small text-muted">Bot အဖြေစာ</label>
                                            <textarea name="reply_message" class="form-control" rows="4">{{ $msg }}</textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="font-weight-bold small text-muted d-block">ပုံများ <span class="font-weight-normal">(နှိပ်မှ chat ထဲ ပို့မည်)</span></label>
                                            <div class="iar-photos" data-gallery>
                                                @foreach($item->replyMediaItems() as $media)
                                                    <div class="iar-ph">
                                                        <img src="{{ $media->getFullUrl() }}" alt="">
                                                        <button type="button"
                                                                onclick="this.parentElement.insertAdjacentHTML('beforeend','<input type=hidden name=remove_media_ids[] value={{ (int) $media->id }}>'); this.parentElement.style.display='none';">&times;</button>
                                                    </div>
                                                @endforeach
                                                <label class="iar-ph-add {{ $item->replyMediaItems()->isNotEmpty() ? 'has-files' : '' }}" data-drop-zone tabindex="0">
                                                    <i class="fas fa-{{ $item->replyMediaItems()->isNotEmpty() ? 'plus' : 'cloud-upload-alt' }}"></i>
                                                    <span class="iar-ph-add__title">{{ $item->replyMediaItems()->isNotEmpty() ? 'ထပ်ထည့်' : 'ပုံများ ရွေးရန် / ဆွဲထည့်ရန်' }}</span>
                                                    <span class="iar-ph-add__hint">Click · Drag & drop · Ctrl+V paste</span>
                                                    <input type="file" name="reply_images[]" accept="image/*" multiple data-multi-file>
                                                </label>
                                            </div>
                                        </div>
                                        <label class="iar-active-toggle" for="active_{{ $item->id }}">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" id="active_{{ $item->id }}"
                                                   name="is_active" value="1" @checked($item->is_active)>
                                            <span class="iar-active-toggle__ui" aria-hidden="true"></span>
                                            <span class="iar-active-toggle__text">
                                                <span class="on">Active</span>
                                                <span class="off">Off</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn-icon btn-icon--del" title="Delete"
                                            data-dismiss="modal"
                                            data-iar-delete
                                            data-id="{{ $item->id }}"
                                            data-label="{{ e($item->label) }}"
                                            data-url="{{ route('inquiry-auto-replies.destroy', $item->id) }}">
                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                    </button>
                                    <div class="iar-edit-actions">
                                        <button type="button" class="btn-icon btn-icon--cancel" title="Cancel" data-dismiss="modal">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                        </button>
                                        <button type="submit" class="btn-icon btn-icon--save" title="Update">
                                            <i class="fas fa-check" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
        @endforeach
    </div>
</div>

{{-- Delete confirm popup --}}
<div class="modal fade iar-delete-modal" id="iarDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="iar-delete-icon"><i class="fas fa-trash-alt"></i></div>
                <h5>Delete reply?</h5>
                <p id="iarDeleteText">ဤ auto reply ကို ဖျက်မလား?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn-delete" id="iarDeleteConfirm">Delete</button>
            </div>
        </div>
    </div>
</div>
<form id="iarDeleteForm" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<template id="iar-row-template">
    <div class="iar-unit" data-iar-row>
        <button type="button" class="iar-rm" data-remove-row>&times;</button>
        <input type="hidden" name="items[__INDEX__][sort_order]" value="__SORT__">
        <input type="hidden" name="items[__INDEX__][is_active]" value="1">
        <div class="form-group">
            <label class="font-weight-bold small text-muted">ခလုတ်စာ</label>
            <input type="text" name="items[__INDEX__][label]" class="form-control" required maxlength="255"
                   placeholder="ဥပမာ — ဆိုင်လိပ်စာသိချင်ပါတယ်">
        </div>
        <div class="form-group">
            <label class="font-weight-bold small text-muted">Bot အဖြေစာ</label>
            <textarea name="items[__INDEX__][reply_message]" class="form-control" rows="3"
                      placeholder="နှိပ်ပြီး chat ထဲ ပို့မည့် အဖြေ"></textarea>
        </div>
        <div>
            <label class="font-weight-bold small text-muted d-block">ပုံများ</label>
            <div class="iar-photos" data-gallery>
                <label class="iar-ph-add" data-drop-zone tabindex="0">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span class="iar-ph-add__title">ပုံများ ရွေးရန် / ဆွဲထည့်ရန်</span>
                    <span class="iar-ph-add__hint">Click · Drag & drop · Ctrl+V paste</span>
                    <input type="file" name="items[__INDEX__][reply_images][]" accept="image/*" multiple data-multi-file>
                </label>
            </div>
        </div>
    </div>
</template>

<script>
(function () {
    var wrap = document.getElementById('iar-create-rows');
    var tpl = document.getElementById('iar-row-template');
    var addBtn = document.getElementById('iar-add-row');
    var nextIndex = 1;
    var baseSort = {{ (int) $items->count() }};
    var pasteTarget = null;

    function isImageFile(file) {
        return file && file.type && file.type.indexOf('image/') === 0;
    }

    function syncInputFiles(input, files) {
        var dt = new DataTransfer();
        files.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
    }

    function getBagFiles(input) {
        return Array.prototype.slice.call(input._iarFiles || input.files || []);
    }

    function setBagFiles(input, files) {
        input._iarFiles = files;
        syncInputFiles(input, files);
    }

    function renderPreviews(gallery, zone, files) {
        gallery.querySelectorAll('[data-new]').forEach(function (n) { n.remove(); });
        files.forEach(function (file, idx) {
            var el = document.createElement('div');
            el.className = 'iar-ph';
            el.setAttribute('data-new', '1');
            var img = document.createElement('img');
            img.alt = '';
            img.src = URL.createObjectURL(file);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'Remove';
            btn.innerHTML = '&times;';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var next = getBagFiles(zone.querySelector('[data-multi-file]')).filter(function (_, i) { return i !== idx; });
                var input = zone.querySelector('[data-multi-file]');
                setBagFiles(input, next);
                renderPreviews(gallery, zone, next);
                updateZoneState(zone, next.length);
            });
            el.appendChild(img);
            el.appendChild(btn);
            gallery.insertBefore(el, zone);
        });
    }

    function updateZoneState(zone, count) {
        var title = zone.querySelector('.iar-ph-add__title');
        var icon = zone.querySelector('i');
        var gallery = zone.closest('[data-gallery]');
        var serverThumbs = gallery ? gallery.querySelectorAll('.iar-ph:not([data-new])').length : 0;
        if (count > 0 || serverThumbs > 0) {
            zone.classList.add('has-files');
            if (title) title.textContent = 'ထပ်ထည့်';
            if (icon) icon.className = 'fas fa-plus';
        } else {
            zone.classList.remove('has-files');
            if (title) title.textContent = 'ပုံများ ရွေးရန် / ဆွဲထည့်ရန်';
            if (icon) icon.className = 'fas fa-cloud-upload-alt';
        }
    }

    function appendFiles(input, incoming) {
        var images = Array.prototype.filter.call(incoming, isImageFile);
        if (!images.length) return;
        var zone = input.closest('.iar-ph-add');
        var gallery = input.closest('[data-gallery]');
        if (!zone || !gallery) return;
        var merged = getBagFiles(input).concat(images);
        setBagFiles(input, merged);
        renderPreviews(gallery, zone, merged);
        updateZoneState(zone, merged.length);
    }

    function bindDropZone(zone) {
        if (zone.dataset.dropBound) return;
        zone.dataset.dropBound = '1';
        var input = zone.querySelector('[data-multi-file]');
        if (!input) return;

        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.remove('is-dragover');
            });
        });
        zone.addEventListener('drop', function (e) {
            var files = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files : [];
            appendFiles(input, files);
        });
        zone.addEventListener('focus', function () { pasteTarget = zone; });
        zone.addEventListener('mouseenter', function () { pasteTarget = zone; });
        zone.addEventListener('click', function () { pasteTarget = zone; });
    }

    function bindMulti(root) {
        root.querySelectorAll('[data-multi-file]').forEach(function (input) {
            if (input.dataset.bound) return;
            input.dataset.bound = '1';
            input._iarFiles = Array.prototype.slice.call(input.files || []);
            input.addEventListener('change', function () {
                // Replace with newly picked files from dialog (browser replaces selection)
                var picked = Array.prototype.slice.call(input.files || []).filter(isImageFile);
                var zone = input.closest('.iar-ph-add');
                var gallery = input.closest('[data-gallery]');
                if (!zone || !gallery) return;
                // Merge with previously accumulated (drag/paste) files
                var prev = input._iarFiles || [];
                var merged = prev.concat(picked);
                // de-dupe by name+size+lastModified
                var seen = {};
                merged = merged.filter(function (f) {
                    var key = f.name + ':' + f.size + ':' + f.lastModified;
                    if (seen[key]) return false;
                    seen[key] = 1;
                    return true;
                });
                setBagFiles(input, merged);
                renderPreviews(gallery, zone, merged);
                updateZoneState(zone, merged.length);
            });
            var zone = input.closest('[data-drop-zone], .iar-ph-add');
            if (zone) bindDropZone(zone);
        });
    }

    document.addEventListener('paste', function (e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        var files = [];
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image/') === 0) {
                var f = items[i].getAsFile();
                if (f) files.push(f);
            }
        }
        if (!files.length) return;

        var zone = pasteTarget;
        if (!zone || !document.body.contains(zone)) {
            zone = document.querySelector('.modal.show [data-drop-zone], .modal.show .iar-ph-add')
                || document.querySelector('#iar-create-rows [data-drop-zone], #iar-create-rows .iar-ph-add');
        }
        if (!zone) return;
        var input = zone.querySelector('[data-multi-file]');
        if (!input) return;
        e.preventDefault();
        appendFiles(input, files);
    });

    if (addBtn && wrap && tpl) {
        addBtn.addEventListener('click', function () {
            var html = tpl.innerHTML
                .replace(/__INDEX__/g, String(nextIndex))
                .replace(/__SORT__/g, String(baseSort + nextIndex));
            nextIndex++;
            var holder = document.createElement('div');
            holder.innerHTML = html.trim();
            var row = holder.firstElementChild;
            wrap.appendChild(row);
            bindMulti(row);
            row.querySelector('[data-remove-row]').addEventListener('click', function () {
                if (wrap.querySelectorAll('[data-iar-row]').length <= 1) return;
                row.remove();
            });
            var label = row.querySelector('input[name*="[label]"]');
            if (label) label.focus();
        });
    }

    bindMulti(document);

    // New reply open / close
    var composeCard = document.getElementById('iarComposeCard');
    var composeToggle = document.getElementById('iarComposeToggle');
    var composeBody = document.getElementById('iarComposeBody');
    if (composeToggle && composeCard && composeBody) {
        composeToggle.addEventListener('click', function () {
            var closed = composeCard.classList.toggle('is-collapsed');
            composeBody.style.display = closed ? 'none' : '';
            composeToggle.setAttribute('aria-expanded', closed ? 'false' : 'true');
        });
    }

    // Custom delete popup (replace native confirm)
    var deleteModal = document.getElementById('iarDeleteModal');
    var deleteForm = document.getElementById('iarDeleteForm');
    var deleteText = document.getElementById('iarDeleteText');
    var deleteConfirm = document.getElementById('iarDeleteConfirm');
    var pendingDeleteUrl = null;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-iar-delete]');
        if (!btn) return;
        e.preventDefault();
        pendingDeleteUrl = btn.getAttribute('data-url');
        var label = btn.getAttribute('data-label') || '';
        if (deleteText) {
            deleteText.textContent = label
                ? ('“' + label + '” ကို ဖျက်မလား?')
                : 'ဤ auto reply ကို ဖျက်မလား?';
        }
        if (window.jQuery && deleteModal) {
            window.jQuery(deleteModal).modal('show');
        }
    });

    if (deleteConfirm) {
        deleteConfirm.addEventListener('click', function () {
            if (!pendingDeleteUrl || !deleteForm) return;
            deleteForm.action = pendingDeleteUrl;
            deleteForm.submit();
        });
    }

    // Drag-and-drop reorder (persists sort_order → mobile API orderBy sort_order)
    var list = document.getElementById('iarSortableList');
    if (list && typeof Sortable !== 'undefined' && list.querySelector('.iar-card[data-id]')) {
        var reorderUrl = list.getAttribute('data-reorder-url');
        var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content
            || (document.querySelector('input[name="_token"]') || {}).value
            || '';
        var saving = false;

        Sortable.create(list, {
            animation: 180,
            draggable: '.iar-card[data-id]',
            filter: '.iar-card__action, .iar-card__action *, .btn-icon, button, a, input, textarea, label',
            preventOnFilter: true,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'is-dragging',
            onEnd: function () {
                if (!reorderUrl || saving) return;
                var order = Array.prototype.map.call(
                    list.querySelectorAll('.iar-card[data-id]'),
                    function (el) { return parseInt(el.getAttribute('data-id'), 10); }
                ).filter(function (id) { return !isNaN(id); });
                if (!order.length) return;

                saving = true;
                fetch(reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ order: order })
                }).then(function (res) {
                    if (!res.ok) throw new Error('reorder failed');
                    return res.json().catch(function () { return {}; });
                }).catch(function () {
                    if (window.iziToast) {
                        window.iziToast.error({
                            title: 'Error',
                            message: 'Order save failed. Please refresh and try again.',
                            position: 'topRight'
                        });
                    }
                }).finally(function () {
                    saving = false;
                });
            }
        });
    }
})();
</script>

</x-master-layout>

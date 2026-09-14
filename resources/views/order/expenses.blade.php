<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-expenses-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-receipt" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.expenses_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value" id="expenses-month-total">{{ number_format($monthTotal) }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.expenses_month_total') }}</span>
                </div>
            </div>

            @include('partials._branch-tabs', [
                'branchTabs' => $branchTabs ?? collect(),
                'selectedBranchId' => $selectedBranchId ?? null,
                'branchTabCounts' => $branchTabCounts ?? [],
                'allCount' => $allBranchCount ?? null,
                'includeAll' => false,
                'routeName' => 'order.expenses',
                'routeQuery' => [
                    'month' => $monthValue,
                    'from_date' => $filterFrom,
                    'to_date' => $filterTo,
                    'subject' => $filterSubject,
                ],
            ])

            <div class="pds-expenses-toolbar">
                <div class="pds-expenses-toolbar__month">
                    <div class="pds-expenses-month-nav">
                        <a href="{{ route('order.expenses', array_filter(['month' => $prevMonth, 'branch_id' => $selectedBranchId ?? null])) }}" class="pds-expenses-month-nav__btn" title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="pds-expenses-month-nav__label">{{ $monthLabel }}</span>
                        <a href="{{ route('order.expenses', array_filter(['month' => $nextMonth, 'branch_id' => $selectedBranchId ?? null])) }}" class="pds-expenses-month-nav__btn" title="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <form method="GET" action="{{ route('order.expenses') }}" class="pds-expenses-filter" id="expensesFilterForm">
                    <input type="hidden" name="month" value="{{ $monthValue }}">
                    <input type="hidden" name="branch_id" value="{{ $branchFilter ?? 'all' }}">
                    <div class="pds-expenses-filter__field">
                        <label for="expenses_from">{{ __('message.from_date') }}</label>
                        <input type="text" name="from_date" id="expenses_from" class="pds-dispatch-input dispatch-datepicker"
                               value="{{ $filterFrom }}" autocomplete="off" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="pds-expenses-filter__field">
                        <label for="expenses_to">{{ __('message.to_date') }}</label>
                        <input type="text" name="to_date" id="expenses_to" class="pds-dispatch-input dispatch-datepicker"
                               value="{{ $filterTo }}" autocomplete="off" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="pds-expenses-filter__field pds-expenses-filter__field--subject">
                        <label for="expenses_subject">{{ __('message.subject') }}</label>
                        <input type="text" name="subject" id="expenses_subject" class="pds-dispatch-input"
                               value="{{ $filterSubject }}"
                               placeholder="{{ __('message.expenses_subject') }}" autocomplete="off">
                    </div>
                    <div class="pds-expenses-filter__actions">
                        <button type="submit" class="pds-daily-check-search-btn" title="{{ __('message.check') }}">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>{{ __('message.check') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="pds-expenses-board" id="expenses-board">
                @forelse($cards as $card)
                    @php
                        $displayItems = collect($card->display_items ?? $card->items);
                        $cardItemsJson = $card->items->map(fn ($i) => [
                            'subject' => $i->subject,
                            'amount' => (float) $i->amount,
                            'image' => $i->hasUploadedImage() ? $i->image : null,
                            'image_url' => $i->imageUrl(),
                            'locked' => $i->isLockedSource(),
                            'lockKind' => $i->source === \App\Models\ExpenseItem::SOURCE_AGENT_FEE
                                ? 'agent'
                                : ($i->source === \App\Models\ExpenseItem::SOURCE_RIDER_FUEL ? 'fuel' : null),
                        ])->values();
                        $isGenerated = in_array((int) $card->id, $generatedCardIds ?? [], true);
                        $canGenerate = ! $isGenerated
                            && $card->expense_date
                            && $today > $card->expense_date->format('Y-m-d');
                    @endphp
                    <article class="pds-expense-card{{ $isGenerated ? ' is-generated' : '' }}" data-id="{{ $card->id }}"
                             data-date="{{ $card->expense_date->format('Y-m-d') }}"
                             data-generated="{{ $isGenerated ? '1' : '0' }}"
                             data-can-generate="{{ $canGenerate ? '1' : '0' }}"
                             data-items='@json($cardItemsJson)'>
                        <header class="pds-expense-card__header">
                            <strong>{{ $card->expense_date->format('d/m/y') }}</strong>
                            <em title="{{ $displayItems->count() }} items">{{ $displayItems->count() }}</em>
                        </header>
                        <div class="pds-expense-card__body">
                            @php
                                $riderFuelItems = $displayItems->filter(
                                    fn ($i) => $i->source === \App\Models\ExpenseItem::SOURCE_RIDER_FUEL
                                );
                                $agentFeeItems = $displayItems->filter(
                                    fn ($i) => $i->source === \App\Models\ExpenseItem::SOURCE_AGENT_FEE
                                );
                                $manualItems = $displayItems->reject(
                                    fn ($i) => in_array($i->source, [
                                        \App\Models\ExpenseItem::SOURCE_RIDER_FUEL,
                                        \App\Models\ExpenseItem::SOURCE_AGENT_FEE,
                                    ], true)
                                );
                            @endphp
                            @foreach($riderFuelItems as $item)
                                <div class="pds-expense-card__rider-fuel">
                                    <span>{{ $item->subject }}</span>
                                    <em>{{ number_format($item->amount) }}</em>
                                </div>
                            @endforeach
                            @foreach($agentFeeItems as $item)
                                <div class="pds-expense-card__rider-fuel">
                                    <span>{{ $item->subject }}</span>
                                    <em>{{ number_format($item->amount) }}</em>
                                    <button type="button"
                                            class="pds-expense-card__agent-photos js-agent-fee-photos"
                                            data-date="{{ $card->expense_date->format('Y-m-d') }}"
                                            title="{{ __('message.expenses_agent_fee_view_photos') }}">
                                        <i class="fas fa-image" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @endforeach
                            <ul class="pds-expense-card__list">
                                @forelse($manualItems as $item)
                                    <li>
                                        <span>{{ $item->subject }}</span>
                                        <em>{{ number_format($item->amount) }}</em>
                                        @php $thumb = $item->imageUrl(); @endphp
                                        @if($thumb)
                                            <button type="button" class="pds-expense-card__thumb js-card-image" data-image="{{ $thumb }}" data-subject="{{ $item->subject }}" title="{{ __('message.expenses_image_view') }}">
                                                <img src="{{ $thumb }}" alt="">
                                            </button>
                                        @else
                                            <span class="pds-expense-card__thumb is-empty" title="{{ __('message.expenses_image_upload') }}">
                                                <i class="fas fa-camera" aria-hidden="true"></i>
                                            </span>
                                        @endif
                                    </li>
                                @empty
                                    @if($riderFuelItems->isEmpty() && $agentFeeItems->isEmpty())
                                        <li class="is-empty">{{ __('message.no_record_found') }}</li>
                                    @endif
                                @endforelse
                            </ul>
                        </div>
                        <div class="pds-expense-card__actions">
                            @if($canEdit && ! $isGenerated)
                                <button type="button" class="pds-expense-card__btn is-edit" data-action="edit" title="{{ __('message.edit') }}">
                                    <i class="fas fa-pen"></i>
                                    <span>{{ __('message.edit') }}</span>
                                </button>
                            @else
                                <button type="button" class="pds-expense-card__btn is-view" data-action="view" title="{{ __('message.expenses_view_card') }}">
                                    <i class="fas fa-eye"></i>
                                    <span>{{ __('message.expenses_view_btn') }}</span>
                                </button>
                            @endif
                            @if($canEdit)
                                <button type="button" class="pds-expense-card__btn is-generate {{ $isGenerated ? 'is-done' : '' }}{{ ! $isGenerated && ! $canGenerate ? ' is-wait' : '' }}"
                                        data-action="generate"
                                        title="{{ $isGenerated
                                            ? __('message.expense_summary_generated_btn')
                                            : ($canGenerate ? __('message.expense_summary_generate') : __('message.expense_summary_generate_next_day')) }}"
                                        @if($isGenerated || ! $canGenerate) disabled @endif>
                                    <i class="fas fa-file-export"></i>
                                    <span>{{ $isGenerated ? __('message.expense_summary_generated_btn') : __('message.expense_summary_generate') }}</span>
                                </button>
                                <button type="button" class="pds-expense-card__btn is-del" data-action="delete"
                                        title="{{ $isGenerated ? __('message.expense_summary_card_locked') : __('message.delete') }}"
                                        @if($isGenerated) disabled @endif>
                                    <i class="fas fa-trash"></i>
                                    <span>{{ __('message.delete') }}</span>
                                </button>
                            @endif
                        </div>
                        <footer class="pds-expense-card__total">
                            <span>{{ __('message.total') }}</span>
                            <strong>{{ number_format($card->display_total ?? $card->total_amount) }}</strong>
                        </footer>
                    </article>
                @empty
                    <div class="pds-money-transfer-empty">
                        <div class="pds-money-transfer-empty__icon"><i class="fas fa-inbox"></i></div>
                        <p>{{ __('message.no_record_found') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Create / Edit / View modal --}}
    <div class="pds-expense-modal" id="expense-form-modal" hidden>
        <div class="pds-expense-modal__backdrop" data-close="form"></div>
        <div class="pds-expense-modal__stage">
            <div class="pds-expense-modal__dialog" role="dialog" aria-modal="true">
                <header class="pds-expense-modal__header">
                    <h5 id="expense-form-title">{{ __('message.expenses_add_card') }}</h5>
                    <div class="pds-expense-modal__date">
                        <label for="expense-date-input">{{ __('message.date') }}</label>
                        <input type="date" id="expense-date-input" class="form-control" value="{{ $today }}">
                    </div>
                </header>
                <div class="pds-expense-modal__cols">
                    <span>{{ __('message.expenses_subject') }}</span>
                    <span>{{ __('message.amount') }}</span>
                    <span>{{ __('message.image') }}</span>
                    <span></span>
                </div>
                <div class="pds-expense-modal__rows" id="expense-item-rows"></div>
                <div class="pds-expense-modal__add-row">
                    <button type="button" class="pds-expense-modal__plus" id="expense-add-row" title="Add row">+</button>
                </div>
                <div class="pds-expense-modal__total">
                    <span>{{ __('message.total_amount') }}</span>
                    <strong id="expense-form-total">0</strong>
                </div>
                <footer class="pds-expense-modal__footer">
                    <button type="button" class="pds-expense-modal__cancel" id="expense-form-cancel" title="{{ __('message.cancel') }}">
                        <i class="fas fa-times"></i>
                    </button>
                    <button type="button" class="pds-expense-modal__save" id="expense-form-save" title="{{ __('message.save') }}">
                        <i class="fas fa-check"></i>
                    </button>
                </footer>
            </div>
            <aside class="pds-expense-modal__preview" id="expense-image-view-modal" hidden>
                <header class="pds-expense-modal__header">
                    <h5 id="expense-image-view-title">{{ __('message.expenses_image_view') }}</h5>
                    <button type="button" class="pds-expense-modal__preview-close" id="expense-image-view-close" title="{{ __('message.close') }}" data-close="image-view">
                        <i class="fas fa-times"></i>
                    </button>
                </header>
                <div class="pds-expense-image-view">
                    <img id="expense-image-view-img" src="" alt="">
                </div>
                <footer class="pds-expense-modal__footer">
                    <button type="button" class="pds-expense-modal__cancel" id="expense-image-view-change">{{ __('message.expenses_image_change') }}</button>
                </footer>
            </aside>
        </div>
    </div>

    {{-- Agent ရငွေ delivered delivered photos --}}
    <div class="pds-expense-modal" id="expense-agent-photos-modal" hidden>
        <div class="pds-expense-modal__backdrop" data-close="agent-photos"></div>
        <div class="pds-expense-modal__stage pds-expense-agent-photos-stage">
            <div class="pds-expense-modal__dialog pds-expense-agent-photos-dialog" role="dialog" aria-modal="true">
                <header class="pds-expense-modal__header">
                    <h5 id="expense-agent-photos-title">{{ __('message.expenses_agent_fee_photos') }}</h5>
                    <button type="button" class="pds-expense-modal__preview-close" id="expense-agent-photos-close" title="{{ __('message.close') }}" data-close="agent-photos">
                        <i class="fas fa-times"></i>
                    </button>
                </header>
                <div class="pds-expense-agent-photos__body" id="expense-agent-photos-body"></div>
            </div>
            <aside class="pds-expense-modal__preview" id="expense-agent-photo-preview" hidden>
                <header class="pds-expense-modal__header">
                    <h5 id="expense-agent-photo-preview-title">{{ __('message.expenses_image_view') }}</h5>
                    <button type="button" class="pds-expense-modal__preview-close" id="expense-agent-photo-preview-close" title="{{ __('message.close') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </header>
                <div class="pds-expense-image-view">
                    <img id="expense-agent-photo-preview-img" src="" alt="">
                </div>
            </aside>
        </div>
    </div>

    {{-- Delete confirm modal --}}
    <div class="pds-expense-modal" id="expense-delete-modal" hidden>
        <div class="pds-expense-modal__backdrop" data-close="delete"></div>
        <div class="pds-expense-modal__dialog pds-expense-modal__dialog--confirm" role="dialog" aria-modal="true">
            <header class="pds-expense-modal__header">
                <h5>{{ __('message.delete') }}</h5>
            </header>
            <p class="pds-expense-modal__confirm-text">{{ __('message.expenses_delete_confirm') }}</p>
            <footer class="pds-expense-modal__footer">
                <button type="button" class="pds-expense-modal__cancel" id="expense-delete-no">{{ __('message.no') }}</button>
                <button type="button" class="pds-expense-modal__save is-danger" id="expense-delete-yes">{{ __('message.yes') }}</button>
            </footer>
        </div>
    </div>

    {{-- Success / info popup --}}
    <div class="pds-expense-modal" id="expense-notice-modal" hidden>
        <div class="pds-expense-modal__backdrop" data-close="notice"></div>
        <div class="pds-expense-modal__dialog pds-expense-modal__dialog--confirm" role="dialog" aria-modal="true">
            <header class="pds-expense-modal__header">
                <h5 id="expense-notice-title">{{ __('message.expense_summary_generate') }}</h5>
            </header>
            <p class="pds-expense-modal__confirm-text" id="expense-notice-text"></p>
            <footer class="pds-expense-modal__footer">
                <button type="button" class="pds-expense-modal__save" id="expense-notice-ok">{{ __('message.close') }}</button>
            </footer>
        </div>
    </div>

    <script>
        (function bootExpenseFilters() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr('#expenses_from, #expenses_to', { dateFormat: 'd-m-Y', allowInput: true });
            } else {
                setTimeout(bootExpenseFilters, 40);
            }
        })();
    </script>
    <script>
        (function () {
            const canEdit = @json((bool) $canEdit);
            const selectedBranchId = @json($selectedBranchId ?? null);
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const routes = {
                store: @json(route('order.expenses.store')),
                update: @json(route('order.expenses.update', ['id' => '__ID__'])),
                destroy: @json(route('order.expenses.destroy', ['id' => '__ID__'])),
                generate: @json(route('order.expenses.generate', ['id' => '__ID__'])),
                riderFuel: @json(route('order.expenses.rider-fuel-total')),
                agentFee: @json(route('order.expenses.agent-fee-total')),
                agentFeePhotos: @json(route('order.expenses.agent-fee-photos')),
            };
            const demoImagePath = @json(\App\Models\ExpenseItem::DEMO_IMAGE);
            const i18n = {
                addTitle: @json(__('message.expenses_add_card')),
                editTitle: @json(__('message.expenses_edit_card')),
                viewTitle: @json(__('message.expenses_view_card')),
                viewLabel: @json(__('message.expenses_view_btn')),
                subjectRequired: @json(__('message.expenses_items_required')),
                saveOk: @json(__('message.expenses_saved')),
                deleteOk: @json(__('message.expenses_deleted')),
                riderFuelSubject: @json(__('message.expenses_rider_fuel')),
                agentFeeSubject: @json(__('message.expenses_agent_fee')),
                agentFeeHint: @json(__('message.expenses_agent_fee_hint')),
                agentFeePhotos: @json(__('message.expenses_agent_fee_photos')),
                agentFeePhotosEmpty: @json(__('message.expenses_agent_fee_photos_empty')),
                agentFeeViewPhotos: @json(__('message.expenses_agent_fee_view_photos')),
                pointIncome: @json(__('message.point_income')),
                agentIncome: @json(__('message.agent_income')),
                generateLabel: @json(__('message.expense_summary_generate')),
                generatedLabel: @json(__('message.expense_summary_generated_btn')),
                generateOk: @json(__('message.expense_summary_generated')),
                generateNextDay: @json(__('message.expense_summary_generate_next_day')),
                lockedHint: @json(__('message.expense_summary_card_locked')),
                noticeError: @json(__('message.error')),
                imageUpload: @json(__('message.expenses_image_upload')),
                imageView: @json(__('message.expenses_image_view')),
                imageChange: @json(__('message.expenses_image_change')),
                imageRequired: @json(__('message.expenses_image_required')),
                close: @json(__('message.close')),
            };

            const formModal = document.getElementById('expense-form-modal');
            const deleteModal = document.getElementById('expense-delete-modal');
            const noticeModal = document.getElementById('expense-notice-modal');
            const imageViewModal = document.getElementById('expense-image-view-modal');
            const imageViewImg = document.getElementById('expense-image-view-img');
            const imageViewTitle = document.getElementById('expense-image-view-title');
            const noticeTitle = document.getElementById('expense-notice-title');
            const noticeText = document.getElementById('expense-notice-text');
            const rowsEl = document.getElementById('expense-item-rows');
            const dateInput = document.getElementById('expense-date-input');
            const titleEl = document.getElementById('expense-form-title');
            let editingId = null;
            let deletingId = null;
            let imageViewRow = null;
            let viewingOnly = false;

            function showNotice(message, title) {
                if (noticeTitle) noticeTitle.textContent = title || i18n.generateLabel;
                if (noticeText) noticeText.textContent = message || '';
                if (noticeModal) noticeModal.hidden = false;
            }

            function closeNotice() {
                if (noticeModal) noticeModal.hidden = true;
            }

            function getImageButtonUrl(btn) {
                if (!btn || btn.classList.contains('is-empty')) return '';
                return btn.querySelector('.js-image-preview')?.getAttribute('src') || '';
            }

            function syncImageButtonMeta(btn) {
                if (!btn) return;
                const hasImage = !!getImageButtonUrl(btn);
                btn.classList.toggle('has-image', hasImage);
                btn.title = hasImage ? i18n.imageView : i18n.imageUpload;
            }

            function openImageView(url, row) {
                if (!url || !imageViewModal || !imageViewImg) return;
                rowsEl?.querySelectorAll('.pds-expense-modal__row.is-previewing').forEach((el) => {
                    el.classList.remove('is-previewing');
                });
                imageViewRow = row || null;
                imageViewRow?.classList.add('is-previewing');
                imageViewImg.src = url;
                const subject = row?.querySelector('.js-subject')?.value?.trim() || '';
                if (imageViewTitle) {
                    imageViewTitle.textContent = subject || i18n.imageView;
                }
                imageViewModal.hidden = false;
                formModal?.classList.add('has-preview');
            }

            function closeImageView() {
                imageViewRow?.classList.remove('is-previewing');
                if (imageViewModal) imageViewModal.hidden = true;
                if (imageViewImg) imageViewImg.removeAttribute('src');
                imageViewRow = null;
                formModal?.classList.remove('has-preview');
            }

            const agentPhotosModal = document.getElementById('expense-agent-photos-modal');
            const agentPhotosBody = document.getElementById('expense-agent-photos-body');
            const agentPhotosTitle = document.getElementById('expense-agent-photos-title');
            const agentPhotoPreview = document.getElementById('expense-agent-photo-preview');
            const agentPhotoPreviewImg = document.getElementById('expense-agent-photo-preview-img');
            const agentPhotoPreviewTitle = document.getElementById('expense-agent-photo-preview-title');

            function closeAgentPhotoSidePreview() {
                agentPhotosModal?.classList.remove('has-preview');
                agentPhotosBody?.querySelectorAll('.pds-expense-agent-photos__card.is-active').forEach((el) => {
                    el.classList.remove('is-active');
                });
                if (agentPhotoPreview) agentPhotoPreview.hidden = true;
                if (agentPhotoPreviewImg) agentPhotoPreviewImg.removeAttribute('src');
            }

            function openAgentPhotoSidePreview(url, meta) {
                if (!url || !agentPhotoPreview || !agentPhotoPreviewImg) return;
                agentPhotoPreviewImg.src = url;
                if (agentPhotoPreviewTitle) {
                    agentPhotoPreviewTitle.textContent = meta || i18n.imageView;
                }
                agentPhotoPreview.hidden = false;
                agentPhotosModal?.classList.add('has-preview');
            }

            function closeAgentFeePhotos() {
                closeAgentPhotoSidePreview();
                if (agentPhotosModal) agentPhotosModal.hidden = true;
                if (agentPhotosBody) agentPhotosBody.innerHTML = '';
            }

            function renderAgentFeePhotos(photos) {
                if (!agentPhotosBody) return;
                closeAgentPhotoSidePreview();
                if (!photos.length) {
                    agentPhotosBody.innerHTML = `<p class="pds-expense-agent-photos__empty">${escapeAttr(i18n.agentFeePhotosEmpty)}</p>`;
                    return;
                }
                agentPhotosBody.innerHTML = photos.map((p, idx) => `
                    <article class="pds-expense-agent-photos__card" data-photo-index="${idx}">
                        <button type="button"
                                class="pds-expense-agent-photos__thumb js-agent-photo-enlarge"
                                data-url="${escapeAttr(p.photo_url || '')}"
                                data-label="${escapeAttr(p.order_id ? ('Order #' + p.order_id) : i18n.imageView)}">
                            <img src="${escapeAttr(p.photo_url || '')}" alt="">
                        </button>
                        <div class="pds-expense-agent-photos__meta">
                            ${p.order_id ? `<span>Order #${escapeAttr(p.order_id)}</span>` : ''}
                            <strong>${escapeAttr(i18n.agentIncome)}: ${fmt(p.agent_amount)}</strong>
                        </div>
                    </article>
                `).join('');
                agentPhotosBody.querySelectorAll('.js-agent-photo-enlarge').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const url = btn.getAttribute('data-url');
                        if (!url) return;
                        const card = btn.closest('.pds-expense-agent-photos__card');
                        agentPhotosBody.querySelectorAll('.pds-expense-agent-photos__card.is-active').forEach((el) => {
                            el.classList.remove('is-active');
                        });
                        card?.classList.add('is-active');
                        openAgentPhotoSidePreview(url, btn.getAttribute('data-label') || i18n.imageView);
                    });
                });
            }

            async function openAgentFeePhotos(date) {
                const day = date || dateInput?.value || '';
                if (!day || !agentPhotosModal) return;
                if (agentPhotosTitle) {
                    agentPhotosTitle.textContent = i18n.agentFeePhotos + ' · ' + day;
                }
                if (agentPhotosBody) {
                    agentPhotosBody.innerHTML = '<p class="pds-expense-agent-photos__empty">...</p>';
                }
                closeAgentPhotoSidePreview();
                agentPhotosModal.hidden = false;
                try {
                    const url = routes.agentFeePhotos
                        + '?date=' + encodeURIComponent(day)
                        + (selectedBranchId ? '&branch_id=' + encodeURIComponent(selectedBranchId) : '');
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message || i18n.noticeError);
                    }
                    renderAgentFeePhotos(Array.isArray(data.photos) ? data.photos : []);
                } catch (err) {
                    if (agentPhotosBody) {
                        agentPhotosBody.innerHTML = `<p class="pds-expense-agent-photos__empty">${escapeAttr(err.message || i18n.noticeError)}</p>`;
                    }
                }
            }

            function fmt(n) {
                return Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
            }

            function riderFuelRow(amount) {
                return {
                    subject: i18n.riderFuelSubject,
                    amount: Number(amount || 0),
                    locked: true,
                    lockKind: 'fuel',
                };
            }

            function agentFeeRow(amount) {
                return {
                    subject: i18n.agentFeeSubject,
                    amount: Number(amount || 0),
                    locked: true,
                    lockKind: 'agent',
                };
            }

            async function fetchRiderFuelAmount(date) {
                try {
                    const url = routes.riderFuel
                        + '?date=' + encodeURIComponent(date || '')
                        + (selectedBranchId ? '&branch_id=' + encodeURIComponent(selectedBranchId) : '');
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (!res.ok) return 0;
                    const data = await res.json();
                    return Number(data.amount || 0);
                } catch (e) {
                    return 0;
                }
            }

            async function fetchAgentFeeAmount(date) {
                try {
                    const url = routes.agentFee
                        + '?date=' + encodeURIComponent(date || '')
                        + (selectedBranchId ? '&branch_id=' + encodeURIComponent(selectedBranchId) : '');
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (!res.ok) return 0;
                    const data = await res.json();
                    return Number(data.amount || 0);
                } catch (e) {
                    return 0;
                }
            }

            async function refreshLockedRiderFuelAmount() {
                const rows = rowsEl.querySelectorAll('.pds-expense-modal__row.is-locked');
                for (const row of rows) {
                    const amountInput = row.querySelector('.js-amount');
                    if (!amountInput) continue;
                    const kind = row.dataset.lockKind || 'fuel';
                    amountInput.value = kind === 'agent'
                        ? await fetchAgentFeeAmount(dateInput.value)
                        : await fetchRiderFuelAmount(dateInput.value);
                }
                refreshFormTotal();
            }

            function setFormViewMode(on) {
                viewingOnly = !!on;
                formModal?.classList.toggle('is-view', viewingOnly);
                if (dateInput) {
                    dateInput.readOnly = viewingOnly;
                    dateInput.disabled = viewingOnly;
                }
                const addRowBtn = document.getElementById('expense-add-row');
                const saveBtn = document.getElementById('expense-form-save');
                const changeBtn = document.getElementById('expense-image-view-change');
                if (addRowBtn) addRowBtn.hidden = viewingOnly;
                if (saveBtn) saveBtn.hidden = viewingOnly;
                if (changeBtn) changeBtn.hidden = viewingOnly;
            }

            async function openForm(mode, card) {
                if (mode === 'create' && !canEdit) return;
                if (mode === 'edit' && (!canEdit || isCardGenerated(card))) {
                    mode = 'view';
                }
                setFormViewMode(mode === 'view');
                editingId = (mode === 'edit' || mode === 'view') && card ? Number(card.dataset.id) : null;
                titleEl.textContent = viewingOnly ? i18n.viewTitle : (editingId ? i18n.editTitle : i18n.addTitle);
                dateInput.value = card?.dataset.date || @json($today);
                rowsEl.innerHTML = '';
                let items = [];
                if (card?.dataset.items) {
                    try {
                        const parsed = JSON.parse(card.dataset.items);
                        if (Array.isArray(parsed) && parsed.length) items = parsed;
                    } catch (e) {}
                }
                if (!editingId) {
                    const fuelAmt = await fetchRiderFuelAmount(dateInput.value);
                    const agentAmt = await fetchAgentFeeAmount(dateInput.value);
                    items = [riderFuelRow(fuelAmt)];
                    if (agentAmt > 0) {
                        items.push(agentFeeRow(agentAmt));
                    }
                    items.push({ subject: '', amount: '' });
                } else if (!items.some((row) => row.locked)) {
                    const fuelAmt = await fetchRiderFuelAmount(dateInput.value);
                    const agentAmt = await fetchAgentFeeAmount(dateInput.value);
                    const manual = items.slice();
                    items = [riderFuelRow(fuelAmt)];
                    if (agentAmt > 0) {
                        items.push(agentFeeRow(agentAmt));
                    }
                    items = items.concat(manual);
                } else {
                    // Ensure agent fee locked row appears when amount exists.
                    const agentAmt = await fetchAgentFeeAmount(dateInput.value);
                    const hasAgent = items.some((row) => row.lockKind === 'agent' || row.subject === i18n.agentFeeSubject);
                    if (agentAmt > 0 && !hasAgent) {
                        const fuelIdx = items.findIndex((row) => row.lockKind === 'fuel' || row.subject === i18n.riderFuelSubject);
                        const insertAt = fuelIdx >= 0 ? fuelIdx + 1 : 0;
                        items.splice(insertAt, 0, agentFeeRow(agentAmt));
                    }
                }
                items.forEach(addRow);
                closeImageView();
                formModal.hidden = false;
            }

            function closeForm() {
                closeImageView();
                closeAgentFeePhotos();
                formModal.hidden = true;
                editingId = null;
                setFormViewMode(false);
            }

            function isRealImage(item) {
                const path = String(item?.image || '').trim();
                const url = String(item?.image_url || '').trim();
                return (path !== '' && path !== demoImagePath) || url !== '';
            }

            function setImagePreview(btn, preview, url) {
                if (!btn) return;
                if (url) {
                    btn.classList.remove('is-empty');
                    if (preview && preview.parentElement === btn) {
                        preview.hidden = false;
                        preview.src = url;
                    } else {
                        btn.innerHTML = `<span class="pds-expense-modal__image-thumb"><img src="${escapeAttr(url)}" alt="" class="js-image-preview"></span>`;
                    }
                } else {
                    btn.classList.add('is-empty');
                    btn.innerHTML = '<i class="fas fa-camera" aria-hidden="true"></i>';
                }
                syncImageButtonMeta(btn);
            }

            function refreshFormTotal() {
                const totalEl = document.getElementById('expense-form-total');
                if (!totalEl || !rowsEl) return;
                let total = 0;
                rowsEl.querySelectorAll('.js-amount').forEach((input) => {
                    const n = parseFloat(input.value || '0');
                    if (!isNaN(n)) total += n;
                });
                totalEl.textContent = Math.round(total).toLocaleString();
            }

            function addRow(item) {
                const isLockedAuto = !!item?.locked;
                const locked = isLockedAuto || viewingOnly;
                const lockKind = item?.lockKind
                    || (item?.subject === i18n.agentFeeSubject ? 'agent' : (isLockedAuto ? 'fuel' : ''));
                const row = document.createElement('div');
                row.className = 'pds-expense-modal__row' + (locked ? ' is-locked' : '');
                if (lockKind) {
                    row.dataset.lockKind = lockKind;
                }
                const amountVal = item?.amount === '' || item?.amount == null ? (locked ? '0' : '') : item.amount;
                const existingImage = isRealImage(item) ? (item?.image || '') : '';
                const imageUrl = isRealImage(item) ? (item?.image_url || '') : '';
                const lockHint = lockKind === 'agent' ? i18n.agentFeeHint : @json(__('message.expenses_rider_fuel_hint'));
                const showImage = !isLockedAuto;
                const showAgentPhotosBtn = isLockedAuto && lockKind === 'agent';
                row.innerHTML = `
                    <input type="text" class="form-control js-subject" placeholder="${@json(__('message.expenses_subject'))}" value="${escapeAttr(item?.subject || '')}" ${locked ? 'readonly' : ''}>
                    <input type="number" min="0" step="1" class="form-control js-amount" placeholder="0" value="${amountVal}" ${locked ? 'readonly' : ''}>
                    ${showImage
                        ? `<div class="pds-expense-modal__image-wrap">
                            <input type="file" class="js-image-input" accept="image/*" hidden>
                            <input type="hidden" class="js-existing-image" value="${escapeAttr(existingImage)}">
                            <button type="button" class="pds-expense-modal__image-btn${imageUrl ? ' has-image' : ' is-empty'}" title="${escapeAttr(imageUrl ? i18n.imageView : i18n.imageUpload)}">
                                ${imageUrl
                                    ? `<span class="pds-expense-modal__image-thumb"><img src="${escapeAttr(imageUrl)}" alt="" class="js-image-preview"></span>`
                                    : '<i class="fas fa-camera" aria-hidden="true"></i>'}
                            </button>
                        </div>`
                        : (showAgentPhotosBtn
                            ? `<div class="pds-expense-modal__image-wrap">
                                <button type="button" class="pds-expense-modal__image-btn has-image js-agent-fee-photos" title="${escapeAttr(i18n.agentFeeViewPhotos)}">
                                    <i class="fas fa-image" aria-hidden="true"></i>
                                </button>
                            </div>`
                            : `<span class="pds-expense-modal__image-spacer"></span>`)}
                    ${locked
                        ? `<span class="pds-expense-modal__row-lock" title="${viewingOnly ? escapeAttr(i18n.lockedHint) : escapeAttr(lockHint)}">🔒</span>`
                        : `<button type="button" class="pds-expense-modal__row-del" title="Remove">&times;</button>`}
                `;
                if (showImage) {
                    const fileInput = row.querySelector('.js-image-input');
                    const imageBtn = row.querySelector('.pds-expense-modal__image-btn');
                    const existingInput = row.querySelector('.js-existing-image');
                    imageBtn?.addEventListener('click', () => {
                        const previewUrl = getImageButtonUrl(imageBtn);
                        if (previewUrl) {
                            openImageView(previewUrl, row);
                            return;
                        }
                        if (viewingOnly) return;
                        fileInput?.click();
                    });
                    fileInput?.addEventListener('change', () => {
                        if (viewingOnly) return;
                        const file = fileInput.files?.[0];
                        if (!file) return;
                        setImagePreview(imageBtn, null, URL.createObjectURL(file));
                        if (existingInput) existingInput.value = '';
                    });
                }
                if (showAgentPhotosBtn) {
                    row.querySelector('.js-agent-fee-photos')?.addEventListener('click', () => {
                        openAgentFeePhotos(dateInput?.value || '');
                    });
                }
                const delBtn = row.querySelector('.pds-expense-modal__row-del');
                row.querySelector('.js-amount')?.addEventListener('input', refreshFormTotal);
                if (delBtn) {
                    delBtn.addEventListener('click', () => {
                        if (rowsEl.querySelectorAll('.pds-expense-modal__row:not(.is-locked)').length <= 1) {
                            row.querySelector('.js-subject').value = '';
                            row.querySelector('.js-amount').value = '';
                            const imageBtn = row.querySelector('.pds-expense-modal__image-btn');
                            const existingInput = row.querySelector('.js-existing-image');
                            const fileInput = row.querySelector('.js-image-input');
                            setImagePreview(imageBtn, null, '');
                            if (existingInput) existingInput.value = '';
                            if (fileInput) fileInput.value = '';
                            refreshFormTotal();
                            return;
                        }
                        row.remove();
                        refreshFormTotal();
                    });
                }
                rowsEl.appendChild(row);
                refreshFormTotal();
            }

            dateInput.addEventListener('change', function () {
                if (!formModal.hidden && !viewingOnly) {
                    refreshLockedRiderFuelAmount();
                }
            });

            function escapeAttr(s) {
                return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }

            function collectItems() {
                const items = [];
                rowsEl.querySelectorAll('.pds-expense-modal__row').forEach((row) => {
                    const subject = (row.querySelector('.js-subject')?.value || '').trim();
                    const amount = parseFloat(row.querySelector('.js-amount')?.value || '0');
                    if (!subject) return;
                    items.push({
                        subject,
                        amount: isNaN(amount) ? 0 : amount,
                        row,
                    });
                });
                return items;
            }

            async function saveForm() {
                if (!canEdit || viewingOnly) return;
                if (editingId) {
                    const card = document.querySelector(`.pds-expense-card[data-id="${editingId}"]`);
                    if (isCardGenerated(card)) {
                        showNotice(i18n.lockedHint, i18n.generatedLabel);
                        return;
                    }
                }
                const items = collectItems();
                if (!items.length) {
                    showNotice(i18n.subjectRequired, i18n.editTitle);
                    return;
                }
                const missingImage = items.find((item) => {
                    const fileInput = item.row.querySelector('.js-image-input');
                    // Locked rows (Rider ဆီဖိုး) have no image input.
                    if (!fileInput) return false;
                    const existingImage = (item.row.querySelector('.js-existing-image')?.value || '').trim();
                    const file = fileInput.files?.[0];
                    return !file && (!existingImage || existingImage === demoImagePath);
                });
                if (missingImage) {
                    showNotice(i18n.imageRequired, i18n.editTitle);
                    return;
                }
                const fd = new FormData();
                fd.append('expense_date', dateInput.value);
                if (selectedBranchId) {
                    fd.append('branch_id', String(selectedBranchId));
                }
                items.forEach((item, index) => {
                    fd.append(`items[${index}][subject]`, item.subject);
                    fd.append(`items[${index}][amount]`, String(item.amount));
                    const existingImage = item.row.querySelector('.js-existing-image')?.value || '';
                    if (existingImage && existingImage !== demoImagePath) {
                        fd.append(`items[${index}][existing_image]`, existingImage);
                    }
                    const file = item.row.querySelector('.js-image-input')?.files?.[0];
                    if (file) {
                        fd.append(`items[${index}][image]`, file);
                    }
                });
                const url = editingId
                    ? routes.update.replace('__ID__', String(editingId))
                    : routes.store;
                if (editingId) {
                    fd.append('_method', 'PUT');
                }
                const btn = document.getElementById('expense-form-save');
                btn.disabled = true;
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(json.message || Object.values(json.errors || {})[0]?.[0] || 'Save failed');
                    }
                    window.location.reload();
                } catch (err) {
                    showNotice(err.message || 'Save failed', i18n.noticeError);
                } finally {
                    btn.disabled = false;
                }
            }

            function isCardGenerated(card) {
                return card?.dataset?.generated === '1';
            }

            function applyViewButton(btn) {
                if (!btn) return;
                btn.disabled = false;
                btn.classList.remove('is-edit');
                btn.classList.add('is-view');
                btn.dataset.action = 'view';
                btn.title = i18n.viewTitle;
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'fas fa-eye';
                const label = btn.querySelector('span');
                if (label) label.textContent = i18n.viewLabel;
            }

            function lockGeneratedCard(card) {
                if (!card) return;
                card.dataset.generated = '1';
                card.classList.add('is-generated');
                applyViewButton(card.querySelector('[data-action="edit"], [data-action="view"]'));
                const del = card.querySelector('[data-action="delete"]');
                if (del) {
                    del.disabled = true;
                    del.title = i18n.lockedHint;
                }
                const gen = card.querySelector('[data-action="generate"]');
                if (gen) {
                    gen.disabled = true;
                    gen.classList.add('is-done');
                    const label = gen.querySelector('span');
                    if (label) label.textContent = i18n.generatedLabel;
                }
            }

            async function confirmDelete() {
                if (!deletingId) return;
                const btn = document.getElementById('expense-delete-yes');
                btn.disabled = true;
                try {
                    const res = await fetch(routes.destroy.replace('__ID__', String(deletingId)), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(json.message || 'Delete failed');
                    window.location.reload();
                } catch (err) {
                    showNotice(err.message || 'Delete failed', i18n.noticeError);
                } finally {
                    btn.disabled = false;
                }
            }

            async function generateSummary(card) {
                const id = Number(card?.dataset?.id);
                if (!id) return;
                if (card?.dataset?.canGenerate !== '1') {
                    showNotice(i18n.generateNextDay, i18n.generateLabel);
                    return;
                }
                const btn = card.querySelector('[data-action="generate"]');
                if (btn) btn.disabled = true;
                try {
                    const res = await fetch(routes.generate.replace('__ID__', String(id)), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok) throw new Error(json.message || 'Generate failed');
                    lockGeneratedCard(card);
                    showNotice(json.message || i18n.generateOk, i18n.generateLabel);
                } catch (err) {
                    showNotice(err.message || 'Generate failed', i18n.noticeError);
                    if (btn && !isCardGenerated(card)) btn.disabled = false;
                }
            }

            document.getElementById('expense-add-row')?.addEventListener('click', () => {
                if (viewingOnly) return;
                addRow({ subject: '', amount: '' });
            });
            document.getElementById('expense-form-cancel')?.addEventListener('click', closeForm);
            document.getElementById('expense-form-save')?.addEventListener('click', saveForm);
            formModal.querySelector('[data-close="form"]')?.addEventListener('click', closeForm);

            document.getElementById('expense-delete-no')?.addEventListener('click', () => {
                deleteModal.hidden = true;
                deletingId = null;
            });
            document.getElementById('expense-delete-yes')?.addEventListener('click', confirmDelete);
            deleteModal.querySelector('[data-close="delete"]')?.addEventListener('click', () => {
                deleteModal.hidden = true;
                deletingId = null;
            });

            document.getElementById('expense-notice-ok')?.addEventListener('click', closeNotice);
            noticeModal?.querySelector('[data-close="notice"]')?.addEventListener('click', closeNotice);

            document.getElementById('expense-image-view-close')?.addEventListener('click', closeImageView);
            imageViewModal?.querySelector('[data-close="image-view"]')?.addEventListener('click', closeImageView);
            document.getElementById('expense-image-view-change')?.addEventListener('click', () => {
                if (viewingOnly) return;
                const row = imageViewRow;
                closeImageView();
                row?.querySelector('.js-image-input')?.click();
            });

            document.getElementById('expense-agent-photos-close')?.addEventListener('click', closeAgentFeePhotos);
            agentPhotosModal?.querySelector('[data-close="agent-photos"]')?.addEventListener('click', closeAgentFeePhotos);
            document.getElementById('expense-agent-photo-preview-close')?.addEventListener('click', closeAgentPhotoSidePreview);

            document.getElementById('expenses-board')?.addEventListener('click', (e) => {
                const agentPhotosBtn = e.target.closest('.js-agent-fee-photos');
                if (agentPhotosBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    openAgentFeePhotos(agentPhotosBtn.getAttribute('data-date') || '');
                    return;
                }
                const card = e.target.closest('.pds-expense-card');
                if (!card || card.classList.contains('pds-expense-card--add')) return;
                const thumb = e.target.closest('.js-card-image');
                if (thumb) {
                    const imageUrl = thumb.dataset.image || '';
                    const subject = (thumb.dataset.subject || '').trim();
                    openForm(isCardGenerated(card) || !canEdit ? 'view' : 'edit', card).then(() => {
                        if (!imageUrl) return;
                        const row = Array.from(rowsEl.querySelectorAll('.pds-expense-modal__row')).find((el) => {
                            return (el.querySelector('.js-subject')?.value || '').trim() === subject;
                        });
                        openImageView(imageUrl, row || null);
                    });
                    return;
                }
                const btn = e.target.closest('[data-action]');
                if (!btn) {
                    if (e.target.closest('.pds-expense-card__header, .pds-expense-card__body')) {
                        openForm(isCardGenerated(card) || !canEdit ? 'view' : 'edit', card);
                    }
                    return;
                }
                if (btn.dataset.action === 'view') {
                    openForm('view', card);
                } else if (btn.dataset.action === 'edit') {
                    openForm('edit', card);
                } else if (btn.dataset.action === 'generate') {
                    if (!canEdit || isCardGenerated(card)) return;
                    if (card?.dataset?.canGenerate !== '1') {
                        showNotice(i18n.generateNextDay, i18n.generateLabel);
                        return;
                    }
                    generateSummary(card);
                } else if (btn.dataset.action === 'delete') {
                    if (!canEdit || isCardGenerated(card)) {
                        showNotice(i18n.lockedHint, i18n.generatedLabel);
                        return;
                    }
                    deletingId = Number(card.dataset.id);
                    deleteModal.hidden = false;
                }
            });
        })();
    </script>
</x-master-layout>

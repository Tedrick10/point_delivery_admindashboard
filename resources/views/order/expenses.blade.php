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

            <div class="pds-expenses-toolbar">
                <div class="pds-expenses-toolbar__month">
                    <div class="pds-expenses-month-nav">
                        <a href="{{ route('order.expenses', ['month' => $prevMonth]) }}" class="pds-expenses-month-nav__btn" title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="pds-expenses-month-nav__label">{{ $monthLabel }}</span>
                        <a href="{{ route('order.expenses', ['month' => $nextMonth]) }}" class="pds-expenses-month-nav__btn" title="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <form method="GET" action="{{ route('order.expenses') }}" class="pds-expenses-filter" id="expensesFilterForm">
                    <input type="hidden" name="month" value="{{ $monthValue }}">
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
                            'image' => $i->hasImage() ? $i->image : null,
                            'image_url' => $i->imageUrl(),
                            'locked' => $i->source === \App\Models\ExpenseItem::SOURCE_RIDER_FUEL,
                        ])->values();
                    @endphp
                    <article class="pds-expense-card" data-id="{{ $card->id }}"
                             data-date="{{ $card->expense_date->format('Y-m-d') }}"
                             data-generated="{{ in_array((int) $card->id, $generatedCardIds ?? [], true) ? '1' : '0' }}"
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
                                $manualItems = $displayItems->reject(
                                    fn ($i) => $i->source === \App\Models\ExpenseItem::SOURCE_RIDER_FUEL
                                );
                            @endphp
                            @foreach($riderFuelItems as $item)
                                <div class="pds-expense-card__rider-fuel">
                                    <span>{{ $item->subject }}</span>
                                    <em>{{ number_format($item->amount) }}</em>
                                </div>
                            @endforeach
                            <ul class="pds-expense-card__list">
                                @forelse($manualItems as $item)
                                    <li>
                                        <span>{{ $item->subject }}</span>
                                        <em>{{ number_format($item->amount) }}</em>
                                    </li>
                                @empty
                                    @if($riderFuelItems->isEmpty())
                                        <li class="is-empty">{{ __('message.no_record_found') }}</li>
                                    @endif
                                @endforelse
                            </ul>
                        </div>
                        @if($canEdit)
                            <div class="pds-expense-card__actions">
                                <button type="button" class="pds-expense-card__btn is-edit" data-action="edit" title="{{ __('message.edit') }}">
                                    <i class="fas fa-pen"></i>
                                    <span>{{ __('message.edit') }}</span>
                                </button>
                                <button type="button" class="pds-expense-card__btn is-generate {{ in_array((int) $card->id, $generatedCardIds ?? [], true) ? 'is-done' : '' }}"
                                        data-action="generate"
                                        title="{{ __('message.expense_summary_generate') }}">
                                    <i class="fas fa-file-export"></i>
                                    <span>{{ in_array((int) $card->id, $generatedCardIds ?? [], true) ? __('message.expense_summary_generated_btn') : __('message.expense_summary_generate') }}</span>
                                </button>
                                <button type="button" class="pds-expense-card__btn is-del" data-action="delete" title="{{ __('message.delete') }}">
                                    <i class="fas fa-trash"></i>
                                    <span>{{ __('message.delete') }}</span>
                                </button>
                            </div>
                        @endif
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

                @if($canEdit)
                    <button type="button" class="pds-expense-card pds-expense-card--add" id="expenses-add-card" title="{{ __('message.expenses_add_card') }}">
                        <span class="pds-expense-card--add__plus">+</span>
                        <span class="pds-expense-card--add__label">{{ __('message.expenses_add_card') }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Create / Edit modal --}}
    <div class="pds-expense-modal" id="expense-form-modal" hidden>
        <div class="pds-expense-modal__backdrop" data-close="form"></div>
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
            <footer class="pds-expense-modal__footer">
                <button type="button" class="pds-expense-modal__cancel" id="expense-form-cancel" title="{{ __('message.cancel') }}">
                    <i class="fas fa-times"></i>
                </button>
                <button type="button" class="pds-expense-modal__save" id="expense-form-save" title="{{ __('message.save') }}">
                    <i class="fas fa-check"></i>
                </button>
            </footer>
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

    {{-- Image preview --}}
    <div class="pds-expense-modal pds-expense-modal--image-view" id="expense-image-view-modal" hidden>
        <div class="pds-expense-modal__backdrop" data-close="image-view"></div>
        <div class="pds-expense-modal__dialog pds-expense-modal__dialog--image" role="dialog" aria-modal="true">
            <header class="pds-expense-modal__header">
                <h5 id="expense-image-view-title">{{ __('message.expenses_image_view') }}</h5>
            </header>
            <div class="pds-expense-image-view">
                <img id="expense-image-view-img" src="" alt="">
            </div>
            <footer class="pds-expense-modal__footer">
                <button type="button" class="pds-expense-modal__cancel" id="expense-image-view-change">{{ __('message.expenses_image_change') }}</button>
                <button type="button" class="pds-expense-modal__save" id="expense-image-view-close">{{ __('message.close') }}</button>
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
            if (!@json($canEdit)) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const routes = {
                store: @json(route('order.expenses.store')),
                update: @json(route('order.expenses.update', ['id' => '__ID__'])),
                destroy: @json(route('order.expenses.destroy', ['id' => '__ID__'])),
                generate: @json(route('order.expenses.generate', ['id' => '__ID__'])),
                riderFuel: @json(route('order.expenses.rider-fuel-total')),
            };
            const demoImagePath = @json(\App\Models\ExpenseItem::DEMO_IMAGE);
            const i18n = {
                addTitle: @json(__('message.expenses_add_card')),
                editTitle: @json(__('message.expenses_edit_card')),
                subjectRequired: @json(__('message.expenses_items_required')),
                saveOk: @json(__('message.expenses_saved')),
                deleteOk: @json(__('message.expenses_deleted')),
                riderFuelSubject: @json(__('message.expenses_rider_fuel')),
                generateLabel: @json(__('message.expense_summary_generate')),
                generatedLabel: @json(__('message.expense_summary_generated_btn')),
                generateOk: @json(__('message.expense_summary_generated')),
                noticeError: @json(__('message.error')),
                imageUpload: @json(__('message.expenses_image_upload')),
                imageView: @json(__('message.expenses_image_view')),
                imageChange: @json(__('message.expenses_image_change')),
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
                imageViewRow = row || null;
                imageViewImg.src = url;
                const subject = row?.querySelector('.js-subject')?.value?.trim() || '';
                if (imageViewTitle) {
                    imageViewTitle.textContent = subject || i18n.imageView;
                }
                imageViewModal.hidden = false;
            }

            function closeImageView() {
                if (imageViewModal) imageViewModal.hidden = true;
                if (imageViewImg) imageViewImg.removeAttribute('src');
                imageViewRow = null;
            }

            function fmt(n) {
                return Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
            }

            function riderFuelRow(amount) {
                return {
                    subject: i18n.riderFuelSubject,
                    amount: Number(amount || 0),
                    locked: true,
                };
            }

            async function fetchRiderFuelAmount(date) {
                try {
                    const url = routes.riderFuel + '?date=' + encodeURIComponent(date || '');
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
                const lockedAmount = rowsEl.querySelector('.pds-expense-modal__row.is-locked .js-amount');
                if (!lockedAmount) return;
                lockedAmount.value = await fetchRiderFuelAmount(dateInput.value);
            }

            async function openForm(mode, card) {
                editingId = mode === 'edit' && card ? Number(card.dataset.id) : null;
                titleEl.textContent = editingId ? i18n.editTitle : i18n.addTitle;
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
                    // Add Expense Card: always start with locked Rider ဆီဖိုး.
                    const fuelAmt = await fetchRiderFuelAmount(dateInput.value);
                    items = [riderFuelRow(fuelAmt), { subject: '', amount: '' }];
                } else if (!items.some((row) => row.locked)) {
                    const fuelAmt = await fetchRiderFuelAmount(dateInput.value);
                    items = [riderFuelRow(fuelAmt), ...items];
                }
                items.forEach(addRow);
                formModal.hidden = false;
            }

            function closeForm() {
                closeImageView();
                formModal.hidden = true;
                editingId = null;
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

            function addRow(item) {
                const locked = !!item?.locked;
                const row = document.createElement('div');
                row.className = 'pds-expense-modal__row' + (locked ? ' is-locked' : '');
                const amountVal = item?.amount === '' || item?.amount == null ? (locked ? '0' : '') : item.amount;
                const existingImage = isRealImage(item) ? (item?.image || '') : '';
                const imageUrl = isRealImage(item) ? (item?.image_url || '') : '';
                row.innerHTML = `
                    <input type="text" class="form-control js-subject" placeholder="${@json(__('message.expenses_subject'))}" value="${escapeAttr(item?.subject || '')}" ${locked ? 'readonly' : ''}>
                    <input type="number" min="0" step="1" class="form-control js-amount" placeholder="0" value="${amountVal}" ${locked ? 'readonly' : ''}>
                    ${locked
                        ? `<span class="pds-expense-modal__image-spacer"></span>`
                        : `<div class="pds-expense-modal__image-wrap">
                            <input type="file" class="js-image-input" accept="image/*" hidden>
                            <input type="hidden" class="js-existing-image" value="${escapeAttr(existingImage)}">
                            <button type="button" class="pds-expense-modal__image-btn${imageUrl ? ' has-image' : ' is-empty'}" title="${escapeAttr(imageUrl ? i18n.imageView : i18n.imageUpload)}">
                                ${imageUrl
                                    ? `<span class="pds-expense-modal__image-thumb"><img src="${escapeAttr(imageUrl)}" alt="" class="js-image-preview"></span>`
                                    : '<i class="fas fa-camera" aria-hidden="true"></i>'}
                            </button>
                        </div>`}
                    ${locked
                        ? `<span class="pds-expense-modal__row-lock" title="${@json(__('message.expenses_rider_fuel_hint'))}">🔒</span>`
                        : `<button type="button" class="pds-expense-modal__row-del" title="Remove">&times;</button>`}
                `;
                if (!locked) {
                    const fileInput = row.querySelector('.js-image-input');
                    const imageBtn = row.querySelector('.pds-expense-modal__image-btn');
                    const existingInput = row.querySelector('.js-existing-image');
                    imageBtn?.addEventListener('click', () => {
                        const previewUrl = getImageButtonUrl(imageBtn);
                        if (previewUrl) {
                            openImageView(previewUrl, row);
                            return;
                        }
                        fileInput?.click();
                    });
                    fileInput?.addEventListener('change', () => {
                        const file = fileInput.files?.[0];
                        if (!file) return;
                        setImagePreview(imageBtn, null, URL.createObjectURL(file));
                        if (existingInput) existingInput.value = '';
                    });
                }
                const delBtn = row.querySelector('.pds-expense-modal__row-del');
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
                            return;
                        }
                        row.remove();
                    });
                }
                rowsEl.appendChild(row);
            }

            dateInput.addEventListener('change', function () {
                if (!formModal.hidden) {
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
                const items = collectItems();
                if (!items.length) {
                    showNotice(i18n.subjectRequired, i18n.editTitle);
                    return;
                }
                const fd = new FormData();
                fd.append('expense_date', dateInput.value);
                items.forEach((item, index) => {
                    fd.append(`items[${index}][subject]`, item.subject);
                    fd.append(`items[${index}][amount]`, String(item.amount));
                    const existingImage = item.row.querySelector('.js-existing-image')?.value || '';
                    if (existingImage) {
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
                    card.dataset.generated = '1';
                    if (btn) {
                        btn.classList.add('is-done');
                        const label = btn.querySelector('span');
                        if (label) label.textContent = i18n.generatedLabel;
                    }
                    showNotice(json.message || i18n.generateOk, i18n.generateLabel);
                } catch (err) {
                    showNotice(err.message || 'Generate failed', i18n.noticeError);
                } finally {
                    if (btn) btn.disabled = false;
                }
            }

            document.getElementById('expenses-add-card')?.addEventListener('click', () => openForm('create'));
            document.getElementById('expense-add-row')?.addEventListener('click', () => addRow({ subject: '', amount: '' }));
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
                const row = imageViewRow;
                closeImageView();
                row?.querySelector('.js-image-input')?.click();
            });

            document.getElementById('expenses-board')?.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                const card = btn.closest('.pds-expense-card');
                if (!card || card.classList.contains('pds-expense-card--add')) return;
                if (btn.dataset.action === 'edit') {
                    openForm('edit', card);
                } else if (btn.dataset.action === 'generate') {
                    generateSummary(card);
                } else if (btn.dataset.action === 'delete') {
                    deletingId = Number(card.dataset.id);
                    deleteModal.hidden = false;
                }
            });
        })();
    </script>
</x-master-layout>

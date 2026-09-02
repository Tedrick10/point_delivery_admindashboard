<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-expenses-page pds-expense-summary-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-chart-pie" aria-hidden="true"></i>
                        <span>{{ __('message.order') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.expense_summary_subtitle') }}</p>
                </div>
                <div class="pds-rider-hero__stat">
                    <span class="pds-rider-hero__stat-value">{{ number_format($totalAko) }}</span>
                    <span class="pds-rider-hero__stat-label">{{ __('message.expense_summary_ako_given') }}</span>
                </div>
            </div>

            <div class="pds-expenses-toolbar">
                <div class="pds-expenses-toolbar__month">
                    <div class="pds-expenses-month-nav">
                        <a href="{{ route('order.expense-summary', ['month' => $prevMonth]) }}" class="pds-expenses-month-nav__btn" title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="pds-expenses-month-nav__label">{{ $monthLabel }}</span>
                        <a href="{{ route('order.expense-summary', ['month' => $nextMonth]) }}" class="pds-expenses-month-nav__btn" title="Next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <form method="GET" action="{{ route('order.expense-summary') }}" class="pds-expenses-filter" id="expenseSummaryFilterForm">
                    <input type="hidden" name="month" value="{{ $monthValue }}">
                    <div class="pds-expenses-filter__field">
                        <label for="summary_from">{{ __('message.from_date') }}</label>
                        <input type="text" name="from_date" id="summary_from" class="pds-dispatch-input dispatch-datepicker"
                               value="{{ $filterFrom }}" autocomplete="off" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="pds-expenses-filter__field">
                        <label for="summary_to">{{ __('message.to_date') }}</label>
                        <input type="text" name="to_date" id="summary_to" class="pds-dispatch-input dispatch-datepicker"
                               value="{{ $filterTo }}" autocomplete="off" placeholder="dd-mm-yyyy">
                    </div>
                    <div class="pds-expenses-filter__actions">
                        <button type="submit" class="pds-daily-check-search-btn" title="{{ __('message.check') }}">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>{{ __('message.check') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="pds-expense-summary-table-wrap">
                <table class="pds-expense-summary-table">
                    <thead>
                        <tr>
                            <th>{{ __('message.expense_summary_no') }}</th>
                            <th>{{ __('message.date') }}</th>
                            <th>{{ __('message.expense_summary_income') }}</th>
                            <th>{{ __('message.expense_summary_expense') }}</th>
                            <th>{{ __('message.expense_summary_ako_given') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $index => $row)
                            @php
                                $card = $row->expenseCard;
                                $cardItems = $card?->items?->map(fn ($i) => [
                                    'subject' => $i->subject,
                                    'amount' => (float) $i->amount,
                                    'image' => $i->hasUploadedImage() ? $i->image : null,
                                    'image_url' => $i->imageUrl(),
                                    'locked' => $i->source === \App\Models\ExpenseItem::SOURCE_RIDER_FUEL,
                                ])->values() ?? collect();
                                $cardDate = $card?->expense_date?->format('Y-m-d')
                                    ?? $row->summary_date?->format('Y-m-d');
                                $dayKey = $row->summary_date?->format('Y-m-d');
                                $incomeItems = $incomeItemsByDate[$dayKey] ?? [];
                                $hasIncomeCard = count($incomeItems) > 0 || (float) $row->income > 0;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row->summary_date->format('d-m-Y') }}</td>
                                <td class="is-income">
                                    <span class="pds-expense-summary-amt-cell">
                                        <span class="pds-expense-summary-amt">{{ number_format($row->income) }}</span>
                                        <button type="button"
                                                class="pds-expense-summary-view js-summary-view-card"
                                                title="{{ __('message.expenses_view_income_card') }}"
                                                data-card-type="income"
                                                data-date="{{ $dayKey }}"
                                                data-items='@json($incomeItems)'
                                                @disabled(! $hasIncomeCard)>
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </button>
                                    </span>
                                </td>
                                <td class="is-expense">
                                    <span class="pds-expense-summary-amt-cell">
                                        <span class="pds-expense-summary-amt">{{ number_format($row->expense) }}</span>
                                        <button type="button"
                                                class="pds-expense-summary-view js-summary-view-card"
                                                title="{{ __('message.expenses_view_card') }}"
                                                data-card-type="expense"
                                                data-date="{{ $cardDate }}"
                                                data-items='@json($cardItems)'
                                                @disabled(! $card)>
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </button>
                                    </span>
                                </td>
                                <td class="is-ako {{ $row->ako_given < 0 ? 'is-neg' : '' }}">{{ number_format($row->ako_given) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="is-empty">{{ __('message.expense_summary_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="2">{{ __('message.total') }}</td>
                                <td class="is-income">{{ number_format($totalIncome) }}</td>
                                <td class="is-expense">{{ number_format($totalExpense) }}</td>
                                <td class="is-ako {{ $totalAko < 0 ? 'is-neg' : '' }}">{{ number_format($totalAko) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="pds-expense-modal is-view" id="expense-form-modal" hidden>
        <div class="pds-expense-modal__backdrop" data-close="form"></div>
        <div class="pds-expense-modal__stage">
            <div class="pds-expense-modal__dialog" role="dialog" aria-modal="true">
                <header class="pds-expense-modal__header">
                    <h5 id="expense-form-title">{{ __('message.expenses_view_card') }}</h5>
                    <div class="pds-expense-modal__date">
                        <label for="expense-date-input">{{ __('message.date') }}</label>
                        <input type="date" id="expense-date-input" class="form-control" readonly disabled>
                    </div>
                </header>
                <div class="pds-expense-modal__cols" id="expense-form-cols">
                    <span data-col="subject">{{ __('message.expenses_subject') }}</span>
                    <span data-col="amount">{{ __('message.amount') }}</span>
                    <span data-col="image">{{ __('message.image') }}</span>
                    <span></span>
                </div>
                <div class="pds-expense-modal__rows" id="expense-item-rows"></div>
                <div class="pds-expense-modal__total">
                    <span>{{ __('message.total_amount') }}</span>
                    <strong id="expense-form-total">0</strong>
                </div>
                <footer class="pds-expense-modal__footer">
                    <button type="button" class="pds-expense-modal__cancel" id="expense-form-cancel" title="{{ __('message.close') }}">
                        <i class="fas fa-times"></i>
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
            </aside>
        </div>
    </div>

    <script>
        (function bootSummaryFilters() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr('#summary_from, #summary_to', { dateFormat: 'd-m-Y', allowInput: true });
            } else {
                setTimeout(bootSummaryFilters, 40);
            }
        })();
    </script>
    <script>
        (function () {
            const demoImagePath = @json(\App\Models\ExpenseItem::DEMO_IMAGE);
            const i18n = {
                expenseTitle: @json(__('message.expenses_view_card')),
                incomeTitle: @json(__('message.expenses_view_income_card')),
                subjectLabel: @json(__('message.expenses_subject')),
                amountLabel: @json(__('message.amount')),
                imageLabel: @json(__('message.image')),
                osNameLabel: @json(__('message.expenses_income_os_name')),
                deliAmountLabel: @json(__('message.expenses_income_deli_amount')),
                imageView: @json(__('message.expenses_image_view')),
                lockedHint: @json(__('message.expense_summary_card_locked')),
            };
            const formModal = document.getElementById('expense-form-modal');
            const imageViewModal = document.getElementById('expense-image-view-modal');
            const imageViewImg = document.getElementById('expense-image-view-img');
            const imageViewTitle = document.getElementById('expense-image-view-title');
            const rowsEl = document.getElementById('expense-item-rows');
            const colsEl = document.getElementById('expense-form-cols');
            const dateInput = document.getElementById('expense-date-input');
            const titleEl = document.getElementById('expense-form-title');
            let activeCardType = 'expense';

            function escapeAttr(s) {
                return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;');
            }

            function isRealImage(item) {
                const path = String(item?.image || '').trim();
                const url = String(item?.image_url || '').trim();
                return (path !== '' && path !== demoImagePath) || url !== '';
            }

            function getImageButtonUrl(btn) {
                if (!btn || btn.classList.contains('is-empty')) return '';
                return btn.querySelector('.js-image-preview')?.getAttribute('src') || '';
            }

            function setColumnLabels(cardType) {
                if (!colsEl) return;
                const subject = colsEl.querySelector('[data-col="subject"]');
                const amount = colsEl.querySelector('[data-col="amount"]');
                const image = colsEl.querySelector('[data-col="image"]');
                if (cardType === 'income') {
                    if (subject) subject.textContent = i18n.osNameLabel;
                    if (amount) amount.textContent = i18n.deliAmountLabel;
                    if (image) image.hidden = true;
                    formModal?.classList.add('is-income-card');
                } else {
                    if (subject) subject.textContent = i18n.subjectLabel;
                    if (amount) amount.textContent = i18n.amountLabel;
                    if (image) image.hidden = false;
                    formModal?.classList.remove('is-income-card');
                }
            }

            function openImageView(url, row) {
                if (!url || !imageViewModal || !imageViewImg) return;
                rowsEl?.querySelectorAll('.pds-expense-modal__row.is-previewing').forEach((el) => {
                    el.classList.remove('is-previewing');
                });
                row?.classList.add('is-previewing');
                imageViewImg.src = url;
                const subject = row?.querySelector('.js-subject')?.value?.trim() || '';
                if (imageViewTitle) imageViewTitle.textContent = subject || i18n.imageView;
                imageViewModal.hidden = false;
                formModal?.classList.add('has-preview');
            }

            function closeImageView() {
                rowsEl?.querySelectorAll('.pds-expense-modal__row.is-previewing').forEach((el) => {
                    el.classList.remove('is-previewing');
                });
                if (imageViewModal) imageViewModal.hidden = true;
                if (imageViewImg) imageViewImg.removeAttribute('src');
                formModal?.classList.remove('has-preview');
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
                const isIncome = activeCardType === 'income';
                const isRiderFuel = !!item?.locked;
                const row = document.createElement('div');
                row.className = 'pds-expense-modal__row is-locked';
                const amountVal = item?.amount === '' || item?.amount == null ? '0' : item.amount;
                const imageUrl = !isIncome && isRealImage(item) ? (item?.image_url || '') : '';
                row.innerHTML = `
                    <input type="text" class="form-control js-subject" value="${escapeAttr(item?.subject || '')}" readonly>
                    <input type="number" class="form-control js-amount" value="${amountVal}" readonly>
                    ${isIncome
                        ? ''
                        : (isRiderFuel
                            ? `<span class="pds-expense-modal__image-spacer"></span>`
                            : `<div class="pds-expense-modal__image-wrap">
                                <button type="button" class="pds-expense-modal__image-btn${imageUrl ? ' has-image' : ' is-empty'}" title="${escapeAttr(imageUrl ? i18n.imageView : '')}">
                                    ${imageUrl
                                        ? `<span class="pds-expense-modal__image-thumb"><img src="${escapeAttr(imageUrl)}" alt="" class="js-image-preview"></span>`
                                        : '<i class="fas fa-camera" aria-hidden="true"></i>'}
                                </button>
                            </div>`)}
                    <span class="pds-expense-modal__row-lock" title="${escapeAttr(i18n.lockedHint)}">🔒</span>
                `;
                if (!isIncome) {
                    const imageBtn = row.querySelector('.pds-expense-modal__image-btn');
                    imageBtn?.addEventListener('click', () => {
                        const previewUrl = getImageButtonUrl(imageBtn);
                        if (previewUrl) openImageView(previewUrl, row);
                    });
                }
                rowsEl.appendChild(row);
                refreshFormTotal();
            }

            function openView(btn) {
                let items = [];
                try {
                    const parsed = JSON.parse(btn.dataset.items || '[]');
                    if (Array.isArray(parsed)) items = parsed;
                } catch (e) {}
                activeCardType = btn.dataset.cardType === 'income' ? 'income' : 'expense';
                if (titleEl) {
                    titleEl.textContent = activeCardType === 'income' ? i18n.incomeTitle : i18n.expenseTitle;
                }
                setColumnLabels(activeCardType);
                if (dateInput) dateInput.value = btn.dataset.date || '';
                rowsEl.innerHTML = '';
                if (!items.length) {
                    items = [{ subject: '', amount: 0, locked: true }];
                }
                items.forEach(addRow);
                closeImageView();
                formModal.hidden = false;
            }

            function closeForm() {
                closeImageView();
                formModal?.classList.remove('is-income-card');
                formModal.hidden = true;
            }

            document.getElementById('expense-form-cancel')?.addEventListener('click', closeForm);
            formModal?.querySelector('[data-close="form"]')?.addEventListener('click', closeForm);
            document.getElementById('expense-image-view-close')?.addEventListener('click', closeImageView);
            imageViewModal?.querySelector('[data-close="image-view"]')?.addEventListener('click', closeImageView);

            document.querySelectorAll('.js-summary-view-card').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (btn.disabled) return;
                    openView(btn);
                });
            });
        })();
    </script>
</x-master-layout>

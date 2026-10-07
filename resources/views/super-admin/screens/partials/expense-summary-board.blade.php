@php
    $rows = $expenseSummary['rows'] ?? collect();
    $incomeItemsByDate = $expenseSummary['incomeItemsByDate'] ?? [];
    $totalIncome = $expenseSummary['totalIncome'] ?? 0;
    $totalExpense = $expenseSummary['totalExpense'] ?? 0;
    $totalAko = $expenseSummary['totalAko'] ?? 0;
    $periodQuery = array_filter([
        'screen' => 'expense-summary',
        'period' => request('period'),
        'date' => request('date'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
    ], fn ($v) => $v !== null && $v !== '');
    $saBranchId = $expenseSummary['selectedBranchId'] ?? null;
    $saMonth = $expenseSummary['calendarMonth'] ?? now('Asia/Yangon')->format('Y-m');
    $saDayUrl = function ($ymd) use ($periodQuery, $saBranchId) {
        $day = \Carbon\Carbon::parse($ymd, 'Asia/Yangon');
        $label = $day->format('d-m-Y');

        return route('super-admin.screens.show', array_filter(array_merge($periodQuery, [
            'month' => $day->format('Y-m'),
            'branch_id' => $saBranchId,
            'from_date' => $label,
            'to_date' => $label,
        ])));
    };
    $saMonthUrl = function ($ym) use ($periodQuery, $saBranchId) {
        try {
            $m = \Carbon\Carbon::createFromFormat('Y-m', $ym, 'Asia/Yangon')->startOfMonth();
        } catch (\Throwable $e) {
            $m = now('Asia/Yangon')->startOfMonth();
        }

        return route('super-admin.screens.show', array_filter(array_merge($periodQuery, [
            'month' => $m->format('Y-m'),
            'branch_id' => $saBranchId,
            'from_date' => $m->copy()->startOfMonth()->format('d-m-Y'),
            'to_date' => $m->copy()->endOfMonth()->format('d-m-Y'),
        ])));
    };
@endphp

<section class="sa-module-panel sa-expense-summary-panel" data-sa-expense-summary>
    <header class="sa-expense-summary-head">
        <div class="sa-expense-summary-head__copy">
            <h3>{{ __('message.expense_summary_title') }}</h3>
            <p>{{ __('message.expense_summary_subtitle') }}</p>
        </div>
        <div class="sa-expense-summary-metrics" aria-label="{{ __('message.expense_summary_title') }}">
            <div class="sa-expense-summary-metric is-income">
                <span>{{ __('message.expense_summary_income') }}</span>
                <strong>{{ number_format($totalIncome) }}</strong>
            </div>
            <div class="sa-expense-summary-metric is-expense">
                <span>{{ __('message.expense_summary_expense') }}</span>
                <strong>{{ number_format($totalExpense) }}</strong>
            </div>
            <div class="sa-expense-summary-metric is-ako {{ $totalAko < 0 ? 'is-neg' : '' }}">
                <span>{{ __('message.expense_summary_ako_given') }}</span>
                <strong>{{ number_format($totalAko) }}</strong>
            </div>
        </div>
    </header>

    <div class="sa-expense-summary-branches">
        @include('partials._branch-tabs', [
            'branchTabs' => $expenseSummary['branchTabs'] ?? collect(),
            'selectedBranchId' => $expenseSummary['selectedBranchId'] ?? null,
            'branchTabCounts' => $expenseSummary['branchTabCounts'] ?? [],
            'allCount' => $expenseSummary['allBranchCount'] ?? null,
            'includeAll' => false,
            'routeName' => 'super-admin.screens.show',
            'routeQuery' => $periodQuery,
        ])
    </div>

    <div class="pds-expense-summary-split sa-expense-summary-split{{ empty($expenseSummary['tripleCanView'] ?? false) ? ' is-no-calendar' : '' }}">
        @if(! empty($expenseSummary['tripleCanView'] ?? false))
        @include('order.partials._expense-summary-calendar', ($expenseSummary ?? []) + [
            'calendarDayUrl' => $saDayUrl,
            'calendarMonthUrl' => $saMonthUrl,
        ])
        @endif

        <div class="pds-expense-summary-split__main sa-expense-summary-main">
            <form method="GET" action="{{ route('super-admin.screens.show', 'expense-summary') }}" class="sa-expense-summary-filter">
                @foreach($periodQuery as $key => $value)
                    @if($key !== 'from_date' && $key !== 'to_date')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="hidden" name="month" value="{{ $saMonth }}">
                <input type="hidden" name="branch_id" value="{{ $expenseSummary['branchFilter'] ?? '' }}">
                <label>
                    {{ __('message.from_date') }}
                    <input type="text" name="from_date" value="{{ $expenseSummary['filterFrom'] ?? '' }}" class="sa-expense-summary-date" autocomplete="off" placeholder="dd-mm-yyyy">
                </label>
                <label>
                    {{ __('message.to_date') }}
                    <input type="text" name="to_date" value="{{ $expenseSummary['filterTo'] ?? '' }}" class="sa-expense-summary-date" autocomplete="off" placeholder="dd-mm-yyyy">
                </label>
                <button type="submit">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span>{{ __('message.check') }}</span>
                </button>
            </form>

            <div class="sa-expense-summary-table-wrap">
                <table class="sa-module-table sa-expense-summary-table">
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
                                    'locked' => $i->isLockedSource(),
                                ])->values() ?? collect();
                                $cardDate = $card?->expense_date?->format('Y-m-d')
                                    ?? $row->summary_date?->format('Y-m-d');
                                $dayKey = $row->summary_date?->format('Y-m-d');
                                $incomeItems = $incomeItemsByDate[$dayKey] ?? [];
                                $hasIncomeCard = count($incomeItems) > 0 || (float) $row->income > 0;
                            @endphp
                            <tr>
                                <td class="is-no">{{ $index + 1 }}</td>
                                <td class="is-date">{{ $row->summary_date->format('d-m-Y') }}</td>
                                <td class="is-income">
                                    <span class="sa-expense-summary-amt-cell">
                                        <span>{{ number_format($row->income) }}</span>
                                        <button type="button"
                                                class="sa-expense-summary-view js-summary-view-card"
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
                                    <span class="sa-expense-summary-amt-cell">
                                        <span>{{ number_format($row->expense) }}</span>
                                        <button type="button"
                                                class="sa-expense-summary-view js-summary-view-card"
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
</section>

<div class="sa-expense-modal" id="expense-form-modal" hidden>
    <div class="sa-expense-modal__backdrop" data-close="form"></div>
    <div class="sa-expense-modal__stage">
        <div class="sa-expense-modal__dialog" role="dialog" aria-modal="true">
            <button type="button" class="sa-expense-modal__close" id="expense-form-cancel" title="{{ __('message.close') }}">
                <i class="fas fa-times"></i>
            </button>
            <header class="sa-expense-modal__header">
                <h5 id="expense-form-title">{{ __('message.expenses_view_card') }}</h5>
                <div class="sa-expense-modal__date">
                    <label for="expense-date-input">{{ __('message.date') }}</label>
                    <input type="date" id="expense-date-input" readonly disabled>
                </div>
            </header>
            <div class="sa-expense-modal__body">
                <div class="sa-expense-modal__cols" id="expense-form-cols">
                    <span data-col="subject">{{ __('message.expenses_subject') }}</span>
                    <span data-col="amount">{{ __('message.amount') }}</span>
                    <span data-col="image">{{ __('message.image') }}</span>
                    <span></span>
                </div>
                <div class="sa-expense-modal__rows" id="expense-item-rows"></div>
            </div>
            <div class="sa-expense-modal__total">
                <span>{{ __('message.total_amount') }}</span>
                <strong id="expense-form-total">0</strong>
            </div>
        </div>
        <aside class="sa-expense-modal__preview" id="expense-image-view-modal" hidden>
            <header class="sa-expense-modal__header">
                <h5 id="expense-image-view-title">{{ __('message.expenses_image_view') }}</h5>
                <button type="button" class="sa-expense-modal__preview-close" id="expense-image-view-close" title="{{ __('message.close') }}" data-close="image-view">
                    <i class="fas fa-times"></i>
                </button>
            </header>
            <div class="sa-expense-image-view">
                <img id="expense-image-view-img" src="" alt="">
            </div>
        </aside>
    </div>
</div>

<style>
    .sa-expense-modal[hidden] { display: none !important; }
    .sa-expense-modal {
        position: fixed; inset: 0; z-index: 1080;
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .sa-expense-modal__backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, 0.48); backdrop-filter: blur(4px); }
    .sa-expense-modal__stage {
        position: relative; z-index: 1; display: flex; align-items: stretch;
        gap: 16px; width: min(1180px, calc(100vw - 32px)); max-height: min(92vh, 780px);
    }
    .sa-expense-modal__dialog {
        position: relative;
        display: flex;
        flex-direction: column;
        flex: 1 1 560px;
        min-height: 0;
        max-height: 100%;
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
        overflow: hidden;
    }
    .sa-expense-modal__close {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 6;
        width: 36px;
        height: 36px;
        border: 1px solid rgba(254, 111, 7, 0.28);
        border-radius: 999px;
        background: #FFF4EC;
        color: #FE6F07;
        cursor: pointer;
        box-shadow: 0 6px 16px rgba(254, 111, 7, 0.12);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .sa-expense-modal__close:hover {
        background: #FE6F07;
        border-color: #FE6F07;
        color: #fff;
    }
    .sa-expense-modal__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-shrink: 0;
        padding: 20px 64px 12px 22px;
        background: #fff;
    }
    .sa-expense-modal__header h5 { margin: 0; font-size: 1.12rem; font-weight: 800; color: #14110F; }
    .sa-expense-modal__date { display: flex; flex-direction: column; gap: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #94a3b8; }
    .sa-expense-modal__date input {
        border: 1px solid #e2e8f0; border-radius: 10px; padding: 7px 10px;
        background: #f8fafc; font-weight: 700; color: #0f172a;
    }
    .sa-expense-modal__body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
        padding: 0 22px 12px;
    }
    .sa-expense-modal__cols, .sa-expense-modal__row {
        display: grid; grid-template-columns: 1fr 120px 48px 28px; gap: 10px; align-items: center;
    }
    .sa-expense-modal.is-income-card .sa-expense-modal__cols,
    .sa-expense-modal.is-income-card .sa-expense-modal__row {
        grid-template-columns: 1fr 140px 28px;
    }
    .sa-expense-modal.is-income-card [data-col="image"] { display: none !important; }
    .sa-expense-modal__cols {
        position: sticky; top: 0; z-index: 1;
        font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;
        letter-spacing: 0.04em; margin-bottom: 8px; padding: 6px 2px 8px; background: #fff;
    }
    .sa-expense-modal__rows { display: flex; flex-direction: column; gap: 8px; }
    .sa-expense-modal__row input {
        width: 100%; border: 1px solid #e8edf3; border-radius: 12px; padding: 10px 12px;
        background: #f8fafc; color: #0f172a; font-weight: 600;
    }
    .sa-expense-modal__row input.js-amount { text-align: right; font-variant-numeric: tabular-nums; }
    .sa-expense-modal__total {
        flex-shrink: 0;
        display: flex; justify-content: space-between; align-items: center;
        margin: 0 22px 20px; padding: 12px 16px;
        background: #FE6F07; color: #fff; border-radius: 14px; font-weight: 700;
        box-shadow: 0 10px 24px rgba(254, 111, 7, 0.28);
    }
    .sa-expense-modal__image-btn {
        width: 40px; height: 40px; border: 0; border-radius: 12px; background: #FFF4EC; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center; overflow: hidden; color: #FE6F07;
    }
    .sa-expense-modal__image-thumb img { width: 40px; height: 40px; object-fit: cover; }
    .sa-expense-modal__lock {
        display: inline-flex; align-items: center; justify-content: center;
        width: 22px; color: #fdba74; font-size: 12px;
    }
    .sa-expense-modal__preview-close {
        width: 36px; height: 36px; border: 1px solid rgba(254, 111, 7, 0.28); border-radius: 999px;
        background: #FFF4EC; cursor: pointer; color: #FE6F07;
    }
    .sa-expense-modal__preview {
        flex: 0 0 min(400px, 38vw); background: #fff; border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); padding: 16px; display: flex; flex-direction: column;
    }
    .sa-expense-modal__preview[hidden] { display: none !important; }
    .sa-expense-image-view { flex: 1; display: flex; align-items: center; justify-content: center; min-height: 220px; }
    .sa-expense-image-view img { max-width: 100%; max-height: 420px; border-radius: 10px; }
    .sa-expense-modal input[type="number"].sa-no-spin { appearance: textfield; -moz-appearance: textfield; }
    .sa-expense-modal input[type="number"].sa-no-spin::-webkit-outer-spin-button,
    .sa-expense-modal input[type="number"].sa-no-spin::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>

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
            imageViewImg.src = url;
            const subject = row?.querySelector('.js-subject')?.value?.trim() || '';
            if (imageViewTitle) imageViewTitle.textContent = subject || i18n.imageView;
            imageViewModal.hidden = false;
        }

        function closeImageView() {
            if (imageViewModal) imageViewModal.hidden = true;
            if (imageViewImg) imageViewImg.removeAttribute('src');
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
            row.className = 'sa-expense-modal__row';
            const amountVal = item?.amount === '' || item?.amount == null ? '0' : item.amount;
            const imageUrl = !isIncome && isRealImage(item) ? (item?.image_url || '') : '';
            row.innerHTML = `
                <input type="text" class="js-subject" value="${escapeAttr(item?.subject || '')}" readonly>
                <input type="number" class="js-amount sa-no-spin" value="${amountVal}" readonly>
                ${isIncome
                    ? ''
                    : (isRiderFuel
                        ? '<span></span>'
                        : `<button type="button" class="sa-expense-modal__image-btn${imageUrl ? ' has-image' : ''}">
                        ${imageUrl
                            ? `<span class="sa-expense-modal__image-thumb"><img src="${escapeAttr(imageUrl)}" alt=""></span>`
                            : '<i class="fas fa-camera"></i>'}
                    </button>`)}
                <span class="sa-expense-modal__lock" title="${escapeAttr(i18n.lockedHint)}"><i class="fas fa-lock"></i></span>
            `;
            if (!isIncome) {
                row.querySelector('.sa-expense-modal__image-btn')?.addEventListener('click', function () {
                    const url = this.querySelector('img')?.getAttribute('src') || '';
                    if (url) openImageView(url, row);
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
<script>
    (function bootSaSummaryDates() {
        if (typeof window.pdsBindDmyDatepickers === 'function') {
            window.pdsBindDmyDatepickers('.sa-expense-summary-date');
            return;
        }
        setTimeout(bootSaSummaryDates, 40);
    })();
</script>
@if(! empty($expenseSummary['tripleCanView'] ?? false))
@include('order.partials._expense-summary-triple-check-js')
@endif

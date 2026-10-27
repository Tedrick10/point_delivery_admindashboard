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
                                    'lockKind' => $i->source === \App\Models\ExpenseItem::SOURCE_AGENT_FEE
                                        ? 'agent'
                                        : ($i->source === \App\Models\ExpenseItem::SOURCE_RIDER_FUEL ? 'fuel' : null),
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
            <div class="sa-expense-image-view pds-expense-image-viewport" id="expense-image-view-viewport">
                <img id="expense-image-view-img" class="pds-expense-image-view__img" src="" alt="" draggable="false">
            </div>
            @include('order.partials._expense-photo-zoom-bar', ['viewportId' => 'expense-image-view-viewport'])
        </aside>
    </div>
</div>

{{-- Agent ရငွေ delivered photos --}}
<div class="sa-expense-modal" id="expense-agent-photos-modal" hidden>
    <div class="sa-expense-modal__backdrop" data-close="agent-photos"></div>
    <div class="sa-expense-modal__stage sa-expense-agent-photos-stage">
        <div class="sa-expense-modal__dialog sa-expense-agent-photos-dialog" role="dialog" aria-modal="true">
            <header class="sa-expense-modal__header">
                <h5 id="expense-agent-photos-title">{{ __('message.expenses_agent_fee_photos') }}</h5>
                <button type="button" class="sa-expense-modal__preview-close" id="expense-agent-photos-close" title="{{ __('message.close') }}" data-close="agent-photos">
                    <i class="fas fa-times"></i>
                </button>
            </header>
            <div class="sa-expense-agent-photos__body" id="expense-agent-photos-body"></div>
        </div>
        <aside class="sa-expense-modal__preview" id="expense-agent-photo-preview" hidden>
            <header class="sa-expense-modal__header">
                <h5 id="expense-agent-photo-preview-title">{{ __('message.expenses_image_view') }}</h5>
                <button type="button" class="sa-expense-modal__preview-close" id="expense-agent-photo-preview-close" title="{{ __('message.close') }}">
                    <i class="fas fa-times"></i>
                </button>
            </header>
            <div class="sa-expense-image-view pds-expense-image-viewport" id="expense-agent-photo-viewport">
                <img id="expense-agent-photo-preview-img" class="pds-expense-image-view__img" src="" alt="" draggable="false">
            </div>
            @include('order.partials._expense-photo-zoom-bar', ['viewportId' => 'expense-agent-photo-viewport'])
        </aside>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/pds-expense-photo-zoom.css') }}?v=2">
<script src="{{ asset('js/pds-photo-zoom.js') }}?v=4"></script>

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
    .sa-expense-modal__preview .pds-expense-image-viewport {
        position: relative; display: block; flex: 1 1 auto; min-height: 280px;
        height: min(58vh, 520px); max-height: min(58vh, 520px); overflow: hidden;
        background: #0f172a; border-radius: 12px; touch-action: none; cursor: grab;
    }
    .sa-expense-modal__preview .pds-expense-image-viewport img {
        position: absolute; top: 50%; left: 50%; max-width: 100%; max-height: 100%;
        width: auto; height: auto; object-fit: contain; transform: translate(-50%, -50%);
        pointer-events: none; border-radius: 8px;
    }
    .sa-expense-modal__preview .pds-photo-carousel-zoom-bar {
        display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.45rem 0.15rem 0;
    }
    .sa-expense-modal__preview .pds-photo-carousel-zoom-btn {
        width: 34px; height: 34px; border: 1px solid #e2e8f0; border-radius: 999px; background: #fff;
        color: #FE6F07; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
    }
    .sa-expense-modal__preview .pds-photo-carousel-zoom-btn--reset { width: auto; padding: 0 0.75rem; font-size: 0.72rem; font-weight: 700; }
    .sa-expense-modal__preview .pds-photo-carousel-zoom-level { min-width: 52px; text-align: center; font-size: 0.78rem; font-weight: 700; }
    .sa-expense-modal__preview .pds-photo-carousel-zoom-hint { margin: 0.15rem 0 0.35rem; text-align: center; color: #64748b; font-size: 0.68rem; }
    .sa-expense-modal input[type="number"].sa-no-spin { appearance: textfield; -moz-appearance: textfield; }
    .sa-expense-modal input[type="number"].sa-no-spin::-webkit-outer-spin-button,
    .sa-expense-modal input[type="number"].sa-no-spin::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .sa-expense-agent-photos-dialog { flex: 1 1 560px; max-width: 720px; }
    .sa-expense-agent-photos__body {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px; padding: 16px 20px 24px; overflow: auto; max-height: min(70vh, 560px);
    }
    .sa-expense-agent-photos__empty { margin: 24px; color: #64748b; text-align: center; }
    .sa-expense-agent-photos__card {
        border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #fff;
    }
    .sa-expense-agent-photos__card.is-active { border-color: #FE6F07; box-shadow: 0 0 0 2px rgba(254,111,7,0.2); }
    .sa-expense-agent-photos__thumb {
        display: block; width: 100%; aspect-ratio: 1; border: 0; padding: 0; background: #f8fafc; cursor: pointer;
    }
    .sa-expense-agent-photos__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sa-expense-agent-photos__meta { padding: 8px 10px; display: flex; flex-direction: column; gap: 2px; font-size: 12px; }
    .sa-expense-agent-photos__meta span { color: #64748b; }
    .sa-expense-agent-photos__meta strong { color: #0f172a; }
    .sa-expense-agent-photos__meta .pds-expense-agent-photos__original-deli,
    .pds-expense-agent-photos__original-deli { color: #FE6F07 !important; font-size: 0.82rem; font-weight: 800; }
    #expense-agent-photos-modal.has-preview .sa-expense-agent-photos-dialog { flex: 0 1 420px; }
</style>

<script>
    (function () {
        const demoImagePath = @json(\App\Models\ExpenseItem::DEMO_IMAGE);
        const selectedBranchId = @json($saBranchId ?? null);
        const routes = {
            agentFeePhotos: @json(route('order.expenses.agent-fee-photos')),
        };
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
            agentFeeSubject: @json(__('message.expenses_agent_fee')),
            agentFeeViewPhotos: @json(__('message.expenses_agent_fee_view_photos')),
            agentFeePhotos: @json(__('message.expenses_agent_fee_photos')),
            agentFeePhotosEmpty: @json(__('message.expenses_agent_fee_photos_empty')),
            originalDeli: @json(__('message.expenses_agent_fee_original_deli') ?: __('message.dispatch_audit_field_original_deli')),
            deliveryMan: @json(__('message.delivery_man')),
            noticeError: @json(__('message.error')),
        };
        const formModal = document.getElementById('expense-form-modal');
        const imageViewModal = document.getElementById('expense-image-view-modal');
        const imageViewImg = document.getElementById('expense-image-view-img');
        const imageViewTitle = document.getElementById('expense-image-view-title');
        const rowsEl = document.getElementById('expense-item-rows');
        const colsEl = document.getElementById('expense-form-cols');
        const dateInput = document.getElementById('expense-date-input');
        const titleEl = document.getElementById('expense-form-title');
        const agentPhotosModal = document.getElementById('expense-agent-photos-modal');
        const agentPhotosBody = document.getElementById('expense-agent-photos-body');
        const agentPhotosTitle = document.getElementById('expense-agent-photos-title');
        const agentPhotoPreview = document.getElementById('expense-agent-photo-preview');
        const agentPhotoPreviewImg = document.getElementById('expense-agent-photo-preview-img');
        const agentPhotoPreviewTitle = document.getElementById('expense-agent-photo-preview-title');
        const imageViewViewport = document.getElementById('expense-image-view-viewport');
        const agentPhotoViewport = document.getElementById('expense-agent-photo-viewport');
        let activeCardType = 'expense';

        function mountExpenseZoom(viewport) {
            if (!viewport || !window.PdsPhotoZoom || typeof window.PdsPhotoZoom.mountExpensePhoto !== 'function') {
                return;
            }
            window.PdsPhotoZoom.mountExpensePhoto(viewport, { reset: true });
        }

        function resetExpenseZoom(viewport) {
            if (!viewport || !window.PdsPhotoZoom || typeof window.PdsPhotoZoom.reset !== 'function') {
                return;
            }
            window.PdsPhotoZoom.reset(viewport);
        }

        function escapeAttr(s) {
            return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        function fmt(n) {
            return Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
        }

        function isRealImage(item) {
            const path = String(item?.image || '').trim();
            const url = String(item?.image_url || '').trim();
            return (path !== '' && path !== demoImagePath) || url !== '';
        }

        function resolveLockKind(item) {
            if (item?.lockKind === 'agent' || item?.lockKind === 'fuel') return item.lockKind;
            if (item?.subject === i18n.agentFeeSubject || item?.subject === 'Agent ရငွေ' || item?.subject === 'Agent fee') {
                return 'agent';
            }
            if (item?.locked) return 'fuel';
            return '';
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
            mountExpenseZoom(imageViewViewport);
        }

        function closeImageView() {
            if (imageViewModal) imageViewModal.hidden = true;
            if (imageViewImg) imageViewImg.removeAttribute('src');
            resetExpenseZoom(imageViewViewport);
        }

        function closeAgentPhotoSidePreview() {
            agentPhotosBody?.querySelectorAll('.sa-expense-agent-photos__card.is-active').forEach((el) => {
                el.classList.remove('is-active');
            });
            if (agentPhotoPreview) agentPhotoPreview.hidden = true;
            if (agentPhotoPreviewImg) agentPhotoPreviewImg.removeAttribute('src');
            agentPhotosModal?.classList.remove('has-preview');
            resetExpenseZoom(agentPhotoViewport);
        }

        function openAgentPhotoSidePreview(url, meta) {
            if (!url || !agentPhotoPreview || !agentPhotoPreviewImg) return;
            agentPhotoPreviewImg.src = url;
            if (agentPhotoPreviewTitle) {
                agentPhotoPreviewTitle.textContent = meta || i18n.imageView;
            }
            agentPhotoPreview.hidden = false;
            agentPhotosModal?.classList.add('has-preview');
            mountExpenseZoom(agentPhotoViewport);
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
                agentPhotosBody.innerHTML = `<p class="sa-expense-agent-photos__empty">${escapeAttr(i18n.agentFeePhotosEmpty)}</p>`;
                return;
            }
            agentPhotosBody.innerHTML = photos.map((p, idx) => `
                <article class="sa-expense-agent-photos__card" data-photo-index="${idx}">
                    <button type="button"
                            class="sa-expense-agent-photos__thumb js-agent-photo-enlarge"
                            data-url="${escapeAttr(p.photo_url || '')}"
                            data-label="${escapeAttr(p.order_id ? ('Order #' + p.order_id) : i18n.imageView)}">
                        <img src="${escapeAttr(p.photo_url || '')}" alt="">
                    </button>
                    <div class="sa-expense-agent-photos__meta">
                        ${p.order_id ? `<span>Order #${escapeAttr(p.order_id)}</span>` : ''}
                        <strong>${escapeAttr(p.rider_name || i18n.deliveryMan)}: ${fmt(p.agent_amount)}</strong>
                        <span class="pds-expense-agent-photos__original-deli">${escapeAttr(i18n.originalDeli)}: ${fmt(p.deli_amount)}</span>
                    </div>
                </article>
            `).join('');
            agentPhotosBody.querySelectorAll('.js-agent-photo-enlarge').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const url = btn.getAttribute('data-url');
                    if (!url) return;
                    const card = btn.closest('.sa-expense-agent-photos__card');
                    agentPhotosBody.querySelectorAll('.sa-expense-agent-photos__card.is-active').forEach((el) => {
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
                agentPhotosBody.innerHTML = '<p class="sa-expense-agent-photos__empty">...</p>';
            }
            closeAgentPhotoSidePreview();
            agentPhotosModal.hidden = false;
            try {
                const url = routes.agentFeePhotos
                    + '?date=' + encodeURIComponent(day)
                    + (selectedBranchId ? '&branch_id=' + encodeURIComponent(selectedBranchId) : '');
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || i18n.noticeError);
                }
                renderAgentFeePhotos(Array.isArray(data.photos) ? data.photos : []);
            } catch (err) {
                if (agentPhotosBody) {
                    agentPhotosBody.innerHTML = `<p class="sa-expense-agent-photos__empty">${escapeAttr(err.message || i18n.noticeError)}</p>`;
                }
            }
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
            const isLockedAuto = !!item?.locked;
            const lockKind = resolveLockKind(item);
            const row = document.createElement('div');
            row.className = 'sa-expense-modal__row';
            if (lockKind) row.dataset.lockKind = lockKind;
            const amountVal = item?.amount === '' || item?.amount == null ? '0' : item.amount;
            const imageUrl = !isIncome && isRealImage(item) ? (item?.image_url || '') : '';
            const showImage = !isIncome && !isLockedAuto;
            const showAgentPhotosBtn = !isIncome && isLockedAuto && lockKind === 'agent';
            let imageCell = '';
            if (isIncome) {
                imageCell = '';
            } else if (showImage) {
                imageCell = `<button type="button" class="sa-expense-modal__image-btn${imageUrl ? ' has-image' : ''}" title="${escapeAttr(imageUrl ? i18n.imageView : '')}">
                    ${imageUrl
                        ? `<span class="sa-expense-modal__image-thumb"><img src="${escapeAttr(imageUrl)}" alt=""></span>`
                        : '<i class="fas fa-camera"></i>'}
                </button>`;
            } else if (showAgentPhotosBtn) {
                imageCell = `<button type="button" class="sa-expense-modal__image-btn has-image js-agent-fee-photos" title="${escapeAttr(i18n.agentFeeViewPhotos)}">
                    <i class="fas fa-image"></i>
                </button>`;
            } else {
                imageCell = '<span></span>';
            }
            row.innerHTML = `
                <input type="text" class="js-subject" value="${escapeAttr(item?.subject || '')}" readonly>
                <input type="number" class="js-amount sa-no-spin" value="${amountVal}" readonly>
                ${imageCell}
                <span class="sa-expense-modal__lock" title="${escapeAttr(i18n.lockedHint)}"><i class="fas fa-lock"></i></span>
            `;
            if (showImage) {
                row.querySelector('.sa-expense-modal__image-btn')?.addEventListener('click', function () {
                    const url = this.querySelector('img')?.getAttribute('src') || '';
                    if (url) openImageView(url, row);
                });
            }
            if (showAgentPhotosBtn) {
                row.querySelector('.js-agent-fee-photos')?.addEventListener('click', () => {
                    openAgentFeePhotos(dateInput?.value || '');
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
            closeAgentFeePhotos();
            formModal?.classList.remove('is-income-card');
            formModal.hidden = true;
        }

        document.getElementById('expense-form-cancel')?.addEventListener('click', closeForm);
        formModal?.querySelector('[data-close="form"]')?.addEventListener('click', closeForm);
        document.getElementById('expense-image-view-close')?.addEventListener('click', closeImageView);
        imageViewModal?.querySelector('[data-close="image-view"]')?.addEventListener('click', closeImageView);

        document.getElementById('expense-agent-photos-close')?.addEventListener('click', closeAgentFeePhotos);
        agentPhotosModal?.querySelector('[data-close="agent-photos"]')?.addEventListener('click', closeAgentFeePhotos);
        document.getElementById('expense-agent-photo-preview-close')?.addEventListener('click', closeAgentPhotoSidePreview);

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

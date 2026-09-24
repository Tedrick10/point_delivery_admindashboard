@php
    $calendarDays = $calendarDays ?? [];
    $calendarWeekdays = $calendarWeekdays ?? collect();
    $tripleCheckers = $tripleCheckers ?? [];
    $tripleCanConfirm = (bool) ($tripleCanConfirm ?? false);
    $tripleCheckerKey = $tripleCheckerKey ?? null;
    $tripleAlreadyConfirmed = (bool) ($tripleAlreadyConfirmed ?? false);
    $tripleConfirmUrl = $tripleConfirmUrl ?? route('order.expense-summary.confirm');
    $selectedFromYmd = $selectedFromYmd ?? null;
    $selectedToYmd = $selectedToYmd ?? null;
    $dayUrlFn = $calendarDayUrl ?? fn ($ymd) => '#';
    $monthUrlFn = $calendarMonthUrl ?? fn ($ym) => '#';
    $calendarToday = $calendarToday ?? now('Asia/Yangon')->toDateString();
    $confirmFrom = $selectedFromYmd && $selectedFromYmd > $calendarToday ? $calendarToday : $selectedFromYmd;
    $confirmTo = $selectedToYmd && $selectedToYmd > $calendarToday ? $calendarToday : $selectedToYmd;
@endphp

<aside class="pds-summary-cal" data-confirm-url="{{ $tripleConfirmUrl }}" data-from="{{ $confirmFrom }}" data-to="{{ $confirmTo }}" data-today="{{ $calendarToday }}" data-checker="{{ $tripleCheckerKey }}">
    <header class="pds-summary-cal__head">
        <div>
            <p class="pds-summary-cal__eyebrow">{{ __('message.expense_summary_triple_check') }}</p>
            <h3 class="pds-summary-cal__title">{{ $calendarMonthLabel ?? '' }}</h3>
        </div>
        <div class="pds-summary-cal__nav">
            <a href="{{ $monthUrlFn($calendarPrevMonth ?? '') }}" class="pds-summary-cal__nav-btn" title="Previous">
                <i class="fas fa-chevron-left"></i>
            </a>
            @if(! empty($calendarNextDisabled))
                <span class="pds-summary-cal__nav-btn is-disabled" aria-disabled="true" title="Next">
                    <i class="fas fa-chevron-right"></i>
                </span>
            @else
                <a href="{{ $monthUrlFn($calendarNextMonth ?? '') }}" class="pds-summary-cal__nav-btn" title="Next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            @endif
        </div>
    </header>

    <div class="pds-summary-cal__weekdays" aria-hidden="true">
        @foreach($calendarWeekdays as $wd)
            <span>{{ $wd }}</span>
        @endforeach
    </div>

    <div class="pds-summary-cal__grid">
        @foreach($calendarDays as $day)
            @php
                $classes = ['pds-summary-cal__day'];
                if (! ($day['in_month'] ?? true)) {
                    $classes[] = 'is-out';
                }
                if ($day['selected'] ?? false) {
                    $classes[] = 'is-selected';
                }
                if ($day['today'] ?? false) {
                    $classes[] = 'is-today';
                }
                if ($day['all_checked'] ?? false) {
                    $classes[] = 'is-checked';
                }
                if ($day['disabled'] ?? false) {
                    $classes[] = 'is-disabled';
                }
                $dayTag = ($day['disabled'] ?? false) ? 'span' : 'a';
            @endphp
            <{{ $dayTag }}
               @if($dayTag === 'a') href="{{ $dayUrlFn($day['ymd']) }}" @endif
               class="{{ implode(' ', $classes) }}"
               data-ymd="{{ $day['ymd'] }}"
               @if($day['disabled'] ?? false) aria-disabled="true" @endif
               title="{{ $day['ymd'] }}{{ ($day['all_checked'] ?? false) ? ' — '.__('message.expense_summary_all_checked') : '' }}">
                <span class="pds-summary-cal__num">{{ $day['day'] }}</span>
                <span class="pds-summary-cal__dots">
                    @foreach($tripleCheckers as $checker)
                        <i class="pds-summary-cal__dot is-{{ $checker['key'] }} {{ !empty($day['dots'][$checker['key']]) ? 'is-on' : '' }}"
                           title="{{ $checker['label'] }}"></i>
                    @endforeach
                </span>
            </{{ $dayTag }}>
        @endforeach
    </div>

    <ul class="pds-summary-cal__legend">
        @foreach($tripleCheckers as $checker)
            <li>
                <i class="pds-summary-cal__dot is-{{ $checker['key'] }} is-on"></i>
                <span>{{ $checker['label'] }}</span>
            </li>
        @endforeach
        <li>
            <span class="pds-summary-cal__done-mark"></span>
            <span>{{ __('message.expense_summary_all_checked') }}</span>
        </li>
    </ul>

    @if($tripleCanConfirm)
        <button type="button"
                class="pds-summary-cal__confirm js-summary-triple-confirm"
                @disabled($tripleAlreadyConfirmed)>
            <i class="fas {{ $tripleAlreadyConfirmed ? 'fa-check-circle' : 'fa-check' }}"></i>
            <span>{{ $tripleAlreadyConfirmed ? __('message.expense_summary_already_confirmed') : __('message.expense_summary_confirm') }}</span>
        </button>
    @endif
</aside>

<div class="pds-summary-pop" id="summaryTriplePop" hidden>
    <div class="pds-summary-pop__backdrop js-summary-pop-cancel"></div>
    <div class="pds-summary-pop__card" role="dialog" aria-modal="true" aria-labelledby="summaryTriplePopTitle">
        <span class="pds-summary-pop__icon" id="summaryTriplePopIcon" aria-hidden="true">
            <i class="fas fa-check"></i>
        </span>
        <h5 class="pds-summary-pop__title" id="summaryTriplePopTitle">{{ __('message.expense_summary_confirm_title') }}</h5>
        <p class="pds-summary-pop__text" id="summaryTriplePopText"></p>
        <div class="pds-summary-pop__actions">
            <button type="button" class="pds-summary-pop__btn is-ghost js-summary-pop-cancel" id="summaryTriplePopCancel">
                {{ __('message.cancel') }}
            </button>
            <button type="button" class="pds-summary-pop__btn is-primary" id="summaryTriplePopOk">
                {{ __('message.expense_summary_confirm') }}
            </button>
        </div>
    </div>
</div>

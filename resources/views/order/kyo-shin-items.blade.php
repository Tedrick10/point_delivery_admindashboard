<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-kyo-shin-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero pds-rider-hero--details">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-box-open" aria-hidden="true"></i>
                        <span>{{ __('message.kyo_shin_title') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <div class="pds-rider-meta-row">
                        <span class="pds-rider-meta-pill">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            {{ $osName }}
                        </span>
                        <span class="pds-rider-meta-pill pds-rider-meta-pill--count">
                            {{ __('message.item_count') }}
                            <strong>{{ $items->count() }}</strong>
                        </span>
                    </div>
                </div>
                <a
                    href="{{ route('order.kyo-shin', [
                        'scope' => $scopeKey,
                        'tab' => $tab,
                        'from_date' => $fromRaw,
                        'to_date' => $toRaw,
                    ]) }}"
                    class="pds-rider-close-btn"
                    title="{{ __('message.close') }}"
                >
                    <i class="fas fa-times"></i>
                </a>
            </div>

            @if($canMark || $canFinish)
                <div class="pds-rider-toolbar">
                    <div class="pds-rider-toolbar__fields">
                        <div class="pds-dispatch-rider-items-total-wrap">
                            <span class="pds-dispatch-rider-items-total-label">{{ __('message.total') }}</span>
                            <div class="pds-dispatch-rider-items-total">
                                {{ number_format($items->sum(fn ($item) => (float) ($item->kyo_shin_amount ?? 0))) }}
                            </div>
                        </div>
                    </div>
                    <div class="pds-rider-toolbar__aside">
                        @if($canMark)
                            <form method="POST" action="{{ route('order.kyo-shin.mark-paid', $osId) }}">
                                @csrf
                                <input type="hidden" name="scope" value="{{ $scopeKey }}">
                                <input type="hidden" name="from_date" value="{{ $fromRaw }}">
                                <input type="hidden" name="to_date" value="{{ $toRaw }}">
                                <button type="submit" class="pds-rider-check-btn">
                                    <i class="fas fa-coins" aria-hidden="true"></i>
                                    <span>{{ __('message.kyo_shin_mark_paid') }}</span>
                                </button>
                            </form>
                        @endif
                        @if($canFinish)
                            <form method="POST" action="{{ route('order.kyo-shin.finish', $osId) }}">
                                @csrf
                                <input type="hidden" name="scope" value="{{ $scopeKey }}">
                                <input type="hidden" name="from_date" value="{{ $fromRaw }}">
                                <input type="hidden" name="to_date" value="{{ $toRaw }}">
                                <button type="submit" class="pds-rider-check-btn">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                    <span>{{ __('message.kyo_shin_mark_finished') }}</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <div class="pds-rider-table-shell pds-rider-table-shell--scroll pds-no-freeze">
                @if($items->isEmpty())
                    <div class="pds-os-settlement-section__empty">
                        <p>{{ __('message.kyo_shin_empty') }}</p>
                    </div>
                @else
                    <table class="table pds-rider-list-table">
                        <thead>
                            <tr>
                                <th class="pds-rider-col-no">{{ __('message.no') }}</th>
                                <th>{{ __('message.code') }}</th>
                                    <th>{{ __('message.customer_name') }}</th>
                                <th>{{ __('message.phone') }}</th>
                                <th class="text-right">{{ __('message.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $index => $item)
                                <tr>
                                    <td class="pds-rider-col-no">{{ $index + 1 }}</td>
                                    <td>{{ $item->code ?: '-' }}</td>
                                    <td>{{ $item->customer_name ?: '-' }}</td>
                                    <td>{{ $item->customer_phone ?: '-' }}</td>
                                    <td class="text-right">{{ number_format((float) ($item->kyo_shin_amount ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-master-layout>

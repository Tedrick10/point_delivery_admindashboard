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
                    href="{{ route('order.dispatch.os-list', [
                        'from_date' => $filterFromDate,
                        'to_date' => $filterToDate,
                        'tab' => 'kyo_shin',
                    ]) }}"
                    class="pds-rider-close-btn"
                    title="{{ __('message.close') }}"
                >
                    <i class="fas fa-times"></i>
                </a>
            </div>

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
                                <th>{{ __('message.kyo_shin_paid_date') }}</th>
                                <th>{{ __('message.status') }}</th>
                                <th>{{ __('message.kyo_shin_proof_image') }}</th>
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
                                    <td>
                                        {{ $item->kyoShinItem?->advanced_paid_at
                                            ? $item->kyoShinItem->advanced_paid_at->timezone('Asia/Yangon')->format('d-m-Y')
                                            : '-' }}
                                    </td>
                                    <td>
                                        @if((string) ($item->status ?? '') === 'return')
                                            <span class="pds-kyo-shin-row-badge">{{ __('message.follow_up_status_return') }}</span>
                                        @elseif((string) ($item->status ?? '') === 'completed')
                                            <span class="pds-kyo-shin-row-badge">{{ __('message.follow_up_status_completed') }}</span>
                                        @else
                                            {{ $item->status ?: '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $kyoShinImages = $item->kyoShinItem?->batch?->slipPhotoUrls() ?? [];
                                        @endphp
                                        @if($kyoShinImages !== [])
                                            <div class="pds-kyo-shin-proofs">
                                                @foreach($kyoShinImages as $imageUrl)
                                                    <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="pds-kyo-shin-proof">
                                                        <img src="{{ $imageUrl }}" alt="{{ __('message.kyo_shin_proof_image') }}">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format((float) ($item->kyo_shin_amount ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="pds-daily-check-total-row">
                                <td colspan="7" class="pds-daily-check-total-label">{{ __('message.total') }}</td>
                                <td class="text-right">{{ number_format((float) $items->sum(fn ($item) => (float) ($item->kyo_shin_amount ?? 0))) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <style>
        .pds-kyo-shin-row-badge {
            display: inline-flex; align-items: center; padding: 2px 7px;
            border-radius: 999px; font-size: 10px; font-weight: 800;
            background: #ffedd5; color: #c2410c;
        }
        .pds-kyo-shin-proofs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            max-width: 220px;
        }
        .pds-kyo-shin-proof {
            display: block;
            width: 44px;
            height: 56px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #fdba74;
            background: #fff7ed;
        }
        .pds-kyo-shin-proof img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</x-master-layout>

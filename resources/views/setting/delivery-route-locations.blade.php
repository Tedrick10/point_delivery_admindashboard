<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-route-locations-page">
        <div class="pds-dispatch-to-assign-screen pds-rider-screen">
            <div class="pds-rider-hero">
                <div class="pds-rider-hero__copy">
                    <div class="pds-rider-hero__eyebrow">
                        <i class="fas fa-route" aria-hidden="true"></i>
                        <span>{{ __('message.delivery_route') }}</span>
                    </div>
                    <h4 class="pds-rider-hero__title">{{ $pageTitle }}</h4>
                    <p class="pds-rider-hero__subtitle">{{ __('message.delivery_route_locations_subtitle') }}</p>
                </div>
            </div>

            <div class="pds-os-settlement-tabs" role="tablist">
                <a href="{{ route('delivery-route-locations.index', ['tab' => 'from_to']) }}"
                   class="pds-os-settlement-tab {{ $tab === 'from_to' ? 'is-active' : '' }}">
                    <span>{{ __('message.from') }} / {{ __('message.to') }}</span>
                    <em>{{ $branches->count() }}</em>
                </a>
                <a href="{{ route('delivery-route-locations.index', ['tab' => 'city']) }}"
                   class="pds-os-settlement-tab {{ $tab === 'city' ? 'is-active' : '' }}">
                    <span>{{ __('message.city') }}</span>
                    <em>{{ $cities->count() }}</em>
                </a>
                <a href="{{ route('delivery-route-locations.index', ['tab' => 'township', 'city_id' => $filterCityId ?: null]) }}"
                   class="pds-os-settlement-tab {{ $tab === 'township' ? 'is-active' : '' }}">
                    <span>{{ __('message.township') }}</span>
                    <em>{{ $townships->count() }}</em>
                </a>
            </div>

            @if($tab === 'from_to')
                @include('setting.partials._delivery_route_from_to')
            @elseif($tab === 'city')
                @include('setting.partials._delivery_route_cities')
            @else
                @include('setting.partials._delivery_route_townships')
            @endif
        </div>
    </div>
    <style>
        .pds-route-locations-page .pds-route-form {
            display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
            padding: 14px 16px; margin-bottom: 16px;
        }
        .pds-route-form__field { min-width: 180px; flex: 1; }
        .pds-route-form__field label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; }
        .pds-route-form__field input, .pds-route-form__field select {
            width: 100%; border: 1px solid #e2e8f0; border-radius: 10px; padding: 9px 12px;
        }
        .pds-route-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; }
        .pds-route-table { width: 100%; border-collapse: collapse; }
        .pds-route-table th, .pds-route-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; text-align: left; }
        .pds-route-table th { font-size: 12px; letter-spacing: .04em; color: #64748b; background: #f8fafc; }
        .pds-route-table tr:last-child td { border-bottom: 0; }
        .pds-route-actions { display: flex; gap: 8px; justify-content: flex-end; }
        .pds-route-empty { padding: 36px 16px; text-align: center; color: #94a3b8; }
    </style>
</x-master-layout>

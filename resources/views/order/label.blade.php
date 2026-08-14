@php
    $companyTitle = optional($companyName)->value ?? config('app.name');
    $companyPhone = optional($companynumber)->value ?? '';
    $companyAddr = optional($companyAddress)->value ?? '';
    $logoUrl = ($invoice ? getSingleMedia($invoice, 'company_logo') : null)
        ?: getSingleMedia(appSettingData('get'), 'site_logo', null);
    $pickup = $order->pickup_point ?? [];
    $delivery = $order->delivery_point ?? [];
    $packagingSymbols = json_decode($order->packaging_symbols ?? '[]', true) ?: [];
    $symbolIcons = [
        'fragile' => asset('images/fragile.png'),
        'keep_dry' => asset('images/keep-dry.png'),
        'this_way_up' => asset('images/up-arrows-couple-sign-for-packaging.png'),
        'do_not_stack' => asset('images/do-not-stack.png'),
        'temperature_sensitive' => asset('images/temperature.png'),
        'recycle' => asset('images/symbols.png'),
        'do_not_use_hooks' => asset('images/do-not-hook.png'),
        'explosive_material' => asset('images/flammable.png'),
        'hazardous_material' => asset('images/hazard.png'),
        'perishable' => asset('images/ice-cube.png'),
        'do_not_open_with_sharp_objects' => asset('images/knives.png'),
        'bike_delivery' => asset('images/fast-delivery.png'),
    ];
@endphp

<x-master-layout :assets="$assets ?? []">
    <div class="pds-print-page">
        <div class="pds-print-toolbar">
            <div>
                <p class="pds-print-eyebrow mb-1">{{ __('message.order') }} #{{ $id }}</p>
                <h4 class="pds-print-title mb-0">{{ __('message.print_label') }}</h4>
            </div>
            <button type="button" onclick="printLabel({{ $id }})" class="btn btn-primary pds-print-action-btn">
                <i class="fas fa-print mr-1"></i> {{ __('message.print_label') }}
            </button>
        </div>

        <div class="pds-print-preview-area">
            <div class="pds-slip" id="pds-print-slip">
                <table class="pds-slip-table">
                    <tbody>
                        <tr>
                            <td colspan="2" class="pds-slip-table__header">
                                <div class="pds-slip-header">
                                    <div class="pds-slip-logo{{ $logoUrl ? ' pds-slip-logo--filled' : '' }}">
                                        @if($logoUrl)
                                            <img src="{{ $logoUrl }}" alt="{{ $companyTitle }}">
                                        @else
                                            <span class="pds-slip-logo__ph"><i class="fas fa-image"></i><em>Logo</em></span>
                                        @endif
                                    </div>
                                    <div class="pds-slip-header__info">
                                        <strong>{{ $companyTitle }}</strong>
                                        @if($companyAddr)<span>{{ $companyAddr }}</span>@endif
                                        @if($labelnumber == 1 && $companyPhone)
                                            <span class="pds-slip-header__phone"><i class="fas fa-phone-alt"></i> {{ $companyPhone }}</span>
                                        @endif
                                        @if($order->is_shipped == 1)
                                            <span class="pds-slip-header__ship">
                                                <i class="fas fa-shipping-fast"></i>
                                                {{ __('message.shipped_via') }}: {{ optional($order->couriercompany)->name }}
                                                · {{ optional($order->shipped_verify_at) ? \Carbon\Carbon::parse($order->shipped_verify_at)->format('Y-m-d') : 'N/A' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="pds-slip-table__addresses">
                                <div class="pds-slip-addr pds-slip-addr--from">
                                    <div class="pds-slip-addr__head">
                                        <span class="pds-slip-badge pds-slip-badge--from">{{ __('message.from') }}</span>
                                        <strong>{{ $pickup['name'] ?? '-' }}</strong>
                                    </div>
                                    @if(!empty($pickup['address']))
                                        <p class="pds-slip-addr__text">{{ $pickup['address'] }}</p>
                                    @endif
                                    @if(!empty($pickup['contact_number']))
                                        <span class="pds-slip-phone"><i class="fas fa-phone-alt"></i> {{ $pickup['contact_number'] }}</span>
                                    @endif
                                </div>

                                <div class="pds-slip-addr pds-slip-addr--to">
                                    <div class="pds-slip-addr__head">
                                        <span class="pds-slip-badge pds-slip-badge--to">{{ __('message.to') }}</span>
                                        <strong>{{ $delivery['name'] ?? '-' }}</strong>
                                    </div>
                                    @if(!empty($delivery['address']))
                                        <p class="pds-slip-addr__text">{{ $delivery['address'] }}</p>
                                    @endif
                                    @if(!empty($delivery['contact_number']))
                                        <span class="pds-slip-phone"><i class="fas fa-phone-alt"></i> {{ $delivery['contact_number'] }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="pds-slip-table__barcode">
                                <div class="pds-slip-barcode-box">
                                    <span class="pds-slip-barcode__label">{{ __('message.tracking_number') }}</span>
                                    <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Barcode" class="pds-slip-barcode__img">
                                    <div class="pds-slip-barcode__no">{{ $order->milisecond }}</div>
                                </div>
                            </td>
                        </tr>

                        @if(!empty($delivery['instruction']))
                        <tr>
                            <td colspan="2" class="pds-slip-table__extra">
                                <span class="pds-slip-badge">{{ __('message.shipping_Ins') }}</span>
                                <p>{{ $delivery['instruction'] }}</p>
                            </td>
                        </tr>
                        @endif

                        @if(is_array($packagingSymbols) && count($packagingSymbols))
                        <tr>
                            <td colspan="2" class="pds-slip-table__extra">
                                <span class="pds-slip-badge">{{ __('message.packaging_symbol') }}</span>
                                <div class="pds-slip-symbols">
                                    @foreach($packagingSymbols as $symbol)
                                        @php $icon = $symbolIcons[$symbol['key'] ?? ''] ?? ''; @endphp
                                        @if($icon)<img src="{{ $icon }}" alt="">@endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-master-layout>

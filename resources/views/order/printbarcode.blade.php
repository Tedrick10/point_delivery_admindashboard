@php
    $companyTitle = optional($companyName)->value ?? config('app.name');
    $companyPhone = optional($companynumber)->value ?? '';
    $logoUrl = ($invoice ? getSingleMedia($invoice, 'company_logo') : null)
        ?: getSingleMedia(appSettingData('get'), 'site_logo', null);
@endphp

<x-master-layout :assets="$assets ?? []">
    <div class="pds-print-page">
        <div class="pds-print-toolbar">
            <div>
                <p class="pds-print-eyebrow mb-1">{{ __('message.order') }} #{{ $id }}</p>
                <h4 class="pds-print-title mb-0">{{ __('message.print_barcode') }}</h4>
            </div>
            <button type="button" onclick="printbarcode({{ $id }})" class="btn btn-primary pds-print-action-btn">
                <i class="fas fa-print mr-1"></i> {{ __('message.print_barcode') }}
            </button>
        </div>

        <div class="pds-print-preview-area">
            <div class="pds-slip pds-slip--barcode" id="pds-print-slip">
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
                                        @if($labelnumber == 1 && $companyPhone)
                                            <span class="pds-slip-header__phone"><i class="fas fa-phone-alt"></i> {{ $companyPhone }}</span>
                                        @endif
                                    </div>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-master-layout>

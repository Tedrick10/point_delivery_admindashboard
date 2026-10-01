@php
    $fields = $fields ?? [];
    $saveRoute = $saveRoute ?? route('super-admin.system-settings.company-contact');
    $meta = [
        'company_name' => ['icon' => 'fa-building', 'hint' => null],
        'company_contact_number' => ['icon' => 'fa-phone', 'hint' => __('message.company_contact_number_hint')],
        'company_hotline' => ['icon' => 'fa-qrcode', 'hint' => __('message.company_hotline_hint')],
        'company_address' => ['icon' => 'fa-map-marker-alt', 'hint' => __('message.company_address_hint')],
        'company_email' => ['icon' => 'fa-envelope', 'hint' => __('message.company_email_hint')],
        'express_phone' => ['icon' => 'fa-bolt', 'hint' => __('message.express_phone_hint')],
    ];
@endphp

<form method="POST" action="{{ $saveRoute }}" class="sa-settings-form sa-company-contact-form">
    @csrf
    <section class="sa-gs-block">
        <div class="sa-settings-grid">
            @foreach($fields as $key => $value)
                @php $info = $meta[$key] ?? ['icon' => 'fa-circle', 'hint' => null]; @endphp
                <div class="sa-settings-field {{ in_array($key, ['company_address', 'company_name'], true) ? 'sa-settings-field--full' : '' }}">
                    <label class="sa-settings-label" for="{{ $key }}">
                        <i class="fas {{ $info['icon'] }}" aria-hidden="true"></i>
                        <span>{{ __('message.'.$key) }}</span>
                    </label>
                    <input
                        type="text"
                        name="{{ $key }}"
                        id="{{ $key }}"
                        value="{{ old($key, $value) }}"
                        class="sa-settings-input"
                        placeholder="{{ __('message.'.$key) }}"
                    >
                    @if(!empty($info['hint']))
                        <p class="sa-settings-hint">{{ $info['hint'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="sa-settings-actions">
            <button type="submit" class="sa-settings-save">
                <i class="fas fa-check" aria-hidden="true"></i>
                {{ __('message.save') }}
            </button>
        </div>
    </section>
</form>

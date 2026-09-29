@php
    $promo = $welcomePromotion['promo'] ?? null;
    $shops = $welcomePromotion['shops'] ?? collect();
    $search = $welcomePromotion['search'] ?? '';
    $enabledCount = $welcomePromotion['enabledCount'] ?? 0;
    $shopCount = $welcomePromotion['shopCount'] ?? 0;
@endphp

@if($promo)
<section class="sa-module-panel sa-fuel-default-panel">
    <header class="sa-module-panel__head">
        <h3>{{ __('message.sa_welcome_promo_global_title') }}</h3>
        <span>{{ __('message.sa_welcome_promo_global_badge') }}</span>
    </header>
    <p class="sa-fuel-default-panel__hint">{{ __('message.sa_welcome_promo_global_hint') }}</p>

    <form method="POST" action="{{ route('super-admin.welcome-promotion.settings') }}" class="sa-late-fine-defaults-form">
        @csrf
        <div class="sa-late-fine-defaults-form__grid">
            <div>
                <label class="form-control-label">{{ __('message.title') }}</label>
                <input type="text" name="title" value="{{ old('title', $promo->title) }}" class="form-control" required>
            </div>
            <div>
                <label class="form-control-label">{{ __('message.max_orders') }}</label>
                <input type="number" name="max_orders" min="1" max="1000" value="{{ old('max_orders', $promo->max_orders) }}" class="form-control" required>
            </div>
            <div>
                <label class="form-control-label">{{ __('message.discount_type') }}</label>
                <select name="discount_type" class="form-control">
                    <option value="percentage" @selected(old('discount_type', $promo->discount_type) === 'percentage')>{{ __('message.percentage') }}</option>
                    <option value="fixed" @selected(old('discount_type', $promo->discount_type) === 'fixed')>{{ __('message.fixed') }}</option>
                </select>
            </div>
            <div>
                <label class="form-control-label">{{ __('message.discount_value') }}</label>
                <input type="number" name="discount_value" step="any" min="0" value="{{ old('discount_value', $promo->discount_value) }}" class="form-control" required>
            </div>
            <div>
                <label class="form-control-label">{{ __('message.status') }}</label>
                <select name="status" class="form-control">
                    <option value="1" @selected((string) old('status', $promo->status) === '1')>{{ __('message.enable') }}</option>
                    <option value="0" @selected((string) old('status', $promo->status) === '0')>{{ __('message.disable') }}</option>
                </select>
            </div>
        </div>
        <div class="sa-fuel-default-form__row" style="margin-top: 1rem;">
            <button type="submit" class="sa-module-hero__btn sa-fuel-default-form__btn">
                <i class="fas fa-save" aria-hidden="true"></i>
                <span>{{ __('message.save') }}</span>
            </button>
        </div>
    </form>

    @if(session('success'))
        <p class="sa-fuel-default-panel__ok">{{ session('success') }}</p>
    @endif
    @foreach($errors->all() as $message)
        <p class="sa-fuel-default-panel__err">{{ $message }}</p>
    @endforeach
</section>
@endif

<section class="sa-module-panel sa-late-fine-staff-panel">
    <header class="sa-module-panel__head">
        <h3>{{ __('message.sa_welcome_promo_shops_title') }}</h3>
        <span>{{ $enabledCount }} / {{ $shopCount }} {{ __('message.enable') }}</span>
    </header>
    <p class="sa-fuel-default-panel__hint">{{ __('message.sa_welcome_promo_shops_hint') }}</p>

    <form method="GET" action="{{ route('super-admin.screens.show', 'welcome-promotion') }}" class="sa-expense-summary-filter" style="margin-bottom: 1rem;">
        <label>
            {{ __('message.search') }}
            <input type="text" name="q" value="{{ $search }}" placeholder="Online Shop">
        </label>
        <button type="submit" class="sa-module-hero__btn">{{ __('message.search') }}</button>
    </form>

    <div class="sa-module-table-wrap">
        <table class="sa-module-table sa-late-fine-staff-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('message.name') }}</th>
                    <th>{{ __('message.contact_number') }}</th>
                    <th>{{ __('message.sa_welcome_promo_shop_control') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shops as $i => $shop)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $shop->name }}</strong>
                            @if($shop->username)
                                <div class="text-muted" style="font-size: 12px;">{{ $shop->username }}</div>
                            @endif
                            <div class="text-muted" style="font-size: 12px;">
                                {{ __('message.sa_welcome_promo_used') }}: {{ (int) ($shop->welcome_orders_used ?? 0) }}
                            </div>
                        </td>
                        <td>{{ $shop->contact_number }}</td>
                        <td>
                            <form method="POST"
                                  action="{{ route('super-admin.welcome-promotion.shop', $shop->id) }}"
                                  class="sa-late-fine-allowance-form">
                                @csrf
                                @method('PUT')
                                @if($search !== '')
                                    <input type="hidden" name="q" value="{{ $search }}">
                                @endif
                                <label style="display:flex; align-items:center; gap:6px; margin:0 8px 0 0;">
                                    <input type="checkbox"
                                           name="welcome_promo_enabled"
                                           value="1"
                                           @checked((bool) ($shop->welcome_promo_enabled ?? true))>
                                    {{ __('message.sa_welcome_promo_give') }}
                                </label>
                                <input type="number"
                                       name="welcome_discount_percent"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       placeholder="{{ __('message.sa_welcome_promo_use_global') }}"
                                       value="{{ $shop->welcome_discount_percent !== null ? $shop->welcome_discount_percent : '' }}"
                                       class="sa-late-fine-allowance-input"
                                       title="{{ __('message.sa_welcome_promo_percent') }}"
                                       style="width: 110px;">
                                <span style="font-size:12px; opacity:.7;">%</span>
                                <button type="submit" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                    {{ __('message.save') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">{{ __('message.no_record_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

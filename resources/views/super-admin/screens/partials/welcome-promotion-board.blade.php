@php
    $shops = $welcomePromotion['shops'] ?? collect();
    $search = $welcomePromotion['search'] ?? '';
    $enabledCount = $welcomePromotion['enabledCount'] ?? 0;
    $shopCount = $welcomePromotion['shopCount'] ?? 0;
@endphp

<div class="sa-welcome-page">
    <section class="sa-module-panel sa-welcome-shops">
        <header class="sa-module-panel__head">
            <h3>{{ __('message.sa_welcome_promo_shops_title') }}</h3>
            <span>{{ $enabledCount }} / {{ $shopCount }} {{ __('message.enable') }}</span>
        </header>
        <form method="GET" action="{{ route('super-admin.screens.show', 'welcome-promotion') }}" class="sa-account-filter">
            <label class="sa-account-filter__search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('message.search') }}">
            </label>
            <button type="submit" class="sa-module-hero__btn">{{ __('message.search') }}</button>
        </form>
        <div class="sa-module-table-wrap">
            <table class="sa-module-table sa-welcome-table">
                <thead>
                    <tr>
                        <th class="sa-welcome-table__num">#</th>
                        <th>{{ __('message.name') }}</th>
                        <th>{{ __('message.sa_welcome_promo_give') }}</th>
                        <th>{{ __('message.max_orders') }}</th>
                        <th>%</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shops as $i => $shop)
                        @php
                            $shopFormId = 'sa-welcome-shop-'.$shop->id;
                            $shopOn = (bool) ($shop->welcome_promo_enabled ?? true);
                        @endphp
                        <tr>
                            <td class="sa-welcome-table__num">{{ $i + 1 }}</td>
                            <td>
                                <div class="sa-person">
                                    <span class="sa-avatar">{{ $initials((string) $shop->name) }}</span>
                                    <div>
                                        <strong>{{ $shop->name }}</strong>
                                        @if($shop->username)
                                            <span class="sa-welcome-meta">{{ $shop->username }}</span>
                                        @endif
                                        @if($shop->contact_number)
                                            <span class="sa-welcome-meta">{{ $shop->contact_number }}</span>
                                        @endif
                                        <span class="sa-welcome-meta">{{ __('message.sa_welcome_promo_used') }}: {{ (int) ($shop->welcome_orders_used ?? 0) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <input type="hidden" form="{{ $shopFormId }}" name="welcome_promo_enabled" value="0">
                                <label class="sa-work-switch {{ $shopOn ? 'is-on' : 'is-off' }}">
                                    <input type="checkbox"
                                           form="{{ $shopFormId }}"
                                           name="welcome_promo_enabled"
                                           value="1"
                                           @checked($shopOn)
                                           onchange="this.closest('.sa-work-switch').classList.toggle('is-on', this.checked); this.closest('.sa-work-switch').classList.toggle('is-off', !this.checked);">
                                    <span class="sa-work-switch__track" aria-hidden="true"></span>
                                    {{ __('message.sa_welcome_promo_give') }}
                                </label>
                            </td>
                            <td>
                                <div class="sa-welcome-percent">
                                    <input type="number"
                                           form="{{ $shopFormId }}"
                                           name="welcome_max_orders"
                                           min="1"
                                           max="1000"
                                           step="1"
                                           placeholder="{{ __('message.sa_welcome_promo_use_global') }}"
                                           value="{{ $shop->welcome_max_orders !== null ? (int) $shop->welcome_max_orders : '' }}"
                                           class="sa-no-spin"
                                           title="{{ __('message.max_orders') }}"
                                           inputmode="numeric">
                                </div>
                            </td>
                            <td>
                                <form method="POST"
                                      action="{{ route('super-admin.welcome-promotion.shop', $shop->id) }}"
                                      class="sa-welcome-shop-form"
                                      id="{{ $shopFormId }}">
                                    @csrf
                                    @method('PUT')
                                    @if($search !== '')
                                        <input type="hidden" name="q" value="{{ $search }}">
                                    @endif
                                    <div class="sa-welcome-percent">
                                        <input type="number"
                                               name="welcome_discount_percent"
                                               min="0"
                                               max="100"
                                               step="0.01"
                                               placeholder="{{ __('message.sa_welcome_promo_use_global') }}"
                                               value="{{ $shop->welcome_discount_percent !== null ? $shop->welcome_discount_percent : '' }}"
                                               class="sa-no-spin"
                                               title="{{ __('message.sa_welcome_promo_percent') }}">
                                        <span>%</span>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <button type="submit" form="{{ $shopFormId }}" class="sa-module-hero__btn sa-late-fine-allowance-btn">
                                    {{ __('message.save') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">{{ __('message.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

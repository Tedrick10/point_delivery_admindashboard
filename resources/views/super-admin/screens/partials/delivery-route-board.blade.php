@php
    $tab = $deliveryRoute['tab'];
    $branches = $deliveryRoute['branches'];
    $cities = $deliveryRoute['cities'];
    $townships = $deliveryRoute['townships'];
    $filterCityId = $deliveryRoute['filterCityId'];
    $canEdit = $deliveryRoute['canEdit'];
    $drRoutes = $deliveryRoute['drRoutes'];
    $indexIsSa = true;
    $settlementModes = [
        \App\Models\Branch::SETTLEMENT_MANUAL => __('message.branch_settlement_manual'),
        \App\Models\Branch::SETTLEMENT_MANUAL_HALF_DELI => __('message.branch_settlement_manual_half_deli'),
        \App\Models\Branch::SETTLEMENT_HALF_DELI => __('message.branch_settlement_half_deli'),
    ];
@endphp

<section class="sa-module-panel sa-delivery-route-panel">
    <div class="sa-dr-tabs" role="tablist">
        <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'from_to']) }}"
           class="sa-dr-tabs__item {{ $tab === 'from_to' ? 'is-active' : '' }}">
            {{ __('message.from') }} / {{ __('message.to') }}
            <em>{{ $branches->count() }}</em>
        </a>
        <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'city']) }}"
           class="sa-dr-tabs__item {{ $tab === 'city' ? 'is-active' : '' }}">
            {{ __('message.city') }}
            <em>{{ $cities->count() }}</em>
        </a>
        <a href="{{ route('super-admin.screens.show', ['screen' => 'delivery-route', 'tab' => 'township', 'city_id' => $filterCityId ?: null]) }}"
           class="sa-dr-tabs__item {{ $tab === 'township' ? 'is-active' : '' }}">
            {{ __('message.township') }}
            <em>{{ $townships->count() }}</em>
        </a>
    </div>

    @if($tab === 'from_to')
        @if($canEdit)
            <form method="POST" action="{{ route($drRoutes['branches.store']) }}" class="sa-dr-toolbar">
                @csrf
                <label class="sa-dr-field">
                    <span>{{ __('message.from') }} / {{ __('message.to') }}</span>
                    <input type="text" name="name" required maxlength="255"
                           placeholder="{{ __('message.enter_name', ['name' => __('message.city')]) }}">
                </label>
                <button type="submit" class="sa-dr-add">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    {{ __('message.add') }}
                </button>
            </form>
        @endif

        <div class="sa-dr-list">
            @forelse($branches as $index => $branch)
                @php
                    $mode = method_exists($branch, 'settlementMode')
                        ? $branch->settlementMode()
                        : \App\Models\Branch::SETTLEMENT_MANUAL;
                @endphp
                <article class="sa-dr-row sa-dr-row--route">
                    <div class="sa-dr-index">{{ $index + 1 }}</div>
                    <div class="sa-dr-main sa-dr-main--route">
                        @if($canEdit)
                            <form method="POST" action="{{ route($drRoutes['branches.update'], $branch->id) }}" id="branch-update-{{ $branch->id }}" class="sa-dr-row__name">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $branch->name }}" required maxlength="255">
                                <input type="hidden" name="status" value="{{ $branch->status }}">
                            </form>
                        @else
                            <strong class="sa-dr-row__title">{{ $branch->name }}</strong>
                        @endif
                        <div class="sa-dr-modes" role="radiogroup" aria-label="{{ __('message.branch_settlement_mode') }}">
                            @foreach($settlementModes as $value => $label)
                                <label class="sa-dr-mode {{ $mode === $value ? 'is-active' : '' }}">
                                    <input
                                        type="radio"
                                        name="delivery_settlement_mode"
                                        value="{{ $value }}"
                                        @if($canEdit) form="branch-update-{{ $branch->id }}" @endif
                                        @checked($mode === $value)
                                        @disabled(! $canEdit)
                                    >
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="sa-dr-ops">
                        <span class="sa-dr-status {{ (int) $branch->status === 1 ? 'is-on' : '' }}">
                            {{ (int) $branch->status === 1 ? __('message.enable') : __('message.disable') }}
                        </span>
                        @if($canEdit)
                            <button type="submit" form="branch-update-{{ $branch->id }}" class="sa-dr-btn sa-dr-btn--save">{{ __('message.update') }}</button>
                            <form method="POST" action="{{ route($drRoutes['branches.destroy'], $branch->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $branch->name])));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sa-dr-btn sa-dr-btn--del">{{ __('message.delete') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <p class="sa-dr-empty">{{ __('message.no_record_found') }}</p>
            @endforelse
        </div>
    @elseif($tab === 'city')
        @if($canEdit)
            <form method="POST" action="{{ route($drRoutes['cities.store']) }}" class="sa-dr-toolbar">
                @csrf
                <label class="sa-dr-field">
                    <span>{{ __('message.city') }}</span>
                    <input type="text" name="name" required maxlength="120" placeholder="မန္တလေး">
                </label>
                <button type="submit" class="sa-dr-add">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    {{ __('message.add') }}
                </button>
            </form>
        @endif

        <div class="sa-dr-list">
            @forelse($cities as $index => $city)
                <article class="sa-dr-row sa-dr-row--city">
                    <div class="sa-dr-index">{{ $index + 1 }}</div>
                    <div class="sa-dr-main">
                        @if($canEdit)
                            <form method="POST" action="{{ route($drRoutes['cities.update'], $city->id) }}" id="city-update-{{ $city->id }}" class="sa-dr-row__name">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ $city->name }}">
                                <input type="text" name="name_mm" value="{{ $city->displayName() }}" required maxlength="120">
                                <input type="hidden" name="status" value="{{ $city->status }}">
                            </form>
                        @else
                            <strong class="sa-dr-row__title">{{ $city->displayName() }}</strong>
                        @endif
                        <a class="sa-dr-count" href="{{ route($drRoutes['index'], ['screen' => 'delivery-route', 'tab' => 'township', 'city_id' => $city->id]) }}">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            {{ $city->townships_count }}
                        </a>
                    </div>
                    @if($canEdit)
                        <div class="sa-dr-ops">
                            <button type="submit" form="city-update-{{ $city->id }}" class="sa-dr-btn sa-dr-btn--save">{{ __('message.update') }}</button>
                            <form method="POST" action="{{ route($drRoutes['cities.destroy'], $city->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $city->displayName()])));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sa-dr-btn sa-dr-btn--del">{{ __('message.delete') }}</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <p class="sa-dr-empty">{{ __('message.no_record_found') }}</p>
            @endforelse
        </div>
    @else
        <form method="GET" action="{{ route($drRoutes['index'], ['screen' => 'delivery-route']) }}" class="sa-dr-toolbar sa-dr-toolbar--filter">
            <input type="hidden" name="screen" value="delivery-route">
            <input type="hidden" name="tab" value="township">
            <label class="sa-dr-field">
                <span>{{ __('message.city') }}</span>
                <select name="city_id" onchange="this.form.submit()">
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected((int) $filterCityId === (int) $city->id)>{{ $city->displayName() }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        @if($canEdit)
            <form method="POST" action="{{ route($drRoutes['townships.store']) }}" class="sa-dr-toolbar">
                @csrf
                <input type="hidden" name="delivery_city_id" value="{{ $filterCityId }}">
                <label class="sa-dr-field">
                    <span>{{ __('message.township') }}</span>
                    <input type="text" name="name" required maxlength="120"
                           placeholder="{{ __('message.enter_name', ['name' => __('message.township')]) }}"
                           @disabled($filterCityId <= 0)>
                </label>
                <label class="sa-dr-field sa-dr-field--amt">
                    <span>{{ __('message.deli_amount') }}</span>
                    <input type="number" name="deli_amount" min="0" step="1" value="2500"
                           @disabled($filterCityId <= 0)>
                </label>
                <button type="submit" class="sa-dr-add" @disabled($filterCityId <= 0)>
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    {{ __('message.add') }}
                </button>
            </form>
        @endif

        <div class="sa-dr-list">
            @forelse($townships as $index => $township)
                <article class="sa-dr-row sa-dr-row--township">
                    <div class="sa-dr-index">{{ $index + 1 }}</div>
                    <div class="sa-dr-main sa-dr-main--township">
                        <span class="sa-dr-city-tag">{{ $township->city?->displayName() }}</span>
                        @if($canEdit)
                            <form id="township-update-{{ $township->id }}" method="POST" action="{{ route($drRoutes['townships.update'], $township->id) }}" class="sa-dr-row__name">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="delivery_city_id" value="{{ $township->delivery_city_id }}">
                                <input type="hidden" name="name_mm" value="{{ $township->name_mm }}">
                                <input type="hidden" name="status" value="{{ $township->status }}">
                                <input type="text" name="name" value="{{ $township->name }}" required maxlength="120">
                            </form>
                            <input form="township-update-{{ $township->id }}" type="number" name="deli_amount" min="0" step="1"
                                   value="{{ (int) $township->deli_amount }}" class="sa-dr-amt">
                        @else
                            <strong class="sa-dr-row__title">{{ $township->displayName() }}</strong>
                            <span class="sa-dr-amt-text">{{ number_format((float) $township->deli_amount) }}</span>
                        @endif
                    </div>
                    @if($canEdit)
                        <div class="sa-dr-ops">
                            <button form="township-update-{{ $township->id }}" type="submit" class="sa-dr-btn sa-dr-btn--save">{{ __('message.update') }}</button>
                            <form method="POST" action="{{ route($drRoutes['townships.destroy'], $township->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $township->displayName()])));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sa-dr-btn sa-dr-btn--del">{{ __('message.delete') }}</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <p class="sa-dr-empty">{{ __('message.no_record_found') }}</p>
            @endforelse
        </div>
    @endif
</section>

@if($canEdit && $tab === 'from_to')
<script>
    (function () {
        document.querySelectorAll('.sa-dr-modes').forEach(function (group) {
            group.addEventListener('change', function (e) {
                if (!e.target || e.target.type !== 'radio') return;
                group.querySelectorAll('.sa-dr-mode').forEach(function (label) {
                    label.classList.toggle('is-active', label.querySelector('input') === e.target);
                });
            });
        });
    })();
</script>
@endif

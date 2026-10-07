@php
    $drRoutes = $drRoutes ?? [
        'index' => 'super-admin.screens.show',
        'townships.store' => 'super-admin.delivery-route.townships.store',
        'townships.update' => 'super-admin.delivery-route.townships.update',
        'townships.destroy' => 'super-admin.delivery-route.townships.destroy',
    ];
@endphp

<div class="sa-route-toolbar">
    <form method="GET" action="{{ route($drRoutes['index'], ['screen' => 'delivery-route']) }}" class="sa-route-filter">
        <input type="hidden" name="screen" value="delivery-route">
        <input type="hidden" name="tab" value="township">
        <select name="city_id" id="filter_city_id" onchange="this.form.submit()" aria-label="{{ __('message.city') }}">
            @foreach($cities as $city)
                <option value="{{ $city->id }}" @selected((int) $filterCityId === (int) $city->id)>{{ $city->displayName() }}</option>
            @endforeach
        </select>
    </form>

    @if($canEdit)
        <form method="POST" action="{{ route($drRoutes['townships.store']) }}" class="sa-city-add sa-route-add">
            @csrf
            <input type="hidden" name="delivery_city_id" value="{{ $filterCityId }}">
            <input type="text" name="name" id="township_name" required maxlength="120"
                   placeholder="{{ __('message.township') }}"
                   @disabled($filterCityId <= 0)>
            <input type="number" name="deli_amount" id="township_deli_amount" min="0" step="1" value="2500"
                   class="sa-route-add__amt" placeholder="{{ __('message.deli_amount') }}"
                   @disabled($filterCityId <= 0)>
            <button type="submit" class="sa-city-add__btn" @disabled($filterCityId <= 0)>
                <i class="fas fa-plus" aria-hidden="true"></i>
                {{ __('message.add') }}
            </button>
        </form>
    @endif
</div>

@if($townships->isEmpty())
    <p class="pds-route-empty">{{ __('message.no_record_found') }}</p>
@else
    <div class="sa-city-grid sa-route-grid">
        @foreach($townships as $index => $township)
            @php
                $label = $township->displayName();
            @endphp
            <article class="sa-city-card sa-route-card">
                <form method="POST" action="{{ route($drRoutes['townships.update'], $township->id) }}" class="sa-route-card__form" id="sa-tsp-upd-{{ $township->id }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="delivery_city_id" value="{{ $township->delivery_city_id }}">
                    <input type="hidden" name="name_mm" value="{{ $township->name_mm }}">
                    <input type="hidden" name="status" value="{{ $township->status }}">
                    <div class="sa-city-card__top">
                        <span class="sa-city-card__no">{{ $index + 1 }}</span>
                        <div class="sa-city-card__name">
                            <input type="text" name="name" value="{{ $township->name }}" required maxlength="120">
                        </div>
                    </div>
                </form>
                <div class="sa-city-card__foot">
                    <input type="number" form="sa-tsp-upd-{{ $township->id }}" name="deli_amount" min="0" step="1"
                           value="{{ (int) $township->deli_amount }}" class="sa-route-amt" aria-label="{{ __('message.deli_amount') }}">
                    @if($canEdit)
                        <div class="sa-city-card__actions">
                            <button type="submit" form="sa-tsp-upd-{{ $township->id }}" class="sa-city-card__save">{{ __('message.update') }}</button>
                            <form method="POST" action="{{ route($drRoutes['townships.destroy'], $township->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $label])));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sa-city-card__del">{{ __('message.delete') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif

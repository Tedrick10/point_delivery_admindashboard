@php
    $drRoutes = $drRoutes ?? [
        'index' => 'super-admin.screens.show',
        'cities.store' => 'super-admin.delivery-route.cities.store',
        'cities.update' => 'super-admin.delivery-route.cities.update',
        'cities.destroy' => 'super-admin.delivery-route.cities.destroy',
    ];
@endphp

@if($canEdit)
    <form method="POST" action="{{ route($drRoutes['cities.store']) }}" class="sa-city-add">
        @csrf
        <input type="text" name="name" id="city_name" required maxlength="120" placeholder="{{ __('message.city') }}">
        <button type="submit" class="sa-city-add__btn">
            <i class="fas fa-plus" aria-hidden="true"></i>
            {{ __('message.add') }}
        </button>
    </form>
@endif

@if($cities->isEmpty())
    <p class="pds-route-empty">{{ __('message.no_record_found') }}</p>
@else
    <div class="sa-city-grid">
        @foreach($cities as $index => $city)
            @php
                $label = $city->displayName();
            @endphp
            <article class="sa-city-card">
                <div class="sa-city-card__top">
                    <span class="sa-city-card__no">{{ $index + 1 }}</span>
                    @if($canEdit)
                        <form method="POST" action="{{ route($drRoutes['cities.update'], $city->id) }}" class="sa-city-card__name" id="sa-city-upd-{{ $city->id }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $city->name }}">
                            <input type="text" name="name_mm" value="{{ $label }}" required maxlength="120">
                            <input type="hidden" name="status" value="{{ $city->status }}">
                        </form>
                    @else
                        <strong class="sa-city-card__title">{{ $label }}</strong>
                    @endif
                </div>
                <div class="sa-city-card__foot">
                    <a class="sa-city-card__chip"
                       href="{{ route($drRoutes['index'], ['screen' => 'delivery-route', 'tab' => 'township', 'city_id' => $city->id]) }}">
                        {{ $city->townships_count }} {{ __('message.township') }}
                    </a>
                    @if($canEdit)
                        <div class="sa-city-card__actions">
                            <button type="submit" form="sa-city-upd-{{ $city->id }}" class="sa-city-card__save">{{ __('message.update') }}</button>
                            <form method="POST" action="{{ route($drRoutes['cities.destroy'], $city->id) }}"
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

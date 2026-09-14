@php
    $drRoutes = $drRoutes ?? [
        'index' => 'delivery-route-locations.index',
        'townships.store' => 'delivery-route-locations.townships.store',
        'townships.update' => 'delivery-route-locations.townships.update',
        'townships.destroy' => 'delivery-route-locations.townships.destroy',
    ];
    $indexIsSa = ($drRoutes['index'] ?? '') === 'super-admin.screens.show';
@endphp
<form method="GET" action="{{ route($drRoutes['index'], $indexIsSa ? ['screen' => 'delivery-route'] : []) }}" class="pds-route-form">
    @if($indexIsSa)
        <input type="hidden" name="screen" value="delivery-route">
    @endif
    <input type="hidden" name="tab" value="township">
    <div class="pds-route-form__field">
        <label for="filter_city_id">{{ __('message.city') }}</label>
        <select name="city_id" id="filter_city_id" onchange="this.form.submit()">
            @foreach($cities as $city)
                <option value="{{ $city->id }}" @selected((int) $filterCityId === (int) $city->id)>{{ $city->displayName() }}</option>
            @endforeach
        </select>
    </div>
</form>

@if($canEdit)
    <form method="POST" action="{{ route($drRoutes['townships.store']) }}" class="pds-route-form">
        @csrf
        <input type="hidden" name="delivery_city_id" value="{{ $filterCityId }}">
        <div class="pds-route-form__field">
            <label for="township_name">{{ __('message.township') }}</label>
            <input type="text" name="name" id="township_name" required maxlength="120"
                   placeholder="{{ __('message.enter_name', ['name' => __('message.township')]) }}"
                   @disabled($filterCityId <= 0)>
        </div>
        <div class="pds-route-form__field pds-route-form__field--amount">
            <label for="township_deli_amount">{{ __('message.deli_amount') }}</label>
            <input type="number" name="deli_amount" id="township_deli_amount" min="0" step="1" value="2500"
                   @disabled($filterCityId <= 0)>
        </div>
        <button type="submit" class="pds-daily-check-search-btn" @disabled($filterCityId <= 0)>
            <i class="fas fa-plus" aria-hidden="true"></i>
            <span>{{ __('message.add') }}</span>
        </button>
    </form>
@endif

<div class="pds-route-table-wrap">
    <table class="pds-route-table">
        <thead>
            <tr>
                <th style="width:70px">{{ __('message.expense_summary_no') }}</th>
                <th>{{ __('message.city') }}</th>
                <th>{{ __('message.township') }}</th>
                <th style="width:160px">{{ __('message.deli_amount') }}</th>
                @if($canEdit)
                    <th style="width:160px"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($townships as $index => $township)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $township->city?->displayName() }}</td>
                    @if($canEdit)
                        <td>
                            <form id="township-update-{{ $township->id }}" method="POST" action="{{ route($drRoutes['townships.update'], $township->id) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="delivery_city_id" value="{{ $township->delivery_city_id }}">
                                <input type="hidden" name="name_mm" value="{{ $township->name_mm }}">
                                <input type="hidden" name="status" value="{{ $township->status }}">
                                <input type="text" name="name" value="{{ $township->name }}" required maxlength="120" class="pds-route-name-input">
                            </form>
                        </td>
                        <td>
                            <input form="township-update-{{ $township->id }}" type="number" name="deli_amount" min="0" step="1"
                                   value="{{ (int) $township->deli_amount }}" class="pds-route-amount-input">
                        </td>
                        <td>
                            <div class="pds-route-actions">
                                <button form="township-update-{{ $township->id }}" type="submit" class="pds-cash-payout-btn pds-cash-payout-btn--ok">{{ __('message.update') }}</button>
                                <form method="POST" action="{{ route($drRoutes['townships.destroy'], $township->id) }}"
                                      onsubmit="return confirm(@json(__('message.delete_form', ['form' => $township->displayName()])));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pds-cash-payout-btn pds-cash-payout-btn--warn">{{ __('message.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    @else
                        <td>{{ $township->displayName() }}</td>
                        <td>{{ number_format((float) $township->deli_amount) }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canEdit ? 5 : 4 }}" class="pds-route-empty">{{ __('message.no_record_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<style>
    .pds-route-form__field--amount { max-width: 160px; flex: 0 0 160px; }
    .pds-route-name-input,
    .pds-route-amount-input {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 10px;
    }
    .pds-route-actions { display: flex; gap: 8px; align-items: center; }
</style>

<form method="GET" action="{{ route('delivery-route-locations.index') }}" class="pds-route-form">
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
    <form method="POST" action="{{ route('delivery-route-locations.townships.store') }}" class="pds-route-form">
        @csrf
        <input type="hidden" name="delivery_city_id" value="{{ $filterCityId }}">
        <div class="pds-route-form__field">
            <label for="township_name">{{ __('message.township') }}</label>
            <input type="text" name="name" id="township_name" required maxlength="120"
                   placeholder="{{ __('message.enter_name', ['name' => __('message.township')]) }}"
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
                    <td>
                        @if($canEdit)
                            <form method="POST" action="{{ route('delivery-route-locations.townships.update', $township->id) }}" class="pds-route-inline-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="delivery_city_id" value="{{ $township->delivery_city_id }}">
                                <input type="text" name="name" value="{{ $township->name }}" required maxlength="120">
                                <input type="hidden" name="name_mm" value="{{ $township->name_mm }}">
                                <input type="hidden" name="status" value="{{ $township->status }}">
                                <button type="submit" class="pds-cash-payout-btn pds-cash-payout-btn--ok">{{ __('message.update') }}</button>
                            </form>
                        @else
                            {{ $township->displayName() }}
                        @endif
                    </td>
                    @if($canEdit)
                        <td>
                            <form method="POST" action="{{ route('delivery-route-locations.townships.destroy', $township->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $township->displayName()])));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pds-cash-payout-btn pds-cash-payout-btn--warn">{{ __('message.delete') }}</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canEdit ? 4 : 3 }}" class="pds-route-empty">{{ __('message.no_record_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<style>
    .pds-route-inline-form { display: flex; gap: 8px; align-items: center; }
    .pds-route-inline-form input[type="text"] { flex: 1; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; }
</style>

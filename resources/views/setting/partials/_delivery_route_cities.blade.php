@if($canEdit)
    <form method="POST" action="{{ route('delivery-route-locations.cities.store') }}" class="pds-route-form">
        @csrf
        <div class="pds-route-form__field">
            <label for="city_name">{{ __('message.city') }}</label>
            <input type="text" name="name" id="city_name" required maxlength="120" placeholder="မန္တလေး">
        </div>
        <button type="submit" class="pds-daily-check-search-btn">
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
            @forelse($cities as $index => $city)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($canEdit)
                            <form method="POST" action="{{ route('delivery-route-locations.cities.update', $city->id) }}" class="pds-route-inline-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ $city->name }}">
                                <input type="text" name="name_mm" value="{{ $city->displayName() }}" required maxlength="120">
                                <input type="hidden" name="status" value="{{ $city->status }}">
                                <button type="submit" class="pds-cash-payout-btn pds-cash-payout-btn--ok">{{ __('message.update') }}</button>
                            </form>
                        @else
                            {{ $city->displayName() }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('delivery-route-locations.index', ['tab' => 'township', 'city_id' => $city->id]) }}">
                            {{ $city->townships_count }}
                        </a>
                    </td>
                    @if($canEdit)
                        <td>
                            <form method="POST" action="{{ route('delivery-route-locations.cities.destroy', $city->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $city->displayName()])));">
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
    .pds-route-inline-form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .pds-route-inline-form input[type="text"] { min-width: 140px; flex: 1; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; }
</style>

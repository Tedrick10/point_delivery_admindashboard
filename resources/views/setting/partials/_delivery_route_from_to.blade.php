@if($canEdit)
    <form method="POST" action="{{ route('delivery-route-locations.branches.store') }}" class="pds-route-form">
        @csrf
        <div class="pds-route-form__field">
            <label for="from_to_name">{{ __('message.from') }} / {{ __('message.to') }}</label>
            <input type="text" name="name" id="from_to_name" required maxlength="255"
                   placeholder="{{ __('message.enter_name', ['name' => __('message.city')]) }}">
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
                <th>{{ __('message.name') }}</th>
                <th style="width:120px">{{ __('message.status') }}</th>
                @if($canEdit)
                    <th style="width:160px"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $index => $branch)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($canEdit)
                            <form method="POST" action="{{ route('delivery-route-locations.branches.update', $branch->id) }}" class="pds-route-inline-form">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $branch->name }}" required maxlength="255">
                                <input type="hidden" name="status" value="{{ $branch->status }}">
                                <button type="submit" class="pds-cash-payout-btn pds-cash-payout-btn--ok">{{ __('message.update') }}</button>
                            </form>
                        @else
                            {{ $branch->name }}
                        @endif
                    </td>
                    <td>{{ (int) $branch->status === 1 ? __('message.enable') : __('message.disable') }}</td>
                    @if($canEdit)
                        <td>
                            <form method="POST" action="{{ route('delivery-route-locations.branches.destroy', $branch->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $branch->name])));">
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

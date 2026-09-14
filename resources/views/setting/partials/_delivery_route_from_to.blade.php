@php
    $drRoutes = $drRoutes ?? [
        'index' => 'delivery-route-locations.index',
        'branches.store' => 'delivery-route-locations.branches.store',
        'branches.update' => 'delivery-route-locations.branches.update',
        'branches.destroy' => 'delivery-route-locations.branches.destroy',
    ];
    $indexParams = ($drRoutes['index'] ?? '') === 'super-admin.screens.show'
        ? ['screen' => 'delivery-route']
        : [];
    $settlementModes = [
        \App\Models\Branch::SETTLEMENT_MANUAL => __('message.branch_settlement_manual'),
        \App\Models\Branch::SETTLEMENT_MANUAL_HALF_DELI => __('message.branch_settlement_manual_half_deli'),
        \App\Models\Branch::SETTLEMENT_HALF_DELI => __('message.branch_settlement_half_deli'),
    ];
@endphp
@if($canEdit)
    <form method="POST" action="{{ route($drRoutes['branches.store']) }}" class="pds-route-form">
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
                <th style="min-width:140px">{{ __('message.name') }}</th>
                <th style="min-width:320px">{{ __('message.branch_settlement_mode') }}</th>
                <th style="width:100px">{{ __('message.status') }}</th>
                @if($canEdit)
                    <th style="width:160px"></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $index => $branch)
                @php
                    $mode = method_exists($branch, 'settlementMode')
                        ? $branch->settlementMode()
                        : \App\Models\Branch::SETTLEMENT_MANUAL;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($canEdit)
                            <form method="POST" action="{{ route($drRoutes['branches.update'], $branch->id) }}" class="pds-route-inline-form" id="branch-update-{{ $branch->id }}">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $branch->name }}" required maxlength="255">
                                <input type="hidden" name="status" value="{{ $branch->status }}">
                            </form>
                        @else
                            {{ $branch->name }}
                        @endif
                    </td>
                    <td>
                        <div class="pds-route-settlement-checks" role="radiogroup" aria-label="{{ __('message.branch_settlement_mode') }}">
                            @foreach($settlementModes as $value => $label)
                                <label class="pds-route-settlement-check {{ $mode === $value ? 'is-active' : '' }}">
                                    <input
                                        type="radio"
                                        name="delivery_settlement_mode"
                                        value="{{ $value }}"
                                        @if($canEdit) form="branch-update-{{ $branch->id }}" @endif
                                        @checked($mode === $value)
                                        @disabled(! $canEdit)
                                    >
                                    <span class="pds-route-settlement-check__box" aria-hidden="true">
                                        <i class="fas fa-check"></i>
                                    </span>
                                    <span class="pds-route-settlement-check__label">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </td>
                    <td>{{ (int) $branch->status === 1 ? __('message.enable') : __('message.disable') }}</td>
                    @if($canEdit)
                        <td>
                            <div class="pds-route-actions">
                                <button type="submit" form="branch-update-{{ $branch->id }}" class="pds-cash-payout-btn pds-cash-payout-btn--ok">{{ __('message.update') }}</button>
                                <form method="POST" action="{{ route($drRoutes['branches.destroy'], $branch->id) }}"
                                      onsubmit="return confirm(@json(__('message.delete_form', ['form' => $branch->name])));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pds-cash-payout-btn pds-cash-payout-btn--warn">{{ __('message.delete') }}</button>
                                </form>
                            </div>
                        </td>
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
    .pds-route-inline-form { display: flex; gap: 8px; align-items: center; }
    .pds-route-inline-form input[type="text"] { flex: 1; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 10px; }
    .pds-route-settlement-checks {
        display: flex; flex-wrap: wrap; gap: 8px; align-items: stretch;
    }
    .pds-route-settlement-check {
        display: inline-flex; align-items: center; gap: 8px;
        margin: 0; padding: 8px 12px; border-radius: 12px;
        border: 1px solid #e2e8f0; background: #fff; cursor: pointer;
        user-select: none; transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }
    .pds-route-settlement-check input {
        position: absolute; opacity: 0; pointer-events: none;
    }
    .pds-route-settlement-check__box {
        width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0;
        border: 2px solid #cbd5e1; background: #fff; color: transparent;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11px;
    }
    .pds-route-settlement-check__label {
        font-size: 13px; font-weight: 700; color: #334155; line-height: 1.2;
    }
    .pds-route-settlement-check.is-active,
    .pds-route-settlement-check:has(input:checked) {
        border-color: #0f766e; background: #ecfdf5; box-shadow: 0 0 0 1px #0f766e inset;
    }
    .pds-route-settlement-check.is-active .pds-route-settlement-check__box,
    .pds-route-settlement-check:has(input:checked) .pds-route-settlement-check__box {
        border-color: #0f766e; background: #0f766e; color: #fff;
    }
    .pds-route-settlement-check.is-active .pds-route-settlement-check__label,
    .pds-route-settlement-check:has(input:checked) .pds-route-settlement-check__label {
        color: #0f766e;
    }
    .pds-route-settlement-check:has(input:disabled) { cursor: default; opacity: .9; }
    .pds-route-actions { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
</style>
@if($canEdit)
<script>
    (function () {
        document.querySelectorAll('.pds-route-settlement-checks').forEach(function (group) {
            group.addEventListener('change', function (e) {
                if (!e.target || e.target.type !== 'radio') return;
                group.querySelectorAll('.pds-route-settlement-check').forEach(function (label) {
                    label.classList.toggle('is-active', label.querySelector('input') === e.target);
                });
            });
        });
    })();
</script>
@endif

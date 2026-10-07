@php
    $drRoutes = $drRoutes ?? [
        'index' => 'super-admin.screens.show',
        'branches.store' => 'super-admin.delivery-route.branches.store',
        'branches.update' => 'super-admin.delivery-route.branches.update',
        'branches.destroy' => 'super-admin.delivery-route.branches.destroy',
    ];
    $settlementModes = [
        \App\Models\Branch::SETTLEMENT_MANUAL => __('message.branch_settlement_manual'),
        \App\Models\Branch::SETTLEMENT_MANUAL_HALF_DELI => __('message.branch_settlement_manual_half_deli'),
        \App\Models\Branch::SETTLEMENT_HALF_DELI => __('message.branch_settlement_half_deli'),
    ];
@endphp

@if($canEdit)
    <form method="POST" action="{{ route($drRoutes['branches.store']) }}" class="sa-city-add">
        @csrf
        <input type="text" name="name" id="from_to_name" required maxlength="255"
               placeholder="{{ __('message.from') }} / {{ __('message.to') }}">
        <button type="submit" class="sa-city-add__btn">
            <i class="fas fa-plus" aria-hidden="true"></i>
            {{ __('message.add') }}
        </button>
    </form>
@endif

@if($branches->isEmpty())
    <p class="pds-route-empty">{{ __('message.no_record_found') }}</p>
@else
    <div class="sa-city-grid sa-route-grid sa-route-grid--from">
        @foreach($branches as $index => $branch)
            @php
                $mode = method_exists($branch, 'settlementMode')
                    ? $branch->settlementMode()
                    : \App\Models\Branch::SETTLEMENT_MANUAL;
            @endphp
            <article class="sa-city-card sa-route-card">
                <form method="POST" action="{{ route($drRoutes['branches.update'], $branch->id) }}" class="sa-route-card__form" id="sa-br-upd-{{ $branch->id }}">
                    @csrf
                    @method('PUT')
                    <div class="sa-city-card__top">
                        <span class="sa-city-card__no">{{ $index + 1 }}</span>
                        <div class="sa-city-card__name">
                            <input type="text" name="name" value="{{ $branch->name }}" required maxlength="255">
                            <input type="hidden" name="status" value="{{ $branch->status }}">
                        </div>
                    </div>
                    <div class="sa-route-modes" role="radiogroup" aria-label="{{ __('message.branch_settlement_mode') }}">
                        @foreach($settlementModes as $value => $label)
                            <label class="sa-route-mode {{ $mode === $value ? 'is-active' : '' }}">
                                <input type="radio" name="delivery_settlement_mode" value="{{ $value }}" @checked($mode === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </form>
                <div class="sa-city-card__foot">
                    @if($canEdit)
                        <div class="sa-city-card__actions">
                            <button type="submit" form="sa-br-upd-{{ $branch->id }}" class="sa-city-card__save">{{ __('message.update') }}</button>
                            <form method="POST" action="{{ route($drRoutes['branches.destroy'], $branch->id) }}"
                                  onsubmit="return confirm(@json(__('message.delete_form', ['form' => $branch->name])));">
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
@if($canEdit)
<script>
    document.querySelectorAll('.sa-route-modes').forEach(function (group) {
        group.addEventListener('change', function (e) {
            if (!e.target || e.target.type !== 'radio') return;
            group.querySelectorAll('.sa-route-mode').forEach(function (label) {
                label.classList.toggle('is-active', label.querySelector('input') === e.target);
            });
        });
    });
</script>
@endif

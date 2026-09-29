{{-- Shared destination / settlement branch tabs --}}
@php
    $branchTabs = $branchTabs ?? collect();
    $selectedBranchId = array_key_exists('selectedBranchId', get_defined_vars())
        ? $selectedBranchId
        : null;
    $branchTabCounts = collect($branchTabCounts ?? []);
    $includeAll = $includeAll ?? false;
    $allCount = $allCount ?? null;
    $routeName = $routeName ?? null;
    $routeQuery = $routeQuery ?? [];
    $paramName = $paramName ?? 'branch_id';
    $allLabel = $allLabel ?? __('message.all_branches');
@endphp
@if($branchTabs->isNotEmpty() && $routeName)
    <div class="pds-dm-branch-tabs" role="tablist" aria-label="{{ __('message.branch') }}">
        @if($includeAll && $branchTabs->count() > 1)
            <a href="{{ route($routeName, array_filter($routeQuery, fn ($v) => $v !== null && $v !== '')) }}"
               class="pds-dm-branch-tab{{ $selectedBranchId === null || (int) $selectedBranchId === 0 ? ' is-active' : '' }}"
               role="tab"
               aria-selected="{{ $selectedBranchId === null || (int) $selectedBranchId === 0 ? 'true' : 'false' }}">
                <span class="pds-dm-branch-tab__label">{{ $allLabel }}</span>
                @if($allCount !== null)
                    <span class="pds-dm-branch-tab__count">{{ (int) $allCount }}</span>
                @endif
            </a>
        @endif
        @foreach($branchTabs as $branchTab)
            @php
                $tabQuery = array_filter(
                    array_merge($routeQuery, [$paramName => $branchTab->id]),
                    fn ($v) => $v !== null && $v !== ''
                );
                $isActive = (int) $selectedBranchId === (int) $branchTab->id;
            @endphp
            <a href="{{ route($routeName, $tabQuery) }}"
               class="pds-dm-branch-tab{{ $isActive ? ' is-active' : '' }}"
               role="tab"
               aria-selected="{{ $isActive ? 'true' : 'false' }}">
                <span class="pds-dm-branch-tab__label">{{ $branchTab->name }}</span>
                <span class="pds-dm-branch-tab__count">{{ (int) ($branchTabCounts[$branchTab->id] ?? 0) }}</span>
            </a>
        @endforeach
    </div>
@endif

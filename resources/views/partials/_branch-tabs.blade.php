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
    <style>
        .pds-dm-branch-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .pds-dm-branch-tab {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid #e2e8f0; background: #fff; color: #334155;
            border-radius: 999px; padding: 8px 14px; font-weight: 700; text-decoration: none;
        }
        .pds-dm-branch-tab .badge { background: #f1f5f9; color: #64748b; }
        .pds-dm-branch-tab:hover { border-color: #fdba74; color: #c2410c; text-decoration: none; }
        .pds-dm-branch-tab.is-active {
            background: linear-gradient(135deg, #FE6F07, #ff8f3d);
            border-color: transparent; color: #fff;
        }
        .pds-dm-branch-tab.is-active .badge { background: rgba(255,255,255,.22); color: #fff; }
    </style>
    <div class="pds-dm-branch-tabs" role="tablist" aria-label="{{ __('message.branch') }}">
        @if($includeAll && $branchTabs->count() > 1)
            <a href="{{ route($routeName, array_filter($routeQuery, fn ($v) => $v !== null && $v !== '')) }}"
               class="pds-dm-branch-tab{{ $selectedBranchId === null || (int) $selectedBranchId === 0 ? ' is-active' : '' }}"
               role="tab">
                {{ $allLabel }}
                @if($allCount !== null)
                    <span class="badge badge-light">{{ (int) $allCount }}</span>
                @endif
            </a>
        @endif
        @foreach($branchTabs as $branchTab)
            @php
                $tabQuery = array_filter(
                    array_merge($routeQuery, [$paramName => $branchTab->id]),
                    fn ($v) => $v !== null && $v !== ''
                );
            @endphp
            <a href="{{ route($routeName, $tabQuery) }}"
               class="pds-dm-branch-tab{{ (int) $selectedBranchId === (int) $branchTab->id ? ' is-active' : '' }}"
               role="tab">
                {{ $branchTab->name }}
                <span class="badge badge-light">{{ (int) ($branchTabCounts[$branchTab->id] ?? 0) }}</span>
            </a>
        @endforeach
    </div>
@endif

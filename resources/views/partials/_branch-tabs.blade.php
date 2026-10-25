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
    {{-- Flex wrap + center: incomplete last row centers; padding keeps Myanmar glyphs from clipping. --}}
    <style id="pds-dm-branch-tabs-styles">
        body.pds-admin .pds-dm-branch-tabs-wrap {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 1rem 1.25rem 0.35rem !important;
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tabs {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            align-items: center !important;
            align-content: center !important;
            gap: 0.5rem !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0.75rem !important;
            box-sizing: border-box !important;
            border-radius: 18px !important;
            border: 1px solid rgba(var(--brand-rgb), 0.18) !important;
            background: linear-gradient(180deg, #fffaf5 0%, #fff3e8 100%) !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.75), 0 8px 20px rgba(15, 23, 42, 0.04) !important;
            overflow: visible !important;
        }
        body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.45rem !important;
            flex: 0 1 11rem !important;
            width: 11rem !important;
            max-width: calc(50% - 0.5rem) !important;
            min-width: 0 !important;
            min-height: 2.85rem !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0.55rem 0.7rem !important;
            border-radius: 999px !important;
            border: 1px solid transparent !important;
            background: rgba(255,255,255,0.35) !important;
            color: #44403c !important;
            box-sizing: border-box !important;
            text-decoration: none !important;
            font-weight: 750 !important;
            line-height: 1.45 !important;
            overflow: visible !important;
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease, color 0.15s ease;
        }
        body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab:hover {
            background: #fff !important;
            border-color: rgba(var(--brand-rgb), 0.25) !important;
            color: var(--site-color) !important;
        }
        body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab.is-active {
            background: #fff !important;
            border-color: rgba(var(--brand-rgb), 0.45) !important;
            color: var(--site-color) !important;
            box-shadow: 0 2px 10px rgba(var(--brand-rgb), 0.16) !important;
        }
        body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab__label {
            flex: 1 1 auto !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0.15rem 0 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
            text-align: center !important;
            line-height: 1.45 !important;
            font-family: 'Noto Sans Myanmar', 'Myanmar Text', 'Padauk', var(--admin-font), system-ui, sans-serif !important;
        }
        body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab__count {
            flex: 0 0 auto !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            align-self: center !important;
            min-width: 1.4rem !important;
            height: 1.4rem !important;
            padding: 0 0.4rem !important;
            margin: 0 !important;
            border-radius: 999px !important;
            background: rgba(28, 25, 23, 0.08) !important;
            color: #44403c !important;
            font-size: 0.7rem !important;
            font-weight: 800 !important;
            line-height: 1 !important;
        }
        body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab.is-active .pds-dm-branch-tab__count {
            background: var(--site-color) !important;
            color: #fff !important;
            box-shadow: 0 0 0 3px rgba(var(--brand-rgb), 0.14) !important;
        }
        @media (min-width: 1200px) {
            body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab {
                flex-basis: 12rem !important;
                width: 12rem !important;
                max-width: calc(25% - 0.5rem) !important;
            }
        }
        @media (max-width: 992px) {
            body.pds-admin .pds-dm-branch-tabs-wrap {
                padding: 0.85rem 0.85rem 0.25rem !important;
            }
            body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tabs {
                gap: 0.4rem !important;
                padding: 0.65rem !important;
            }
            body.pds-admin .pds-dm-branch-tabs-wrap .pds-dm-branch-tab {
                flex-basis: calc(50% - 0.4rem) !important;
                width: calc(50% - 0.4rem) !important;
                max-width: calc(50% - 0.4rem) !important;
            }
        }
    </style>
    <div class="pds-dm-branch-tabs-wrap">
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
    </div>
@endif

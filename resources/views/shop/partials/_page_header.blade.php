@php
    $backRoute = $backRoute ?? null;
    $addRoute = $addRoute ?? null;
    $addPermission = $addPermission ?? null;
    $addLabel = $addLabel ?? __('message.add');
@endphp
<div class="card-header pds-page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="pds-shop-page-head">
        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
        @if(!empty($subtitle))
            <p class="pds-shop-page-head__sub mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if($backRoute)
            <a href="{{ $backRoute }}" class="btn btn-sm btn-outline-primary pds-shop-btn-back">
                <i class="fas fa-arrow-left mr-1"></i> {{ __('message.back') }}
            </a>
        @endif
        @if($addRoute && (empty($addPermission) || auth()->user()->can($addPermission)))
            <a href="{{ route($addRoute) }}" class="btn btn-sm btn-primary">
                <i class="fa fa-plus-circle mr-1"></i> {{ $addLabel }}
            </a>
        @endif
    </div>
</div>

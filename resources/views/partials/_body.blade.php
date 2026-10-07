<div id="loading">
    @include('partials._body_loader')
</div>

{{-- Build menu (shared as MyNavBar) then show it in the top header. --}}
@include('partials._body_sidebar')

@php
    // Sidebar include builds the menu; re-bind for header nav.
    if (! isset($MyNavBar)) {
        $MyNavBar = $MenuList ?? \Menu::get('MenuList');
    }
@endphp

@include('partials._body_header')

<div id="remoteModelData" class="modal fade" role="dialog"></div>

<div
    class="content-page pds-content-shell"
    id="adminSpaContent"
    data-page="{{ optional(request()->route())->getName() ?? '' }}"
>
    {{ $slot }}
</div>

@include('partials._body_footer')

@include('partials._scripts')
@include('partials._dynamic_script')

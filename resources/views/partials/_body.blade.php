<div id="loading">
    @include('partials._body_loader')
</div>

@include('partials._body_sidebar')

@include('partials._body_header')

<div id="remoteModelData" class="modal fade" role="dialog"></div>

<div class="content-page pds-content-shell">
    {{ $slot }}
</div>

@include('partials._body_footer')

@include('partials._scripts')
@include('partials._dynamic_script')

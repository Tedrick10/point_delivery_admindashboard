<div id="loading">
    @include('frontand-partials._body_loader')
</div>
@include('frontand-partials._body_header')

<div id="remoteModelData" class="modal fade" role="dialog"></div>
@php
    $hideWebsiteFooter = request()->routeIs(['privacypolicy', 'termofservice']);
@endphp
<div class="main-page {{ $hideWebsiteFooter ? 'main-page--legal' : '' }}">
    {{ $slot }}
</div>

@unless($hideWebsiteFooter)
    @include('frontand-partials._body_footer')
@endunless

@include('frontand-partials._scripts')

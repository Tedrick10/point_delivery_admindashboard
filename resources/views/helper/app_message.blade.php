<script>
(function($) {
    "use strict";

    if (typeof Snackbar !== 'undefined' && typeof Snackbar.close === 'function') {
        Snackbar.close();
    }

    @if(Session::has('success') && Session::get('success'))
        Snackbar.show({
            text: @json(Session::get('success')),
            pos: 'bottom-center',
            showAction: false,
            duration: 4000
        });
    @endif

    @if(Session::has('error') && Session::get('error'))
        Snackbar.show({
            text: @json(Session::get('error')),
            pos: 'bottom-center',
            backgroundColor: '#dc3545',
            textColor: '#ffffff',
            showAction: false,
            duration: 4000
        });
    @endif

    @if(isset($errors) && $errors->any())
        @php $flashError = $errors->first(); @endphp
        @if($flashError)
        Snackbar.show({
            text: @json($flashError),
            pos: 'bottom-center',
            backgroundColor: '#dc3545',
            textColor: '#ffffff',
            showAction: false,
            duration: 4000
        });
        @endif
    @endif
})(jQuery);
</script>

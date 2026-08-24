<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ mighty_language_direction() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials._head')

    </head>
    <body class="pds-admin{{ (Auth::check() && !in_array(Auth::user()->user_type, ['client', 'delivery_man'], true)) ? ' pds-header-nav' : '' }}" id="app">
        <script>
            try {
                if (localStorage.getItem('dark') === 'true') {
                    document.body.classList.add('dark');
                }
            } catch (e) {}
        </script>

        @include('partials._body', ['slot' => $slot])

    </body>

</html>

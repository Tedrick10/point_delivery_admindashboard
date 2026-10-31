@php
    $asSuperAdmin = function_exists('isSuperAdmin') && isSuperAdmin(auth()->user());
@endphp

@if($asSuperAdmin)
    @extends('super-admin.layout')

    @section('title', $pageTitle ?? __('message.online_shop'))
    @section('page_title', $pageTitle ?? __('message.online_shop'))

    @section('content')
        @include('users.partials.show-body')
    @endsection
@else
    <x-master-layout>
        @include('users.partials.show-body')
    </x-master-layout>
@endif

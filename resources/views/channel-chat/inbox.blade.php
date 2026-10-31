@php
    $asSuperAdmin = function_exists('isSuperAdmin') && isSuperAdmin(auth()->user());
@endphp

@if($asSuperAdmin)
    @extends('super-admin.layout')

    @section('title', $pageTitle)
    @section('page_title', $pageTitle)

    @section('content')
        @include('channel-chat.partials.inbox-body')
    @endsection

    @section('bottom_script')
        @include('channel-chat.partials.inbox-script')
    @endsection
@else
    <x-master-layout :assets="$assets ?? []">
        @include('channel-chat.partials.inbox-body')

        @section('bottom_script')
            @include('channel-chat.partials.inbox-script')
        @endsection
    </x-master-layout>
@endif

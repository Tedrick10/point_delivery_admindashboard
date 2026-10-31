@php
    $asSuperAdmin = function_exists('isSuperAdmin') && isSuperAdmin(auth()->user());
@endphp

@if($asSuperAdmin)
    @extends('super-admin.layout')

    @section('title', $pageTitle)
    @section('page_title', $pageTitle)

    @section('content')
        @include('customer-suport.partials.show-body')
    @endsection
@else
    <x-master-layout>
        @include('customer-suport.partials.show-body')
    </x-master-layout>
@endif

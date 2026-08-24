@extends('super-admin.layout')

@section('title', __('message.sa_all_screens'))
@section('page_title', __('message.sa_all_screens'))
@section('page_sub', __('message.sa_choose_module') . ' · ' . $monthLabel)

@section('content')
<div class="sa-modules">
    <header class="sa-modules__intro">
        <p>{{ __('message.sa_modules_intro') }}</p>
    </header>
    <div class="sa-modules__grid">
        @foreach($screens as $key => $screen)
            <a href="{{ route('super-admin.screens.show', $key) }}" class="sa-module-card">
                <span class="sa-module-card__icon"><i class="fas {{ $screen['icon'] }}"></i></span>
                <div class="sa-module-card__body">
                    <strong>{{ __('message.'.($screen['title_key'] ?? '')) }}</strong>
                    <span>{{ __('message.'.($screen['subtitle_key'] ?? '')) }}</span>
                </div>
                <span class="sa-module-card__arrow" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </a>
        @endforeach
    </div>
</div>
@endsection

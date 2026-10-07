@extends('super-admin.layout')

@section('title', __('message.sa_all_screens'))
@section('page_title', __('message.sa_all_screens'))
@section('page_sub', __('message.sa_choose_module') . ' · ' . $monthLabel)

@section('content')
@php
    $groups = [
        ['label' => __('message.sa_operations'), 'keys' => ['delivery-route']],
        ['label' => __('message.sa_nav_finance'), 'keys' => ['kyo-shin', 'rider-remit', 'expense-summary']],
        ['label' => __('message.sa_nav_people'), 'keys' => ['account-creation', 'roles-permissions', 'office-salary', 'rider-salary', 'late-fine', 'welcome-promotion']],
        ['label' => __('message.sa_nav_settings'), 'keys' => ['general-setting', 'company-contact', 'api-server-setting', 'app-store-update', 'app-copy']],
    ];
@endphp
<div class="sa-modules">
    <header class="sa-modules__intro">
        <p>{{ __('message.sa_modules_intro') }}</p>
    </header>
    @foreach($groups as $group)
        @php
            $cards = collect($group['keys'])->map(fn ($key) => [$key, $screens[$key] ?? null])->filter(fn ($pair) => $pair[1]);
        @endphp
        @continue($cards->isEmpty())
        <section class="sa-modules__section">
            <h2 class="sa-modules__section-title">{{ $group['label'] }}</h2>
            <div class="sa-modules__grid">
                @foreach($cards as [$key, $screen])
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
        </section>
    @endforeach
</div>
@endsection

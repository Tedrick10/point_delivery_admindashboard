@extends('super-admin.layout')

@php
    $screenTitle = __('message.'.($screen['title_key'] ?? 'sa_screen_dispatch'));
    $screenSub = __('message.'.($screen['subtitle_key'] ?? 'sa_screen_dispatch_sub'));
@endphp

@section('title', $screenTitle)
@section('page_title', $screenTitle)
@section('page_sub', $monthLabel)

@section('content')
@php
    $money = fn ($n) => is_numeric($n) ? number_format((float) $n, 0) . ' Ks' : $n;
    $primaryLink = $screen['links'][0] ?? null;
    $primaryHref = $primaryLink ? route($primaryLink['route'], $primaryLink['params'] ?? []) : '#';
    $primaryLabel = $primaryLink ? __('message.'.($primaryLink['label_key'] ?? '')) : '';
    $branchCols = !empty($branchRows) ? array_keys($branchRows[0]['cols'] ?? []) : [];
@endphp

<div class="sa-module-page">
    <a href="{{ route('super-admin.screens.hub') }}" class="sa-module-page__back">
        <i class="fas fa-arrow-left"></i> {{ __('message.sa_all_screens') }}
    </a>

    <header class="sa-module-hero">
        <div class="sa-module-hero__icon"><i class="fas {{ $screen['icon'] }}"></i></div>
        <div class="sa-module-hero__copy">
            <h2>{{ $screenTitle }}</h2>
            <p>{{ $screenSub }}</p>
        </div>
        @if($primaryLink)
            <a href="{{ $primaryHref }}" class="sa-module-hero__btn" target="_blank" rel="noopener">
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <span>{{ __('message.sa_open') }} {{ $primaryLabel }}</span>
            </a>
        @endif
    </header>

    @if(count($screen['links'] ?? []) > 1)
        <div class="sa-module-links">
            @foreach($screen['links'] as $link)
                <a href="{{ route($link['route'], $link['params'] ?? []) }}" class="sa-module-links__item" target="_blank" rel="noopener">
                    {{ __('message.'.($link['label_key'] ?? '')) }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="sa-module-page__grid">
        <section class="sa-module-panel">
            <header class="sa-module-panel__head">
                <h3>{{ __('message.sa_tab_overview') }}</h3>
                <span>{{ $monthLabel }}</span>
            </header>
            <div class="sa-module-metrics">
                @foreach($metrics as $m)
                    <div class="sa-module-metric">
                        <span class="sa-module-metric__label">{{ $m['label'] }}</span>
                        <strong class="sa-module-metric__value">
                            @if(!empty($m['raw']))
                                {{ $m['value'] }}
                            @elseif(!empty($m['money']))
                                {{ $money($m['value']) }}
                            @else
                                {{ number_format((float) $m['value']) }}
                            @endif
                        </strong>
                    </div>
                @endforeach
            </div>
        </section>

        @if(!empty($branchRows) && !empty($branchCols))
            <section class="sa-module-panel sa-module-panel--branch">
                <header class="sa-module-panel__head">
                    <h3>{{ __('message.sa_by_branch') }}</h3>
                    <span>{{ count($branchRows) }} {{ __('message.sa_branches') }}</span>
                </header>
                <div class="sa-module-table-wrap">
                    <table class="sa-module-table">
                        <thead>
                            <tr>
                                <th>{{ __('message.branch') }}</th>
                                @foreach($branchCols as $col)
                                    <th>{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchRows as $row)
                                <tr>
                                    <td><strong>{{ $row['name'] }}</strong></td>
                                    @foreach($branchCols as $col)
                                        <td>{{ $row['cols'][$col] ?? '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

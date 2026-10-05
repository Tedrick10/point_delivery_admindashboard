@extends('super-admin.layout')

@section('title', __('message.dashboard'))
@section('page_title', __('message.sa_network_dashboard'))
@section('page_sub', __('message.sa_all_branches') . ' · ' . $monthLabel)

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n);
    $money = fn ($n) => number_format((float) $n, 0) . ' Ks';
    $s = $stats;
    $statusLabel = function (string $status): string {
        $key = 'message.sa_status_'.$status;
        $translated = __($key);
        return $translated === $key ? str_replace('_', ' ', $status) : $translated;
    };
    $statusTone = function (string $status): string {
        $slug = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '_', $status)), '_');
        return match ($slug) {
            'assigned' => 'blue',
            'pending' => 'amber',
            'delivered' => 'teal',
            'completed' => 'green',
            'finished' => 'violet',
            'returned', 'return' => 'rose',
            default => 'orange',
        };
    };
    $initials = function (string $name): string {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $chars = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $chars[] = mb_strtoupper(mb_substr($part, 0, 1));
            if (count($chars) >= 2) {
                break;
            }
        }

        return implode('', $chars) ?: '?';
    };
@endphp

<div class="sa-home">
    <section class="sa-kpi">
        @foreach([
            ['label' => __('message.sa_items'), 'value' => $fmt($s['items_month']), 'hint' => $fmt($s['items_today']).' '.__('message.sa_today_lc')],
            ['label' => __('message.deli_amount'), 'value' => $money($s['deli_month']), 'hint' => $monthLabel],
            ['label' => __('message.sa_income'), 'value' => $money($s['summary_income_month']), 'hint' => $monthLabel],
            ['label' => __('message.sa_akos_given'), 'value' => $money($s['summary_ako_month']), 'hint' => $monthLabel],
        ] as $card)
            <article>
                <p>{{ $card['label'] }}</p>
                <strong>{{ $card['value'] }}</strong>
                <span>{{ $card['hint'] }}</span>
            </article>
        @endforeach
    </section>

    <section class="sa-kpi sa-kpi--people">
        @foreach([
            ['label' => __('message.sa_metric_branches'), 'value' => $fmt($s['branches']), 'hint' => $fmt($s['active_branches']).' '.__('message.sa_active')],
            ['label' => __('message.sa_metric_branch_admins'), 'value' => $fmt($s['branch_admins']), 'hint' => $fmt($s['branches_without_admin']).' '.__('message.sa_missing')],
            ['label' => __('message.sa_metric_riders'), 'value' => $fmt($s['riders']), 'hint' => $fmt($s['riders_active']).' '.__('message.sa_active')],
            ['label' => __('message.sa_metric_os_clients'), 'value' => $fmt($s['clients']), 'hint' => $fmt($s['clients_vip']).' '.__('message.sa_vip')],
        ] as $card)
            <article>
                <p>{{ $card['label'] }}</p>
                <strong>{{ $card['value'] }}</strong>
                <span>{{ $card['hint'] }}</span>
            </article>
        @endforeach
    </section>

    <div class="sa-home-split">
        <section class="sa-home-card sa-home-card--chart">
            <header>
                <h2>{{ __('message.sa_activity') }}</h2>
                <div class="sa-home-legend">
                    <span><i></i> {{ __('message.sa_created') }}</span>
                    <span><b></b> {{ __('message.delivered') }}</span>
                </div>
            </header>
            @php $maxBar = max(1, collect($last7)->max(fn ($d) => max((int) $d['created'], (int) $d['delivered']))); @endphp
            <div class="sa-home-bars">
                @foreach($last7 as $day)
                    @php
                        $created = (int) $day['created'];
                        $delivered = (int) $day['delivered'];
                        $hCreated = $created > 0 ? max(8, (int) round(($created / $maxBar) * 100)) : 0;
                        $hDelivered = $delivered > 0 ? max(8, (int) round(($delivered / $maxBar) * 100)) : 0;
                    @endphp
                    <div class="sa-home-bars__col">
                        <div class="sa-home-bars__pair">
                            <i class="{{ $created ? '' : 'is-empty' }}" style="height: {{ $created ? $hCreated.'%' : '4px' }}"></i>
                            <b class="{{ $delivered ? '' : 'is-empty' }}" style="height: {{ $delivered ? $hDelivered.'%' : '4px' }}"></b>
                        </div>
                        <strong>{{ $created ?: '' }}</strong>
                        <em>{{ $day['label'] }}</em>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sa-home-card">
            <header>
                <h2>{{ __('message.status') }}</h2>
            </header>
            @php $maxStatus = max(1, (int) collect($s['status_counts'] ?? [])->max()); @endphp
            <ul class="sa-home-status">
                @foreach(($s['status_counts'] ?? []) as $status => $count)
                    @php $tone = $statusTone((string) $status); @endphp
                    <li class="is-{{ $tone }}">
                        <div>
                            <span><i></i> {{ $statusLabel((string) $status) }}</span>
                            <strong>{{ $fmt($count) }}</strong>
                        </div>
                        <div class="sa-home-status__bar"><i style="width: {{ max(12, (int) round(((int) $count / $maxStatus) * 100)) }}%"></i></div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <section class="sa-home-card sa-home-card--table">
        <header>
            <div>
                <h2>{{ __('message.sa_branches_title') }}</h2>
                <p>{{ $monthLabel }} · {{ $fmt(count($byBranch)) }} {{ __('message.sa_metric_branches') }}</p>
            </div>
            <a href="{{ route('super-admin.branch-admins.index') }}" class="sa-btn sa-btn-primary">{{ __('message.sa_manage_admins') }}</a>
        </header>
        <div class="sa-home-table-wrap">
            <table class="sa-home-table">
                <thead>
                    <tr>
                        <th>{{ __('message.branch') }}</th>
                        <th>{{ __('message.sa_admin') }}</th>
                        <th class="sa-num">{{ __('message.sa_items') }}</th>
                        <th class="sa-num">{{ __('message.active') }}</th>
                        <th class="sa-num sa-th-keep">{{ __('message.deli_amount') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byBranch as $row)
                        <tr class="{{ empty($row['admin']) ? 'is-open' : '' }}">
                            <td>
                                <div class="sa-person">
                                    <span class="sa-avatar">{{ $initials((string) $row['name']) }}</span>
                                    <div>
                                        <strong>{{ $row['name'] }}</strong>
                                        @if(!empty($row['settlement_label']))
                                            <small>{{ $row['settlement_label'] }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($row['admin'])
                                    <div class="sa-person sa-person--plain">
                                        <span class="sa-avatar sa-avatar--admin">{{ $initials((string) $row['admin']['name']) }}</span>
                                        <strong>{{ $row['admin']['name'] }}</strong>
                                    </div>
                                @else
                                    <span class="sa-badge sa-badge-warn">{{ __('message.sa_unassigned') }}</span>
                                @endif
                            </td>
                            <td class="sa-num">{{ $fmt($row['items_month']) }}</td>
                            <td class="sa-num">{{ $fmt($row['in_progress']) }}</td>
                            <td class="sa-num sa-num--money">{{ $money($row['deli_month']) }}</td>
                            <td class="sa-table-actions">
                                @unless($row['admin'])
                                    <a href="{{ route('super-admin.branch-admins.create', ['branch_id' => $row['id']]) }}" class="sa-btn sa-btn-primary sa-btn-sm">{{ __('message.sa_create_admin') }}</a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sa-empty">{{ __('message.sa_no_branches') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

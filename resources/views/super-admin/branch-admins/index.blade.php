@extends('super-admin.layout')

@section('title', __('message.sa_branch_admins'))
@section('page_title', __('message.sa_branch_admins'))
@section('page_sub', __('message.sa_one_admin_per_branch'))

@section('content')
@php
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
<div class="sa-module-panel sa-branch-admins-board">
    <header class="sa-module-panel__head">
        <div>
            <h3>{{ __('message.sa_accounts_by_branch') }}</h3>
            <span>{{ $branches->count() }} {{ __('message.sa_metric_branches') }}</span>
        </div>
        <a href="{{ route('super-admin.branch-admins.create') }}" class="sa-btn sa-btn-primary">
            <i class="fas fa-plus"></i> {{ __('message.sa_new_branch_admin') }}
        </a>
    </header>
    <div class="sa-module-table-wrap">
        <table class="sa-module-table sa-people-table">
            <thead>
                <tr>
                    <th>{{ __('message.branch') }}</th>
                    <th>{{ __('message.sa_admin') }}</th>
                    <th>{{ __('message.email') }}</th>
                    <th>{{ __('message.phone') }}</th>
                    <th>{{ __('message.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    @php $admin = $adminsByBranch->get($branch->id); @endphp
                    <tr class="{{ $admin ? '' : 'is-open' }}">
                        <td>
                            <div class="sa-person">
                                <span class="sa-avatar">{{ $initials((string) $branch->name) }}</span>
                                <strong>{{ $branch->name }}</strong>
                            </div>
                        </td>
                        <td>
                            @if($admin)
                                <div class="sa-person sa-person--plain">
                                    <span class="sa-avatar sa-avatar--admin">{{ $initials((string) $admin->name) }}</span>
                                    <strong>{{ $admin->name }}</strong>
                                </div>
                            @else
                                <span class="sa-badge sa-badge-warn">{{ __('message.sa_unassigned') }}</span>
                            @endif
                        </td>
                        <td class="sa-muted">{{ $admin->email ?? '—' }}</td>
                        <td class="sa-muted">{{ $admin->contact_number ?? '—' }}</td>
                        <td>
                            @if($admin)
                                @if((int) $admin->status === 1)
                                    <span class="sa-badge sa-badge-ok">{{ __('message.active') }}</span>
                                @else
                                    <span class="sa-badge sa-badge-off">{{ __('message.inactive') }}</span>
                                @endif
                            @else
                                <span class="sa-muted">—</span>
                            @endif
                        </td>
                        <td class="sa-table-actions">
                            @if($admin)
                                <a href="{{ route('super-admin.branch-admins.edit', $admin->id) }}" class="sa-btn sa-btn-ghost sa-btn-sm">{{ __('message.sa_edit') }}</a>
                                <form action="{{ route('super-admin.branch-admins.destroy', $admin->id) }}" method="POST" onsubmit="return confirm(@json(__('message.sa_remove_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sa-btn sa-btn-danger sa-btn-sm">{{ __('message.sa_remove') }}</button>
                                </form>
                            @else
                                <a href="{{ route('super-admin.branch-admins.create', ['branch_id' => $branch->id]) }}" class="sa-btn sa-btn-primary sa-btn-sm">{{ __('message.sa_create_admin') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="sa-empty">{{ __('message.sa_no_regional_branches') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

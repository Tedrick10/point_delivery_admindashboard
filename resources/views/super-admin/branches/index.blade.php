@extends('super-admin.layout')

@section('title', __('message.sa_branches_title'))
@section('page_title', __('message.sa_branches_title'))
@section('page_sub', __('message.sa_branches_manage_sub'))

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
            <h3>{{ __('message.sa_branches_title') }}</h3>
            <span>{{ $branches->count() }} {{ __('message.sa_metric_branches') }}</span>
        </div>
        <a href="{{ route('super-admin.branches.create') }}" class="sa-btn sa-btn-primary">
            <i class="fas fa-plus"></i> {{ __('message.sa_new_branch') }}
        </a>
    </header>
    <div class="sa-module-table-wrap">
        <table class="sa-module-table sa-people-table">
            <thead>
                <tr>
                    <th>{{ __('message.branch') }}</th>
                    <th>{{ __('message.code') }}</th>
                    <th>{{ __('message.city_name') }}</th>
                    <th>{{ __('message.phone') }}</th>
                    <th>{{ __('message.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td>
                            <div class="sa-person">
                                <span class="sa-avatar">{{ $initials((string) $branch->name) }}</span>
                                <strong>{{ $branch->name }}</strong>
                            </div>
                        </td>
                        <td class="sa-muted">{{ $branch->code ?: '—' }}</td>
                        <td class="sa-muted">{{ $branch->city_name ?: '—' }}</td>
                        <td class="sa-muted">{{ $branch->phone ?: '—' }}</td>
                        <td>
                            @if((int) $branch->status === 1)
                                <span class="sa-badge sa-badge-ok">{{ __('message.active') }}</span>
                            @else
                                <span class="sa-badge sa-badge-off">{{ __('message.inactive') }}</span>
                            @endif
                        </td>
                        <td class="sa-table-actions">
                            <a href="{{ route('super-admin.branches.edit', $branch->id) }}" class="sa-btn sa-btn-ghost sa-btn-sm">{{ __('message.sa_edit') }}</a>
                            <a href="{{ route('super-admin.branch-admins.create', ['branch_id' => $branch->id]) }}" class="sa-btn sa-btn-primary sa-btn-sm">{{ __('message.sa_create_admin') }}</a>
                            <form action="{{ route('super-admin.branches.destroy', $branch->id) }}" method="POST" onsubmit="return confirm(@json(__('message.delete_form', ['form' => $branch->name])));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sa-btn sa-btn-danger sa-btn-sm">{{ __('message.delete') }}</button>
                            </form>
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
</div>
@endsection

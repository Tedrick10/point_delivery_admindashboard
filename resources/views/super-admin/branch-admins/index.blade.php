@extends('super-admin.layout')

@section('title', __('message.sa_branch_admins'))
@section('page_title', __('message.sa_branch_admins'))
@section('page_sub', __('message.sa_one_admin_per_branch'))

@section('content')
<div class="sa-card mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="mb-0">{{ __('message.sa_accounts_by_branch') }}</h2>
        <a href="{{ route('super-admin.branch-admins.create') }}" class="sa-btn sa-btn-primary">
            <i class="fas fa-plus"></i> {{ __('message.sa_new_branch_admin') }}
        </a>
    </div>
    <div class="table-responsive mt-3">
        <table class="sa-table">
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
                    <tr>
                        <td><strong>{{ $branch->name }}</strong></td>
                        <td>
                            @if($admin)
                                {{ $admin->name }}
                            @else
                                <span class="sa-badge sa-badge-warn">{{ __('message.sa_unassigned') }}</span>
                            @endif
                        </td>
                        <td>{{ $admin->email ?? '—' }}</td>
                        <td>{{ $admin->contact_number ?? '—' }}</td>
                        <td>
                            @if($admin)
                                @if((int) $admin->status === 1)
                                    <span class="sa-badge sa-badge-ok">{{ __('message.active') }}</span>
                                @else
                                    <span class="sa-badge sa-badge-off">{{ __('message.inactive') }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-right">
                            @if($admin)
                                <a href="{{ route('super-admin.branch-admins.edit', $admin->id) }}" class="sa-btn sa-btn-ghost">{{ __('message.sa_edit') }}</a>
                                <form action="{{ route('super-admin.branch-admins.destroy', $admin->id) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('message.sa_remove_confirm')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sa-btn sa-btn-danger">{{ __('message.sa_remove') }}</button>
                                </form>
                            @else
                                <a href="{{ route('super-admin.branch-admins.create', ['branch_id' => $branch->id]) }}" class="sa-btn sa-btn-primary">{{ __('message.sa_create_admin') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('message.sa_no_regional_branches') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

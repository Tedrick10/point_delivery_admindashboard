<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height pds-page-card">
                    <div class="card-header d-flex justify-content-between align-items-center pds-page-header">
                        <div class="header-title">
                            <h4 class="card-title mb-0 pds-page-title">{{ $pageTitle }}</h4>
                            <p class="text-muted mb-0 mt-1" style="font-size:13px;">{{ __('message.sa_one_admin_per_branch') }}</p>
                        </div>
                        <div class="card-header-toolbar pds-page-actions">
                            @if($canAdd ?? false)
                                <a href="{{ route('branch.create') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-plus-circle"></i> {{ __('message.sa_new_branch') }}
                                </a>
                                <a href="{{ route('branch-admins.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-plus-circle"></i> {{ __('message.sa_new_branch_admin') }}
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body pds-page-body">
                        <div class="pds-table-shell table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('message.branch') }}</th>
                                        <th>{{ __('message.sa_admin') }}</th>
                                        <th>{{ __('message.email') }}</th>
                                        <th>{{ __('message.phone') }}</th>
                                        <th>{{ __('message.status') }}</th>
                                        <th style="width:180px"></th>
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
                                                    <span class="badge badge-warning">{{ __('message.sa_unassigned') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $admin->email ?? '—' }}</td>
                                            <td>{{ $admin->contact_number ?? '—' }}</td>
                                            <td>
                                                @if($admin)
                                                    @if((int) $admin->status === 1)
                                                        <span class="badge badge-success">{{ __('message.active') }}</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ __('message.inactive') }}</span>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if($admin)
                                                    @if($canEdit ?? false)
                                                        <a href="{{ route('branch-admins.edit', $admin->id) }}" class="btn btn-sm btn-outline-primary">{{ __('message.edit') }}</a>
                                                    @endif
                                                    @if($canDelete ?? false)
                                                        <form action="{{ route('branch-admins.destroy', $admin->id) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('message.sa_remove_confirm')));">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('message.delete') }}</button>
                                                        </form>
                                                    @endif
                                                @elseif($canAdd ?? false)
                                                    <a href="{{ route('branch-admins.create', ['branch_id' => $branch->id]) }}" class="btn btn-sm btn-primary">{{ __('message.sa_create_admin') }}</a>
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
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

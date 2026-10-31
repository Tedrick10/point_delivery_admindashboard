<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <h4 class="mb-1">{{ $pageTitle }}</h4>
                <p class="text-muted mb-0">{{ __('message.point_salary_requests_hint') }}</p>
            </div>
            <a href="{{ route('point-system.index') }}" class="btn btn-outline-secondary">{{ __('message.point_system') }}</a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-control-label">{{ __('message.status') }}</label>
                        <select name="status" class="form-control">
                            <option value="all" @selected($status === 'all')>{{ __('message.all') }}</option>
                            <option value="pending" @selected($status === 'pending')>{{ __('message.point_status_pending_admin') }}</option>
                            <option value="approved" @selected($status === 'approved')>{{ __('message.point_status_confirmed') }}</option>
                            <option value="rejected" @selected($status === 'rejected')>{{ __('message.point_status_rejected') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-block" type="submit">{{ __('message.filter') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0 text-center">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('message.user') }}</th>
                            <th>{{ __('message.point_amount') }}</th>
                            <th>MMK</th>
                            <th>{{ __('message.point_salary_period') }}</th>
                            <th>{{ __('message.status') }}</th>
                            <th>{{ __('message.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td class="text-left">
                                    {{ optional($row->user)->name }}
                                    <div class="small text-muted">{{ optional($row->staff)->staff_group ?: optional($row->user)->user_type }}</div>
                                </td>
                                <td>{{ number_format((float) $row->points, 2) }}</td>
                                <td>{{ number_format((float) $row->amount, 0) }}</td>
                                <td>{{ $row->salary_period }}</td>
                                <td>
                                    <span class="badge badge-{{ $row->status === 'approved' ? 'success' : ($row->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $row->status }}
                                    </span>
                                </td>
                                <td>
                                    @if($canConfirm && $row->status === 'pending')
                                        <form method="POST" action="{{ route('point-system.salary.approve', $row->id) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success">{{ __('message.point_action_approve') }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('point-system.salary.reject', $row->id) }}" class="d-inline"
                                              onsubmit="return confirm(@json(__('message.are_you_sure') ?? 'Are you sure?'))">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">{{ __('message.point_action_reject') }}</button>
                                        </form>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted py-4">{{ __('message.no_record_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())
                <div class="card-footer">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
</x-master-layout>

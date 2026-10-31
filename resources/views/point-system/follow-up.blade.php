<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <h4 class="mb-1">{{ $pageTitle }}</h4>
                <p class="text-muted mb-0">{{ __('message.point_follow_up_hint') }}</p>
            </div>
            <a href="{{ route('point-system.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-coins"></i> {{ __('message.point_system') }}
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('point-system.follow-up') }}" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-control-label">{{ __('message.status') }}</label>
                        <select name="status" class="form-control">
                            <option value="all" @selected($status === 'all')>{{ __('message.all') }}</option>
                            <option value="pending_admin" @selected($status === 'pending_admin')>{{ __('message.point_status_pending_admin') }}</option>
                            <option value="pending_super_admin" @selected($status === 'pending_super_admin')>{{ __('message.point_status_pending_super_admin') }}</option>
                            <option value="confirmed" @selected($status === 'confirmed')>{{ __('message.point_status_confirmed') }}</option>
                            <option value="rejected" @selected($status === 'rejected')>{{ __('message.point_status_rejected') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-control-label">{{ __('message.search') }}</label>
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Order ID / name">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('message.filter') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 text-center">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('message.order_id') }}</th>
                                <th>{{ __('message.user') }}</th>
                                <th>{{ __('message.point_amount') }}</th>
                                <th>{{ __('message.status') }}</th>
                                <th>{{ __('message.date') }}</th>
                                <th>{{ __('message.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>
                                        @if(\Illuminate\Support\Facades\Route::has('order-view.show'))
                                            <a href="{{ route('order-view.show', $row->order_id) }}">#{{ $row->order_id }}</a>
                                        @else
                                            #{{ $row->order_id }}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        {{ optional($row->user)->name }}
                                        <div class="small text-muted">{{ optional($row->user)->user_type }}</div>
                                    </td>
                                    <td>{{ number_format((float) $row->points, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $row->status === 'confirmed' ? 'success' : ($row->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ __('message.point_status_'.$row->status) }}
                                        </span>
                                    </td>
                                    <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('point-system.payment.show', $row->id) }}" class="btn btn-sm btn-outline-primary">
                                            {{ __('message.detail') }}
                                        </a>
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
            </div>
            @if($rows->hasPages())
                <div class="card-footer">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
</x-master-layout>

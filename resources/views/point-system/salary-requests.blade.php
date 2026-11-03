<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <h4 class="mb-1">{{ $pageTitle }}</h4>
                <p class="text-muted mb-0">{{ __('message.point_salary_requests_hint') }}</p>
            </div>
            <a href="{{ route('point-system.index') }}" class="btn btn-outline-secondary">{{ __('message.point_system') }}</a>
        </div>

        @php
            $kind = $kind ?? 'os';
            $kindCounts = $kindCounts ?? ['os' => 0, 'staff' => 0, 'rider' => 0];
        @endphp
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach(['os' => 'OS', 'staff' => 'Staff', 'rider' => 'Rider'] as $key => $label)
                        <a href="{{ request()->fullUrlWithQuery(['kind' => $key, 'page' => null]) }}"
                           class="btn btn-sm {{ ($kind ?? 'os') === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $label }}
                            <span class="badge badge-light ml-1">{{ (int) ($kindCounts[$key] ?? 0) }}</span>
                        </a>
                    @endforeach
                </div>
                <form method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="kind" value="{{ $kind }}">
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
                                    <div class="small text-muted">
                                        @if(($row->request_kind ?? '') === 'client')
                                            OS
                                        @else
                                            {{ optional($row->staff)->staff_group ?: optional($row->user)->user_type }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ number_format((float) $row->amount, 0) }} MMK
                                    @if(($row->request_kind ?? '') === 'client')
                                        <div class="small text-muted">
                                            {{ __('message.point_bonus_os_pay_settled') }}:
                                            {{ number_format((float) ($row->os_pay_settled_amount ?? 0), 0) }}
                                            · {{ __('message.point_bonus_remaining') }}:
                                            {{ number_format((float) ($row->points_remaining ?? max(0, (float) $row->points - (float) ($row->os_pay_settled_amount ?? 0))), 0) }}
                                        </div>
                                        @if(is_array($row->os_pay_settled_details) && count($row->os_pay_settled_details) > 0)
                                            <div class="small text-success mt-1">
                                                @foreach($row->os_pay_settled_details as $detail)
                                                    #{{ (int) ($detail['item_id'] ?? 0) }} {{ $detail['customer'] ?? '-' }}
                                                    ({{ number_format((float) ($detail['settled'] ?? 0), 0) }})@if(! $loop->last), @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $row->salary_period }}</td>
                                <td>
                                    @php
                                        $statusLabel = match ((string) $row->status) {
                                            'approved' => __('message.point_status_confirmed'),
                                            'rejected' => __('message.point_status_rejected'),
                                            default => __('message.point_status_pending'),
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $row->status === 'approved' ? 'success' : ($row->status === 'rejected' ? 'danger' : 'warning') }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>
                                    @if($canConfirm && $row->status === 'pending')
                                        <form method="POST" action="{{ route('point-system.salary.approve', $row->id) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success">{{ __('message.point_salary_action_confirm') }}</button>
                                        </form>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted py-4">{{ __('message.no_record_found') }}</td>
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

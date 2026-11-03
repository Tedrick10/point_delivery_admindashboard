<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <h4 class="mb-1">{{ $pageTitle }}</h4>
                <p class="text-muted mb-0">{{ __('message.point_system_hint') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('point-system.follow-up') }}" class="btn btn-outline-primary">
                    <i class="fas fa-clipboard-list"></i>
                    {{ __('message.point_system_follow_up') }}
                    @if(($pendingFollowUps ?? 0) > 0)
                        <span class="badge badge-warning">{{ $pendingFollowUps }}</span>
                    @endif
                </a>
                <a href="{{ route('point-system.salary-requests') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-wallet"></i>
                    {{ __('message.point_salary_requests') }}
                    @if(($pendingSalary ?? 0) > 0)
                        <span class="badge badge-warning">{{ $pendingSalary }}</span>
                    @endif
                </a>
            </div>
        </div>

        <div class="row">
            @if($canGive)
                <div class="col-lg-4 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('message.point_give_title') }}</h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">{{ __('message.point_give_hint') }}</p>
                            {!! html()->form('POST', route('point-system.credit'))->open() !!}
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('message.point_recipient') }} <span class="text-danger">*</span></label>
                                    <select name="user_id" id="pointRecipient" class="form-control select2js" required>
                                        <option value="">{{ __('message.select') }}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('message.point_amount') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required placeholder="100">
                                </div>
                                <div class="form-group">
                                    <label class="form-control-label">{{ __('message.description') }}</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="{{ __('message.point_admin_credit_default') }}"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-plus-circle"></i> {{ __('message.point_give_submit') }}
                                </button>
                            {!! html()->form()->close() !!}
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('message.point_recent_credits') }}</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ __('message.user') }}</th>
                                            <th>{{ __('message.point_amount') }}</th>
                                            <th>{{ __('message.date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentCredits as $row)
                                            <tr>
                                                <td>
                                                    #{{ $row->user_id }} {{ optional($row->user)->name }}
                                                    <div class="small text-muted">{{ optional($row->user)->user_type }}</div>
                                                </td>
                                                <td>+{{ number_format((float) $row->amount, 2) }}</td>
                                                <td>{{ optional($row->created_at)->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">{{ __('message.no_record_found') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="{{ $canGive ? 'col-lg-8' : 'col-12' }} mb-3">
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0">{{ __('message.point_balances') }}</h5>
                        <form method="GET" action="{{ route('point-system.index') }}" class="d-flex flex-wrap gap-2">
                            <select name="recipient_type" class="form-control form-control-sm">
                                <option value="all" @selected($recipientType === 'all')>{{ __('message.all') }}</option>
                                <option value="client" @selected($recipientType === 'client')>{{ __('message.online_shop') }} / User</option>
                                <option value="delivery_man" @selected($recipientType === 'delivery_man')>{{ __('message.delivery_man') }}</option>
                                <option value="staff" @selected($recipientType === 'staff')>Staff</option>
                            </select>
                            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="{{ __('message.search') }}">
                            <button class="btn btn-sm btn-primary" type="submit">{{ __('message.filter') }}</button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>{{ __('message.name') }}</th>
                                        <th>{{ __('message.type') }}</th>
                                        <th>{{ __('message.point_balance') }}</th>
                                        <th>{{ __('message.updated_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($wallets as $wallet)
                                        <tr>
                                            <td>{{ $wallet->user_id }}</td>
                                            <td class="text-left">
                                                {{ optional($wallet->user)->name }}
                                                <div class="small text-muted">{{ optional($wallet->user)->contact_number }}</div>
                                            </td>
                                            <td>{{ optional($wallet->user)->user_type }}</td>
                                            <td><strong>{{ number_format((float) $wallet->balance, 0) }} MMK</strong></td>
                                            <td>{{ optional($wallet->updated_at)->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted">{{ __('message.no_record_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($wallets->hasPages())
                        <div class="card-footer">{{ $wallets->links() }}</div>
                    @endif
                </div>

                @if(($pendingSalary ?? 0) > 0)
                    <div class="alert alert-info mt-3 mb-0">
                        {{ __('message.point_pending_salary_count', ['count' => $pendingSalary]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($canGive)
        @push('scripts')
            <script>
                (function () {
                    var $el = $('#pointRecipient');
                    if (!$el.length || !$.fn.select2) return;
                    $el.select2({
                        width: '100%',
                        placeholder: @json(__('message.select')),
                        allowClear: true,
                        ajax: {
                            url: @json(route('point-system.search-users')),
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return { q: params.term || '', recipient_type: 'all' };
                            },
                            processResults: function (res) {
                                return {
                                    results: (res.data || []).map(function (row) {
                                        return { id: row.id, text: row.label };
                                    })
                                };
                            }
                        }
                    });
                })();
            </script>
        @endpush
    @endif
</x-master-layout>

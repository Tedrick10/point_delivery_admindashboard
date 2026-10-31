<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <h4 class="mb-1">{{ $pageTitle }} #{{ $payment->id }}</h4>
                <p class="text-muted mb-0">{{ __('message.point_follow_up_hint') }}</p>
            </div>
            <a href="{{ route('point-system.follow-up') }}" class="btn btn-outline-secondary">{{ __('message.back') }}</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('message.order_id') }}</dt>
                            <dd class="col-sm-8">
                                @if(\Illuminate\Support\Facades\Route::has('order-view.show'))
                                    <a href="{{ route('order-view.show', $payment->order_id) }}">#{{ $payment->order_id }}</a>
                                @else
                                    #{{ $payment->order_id }}
                                @endif
                            </dd>
                            <dt class="col-sm-4">{{ __('message.user') }}</dt>
                            <dd class="col-sm-8">{{ optional($payment->user)->name }} (#{{ $payment->user_id }})</dd>
                            <dt class="col-sm-4">{{ __('message.point_amount') }}</dt>
                            <dd class="col-sm-8"><strong>{{ number_format((float) $payment->points, 2) }}</strong></dd>
                            <dt class="col-sm-4">{{ __('message.status') }}</dt>
                            <dd class="col-sm-8">{{ __('message.point_status_'.$payment->status) }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Admin Approve</dt>
                            <dd class="col-sm-7">
                                {{ optional($payment->adminApprover)->name ?: '—' }}
                                <div class="small text-muted">{{ optional($payment->admin_approved_at)->format('Y-m-d H:i') }}</div>
                            </dd>
                            <dt class="col-sm-5">Super Admin Confirm</dt>
                            <dd class="col-sm-7">
                                {{ optional($payment->superAdminConfirmer)->name ?: '—' }}
                                <div class="small text-muted">{{ optional($payment->super_admin_confirmed_at)->format('Y-m-d H:i') }}</div>
                            </dd>
                            <dt class="col-sm-5">{{ __('message.remark') }}</dt>
                            <dd class="col-sm-7" style="white-space: pre-wrap;">{{ $payment->remark ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>

                <hr>
                <form method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-control-label">{{ __('message.remark') }}</label>
                        <input type="text" name="remark" class="form-control" placeholder="optional">
                    </div>
                    <div class="col-md-6 d-flex flex-wrap gap-2">
                        @if($canApprove && $payment->status === 'pending_admin')
                            <button formaction="{{ route('point-system.payment.approve', $payment->id) }}" class="btn btn-primary">
                                {{ __('message.point_action_approve') }}
                            </button>
                        @endif
                        @if($canConfirm && $payment->status === 'pending_super_admin')
                            <button formaction="{{ route('point-system.payment.confirm', $payment->id) }}" class="btn btn-success">
                                {{ __('message.point_action_confirm') }}
                            </button>
                        @endif
                        @if(
                            ($canConfirm && in_array($payment->status, ['pending_admin', 'pending_super_admin'], true))
                            || ($canApprove && $payment->status === 'pending_admin')
                        )
                            <button formaction="{{ route('point-system.payment.reject', $payment->id) }}" class="btn btn-outline-danger"
                                    onclick="return confirm(@json(__('message.are_you_sure') ?? 'Are you sure?'))">
                                {{ __('message.point_action_reject') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-master-layout>

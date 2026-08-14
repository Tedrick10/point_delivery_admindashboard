<div class="card shadow mb-10">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">{{ $pageTitle }}</h4>
        <a href="{{ route('ordersms.index') }}" class="btn btn-sm btn-primary">{{ __('message.sms_template') }}</a>
    </div>
    <div class="card-body">
        <p class="text-muted">{{ __('message.sms_template_description') ?? 'Manage SMS templates for order status notifications from the SMS Template section.' }}</p>
        <div class="row">
            @foreach($sms_template_setting as $templateKey => $templateValue)
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="border rounded p-3 h-100">
                        <h6 class="mb-2">{{ __('message.' . $templateKey) }}</h6>
                        <p class="text-muted small mb-0 text-truncate">{{ $templateValue ?: __('message.not_configured') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

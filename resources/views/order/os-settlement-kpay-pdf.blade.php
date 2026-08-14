<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .meta { margin-bottom: 8px; }
        img { max-width: 100%; max-height: 700px; }
    </style>
</head>
<body>
    <h3>{{ __('message.kpay_slip') }}</h3>
    @if(!empty($kpayName))
        <div class="meta"><strong>{{ __('message.kpay_name') }}:</strong> {{ $kpayName }}</div>
    @endif
    @if(!empty($kpayNo))
        <div class="meta"><strong>{{ __('message.kpay_no') }}:</strong> {{ $kpayNo }}</div>
    @endif
    @if(!empty($amount))
        <div class="meta"><strong>{{ __('message.amount') }}:</strong> {{ number_format((float) $amount) }}</div>
    @endif
    @if(!empty($kpayImagePath) && file_exists($kpayImagePath))
        <div style="margin-top: 16px;">
            <img src="{{ $kpayImagePath }}" alt="KBZ Pay Slip">
        </div>
    @endif
</body>
</html>

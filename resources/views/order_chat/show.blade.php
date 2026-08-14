<x-master-layout>
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">{{ $pageTitle }}</h4>
                    <a href="{{ route('order-chat.index') }}" class="btn btn-sm btn-secondary">{{ __('message.back') }}</a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4"><strong>{{ __('message.client') }}:</strong> {{ optional($order->client)->name }}</div>
                        <div class="col-md-4"><strong>{{ __('message.delivery_man') }}:</strong> {{ optional($order->delivery_man)->name ?? '-' }}</div>
                        <div class="col-md-4"><strong>{{ __('message.status') }}:</strong> {!! orderStatus($order->status) !!}</div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @forelse($messages as $msg)
                        <div class="mb-3 p-2 border rounded {{ $msg->sender_type === 'client' ? 'bg-light' : '' }}">
                            <div class="d-flex justify-content-between">
                                <strong>{{ optional($msg->sender)->name ?? $msg->sender_type }}
                                    @if($msg->way_type !== 'general')
                                        <span class="badge badge-info">{{ ucfirst($msg->way_type) }}</span>
                                    @endif
                                </strong>
                                <small class="text-muted">{{ $msg->created_at->format('Y-m-d H:i') }}</small>
                            </div>
                            @if($msg->message_type === 'image' && $msg->image_url)
                                <img src="{{ $msg->image_url }}" class="mt-2" style="max-width:200px">
                            @else
                                <p class="mb-0 mt-1">{{ $msg->message }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-center text-muted">{{ __('message.no_record_found') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-master-layout>

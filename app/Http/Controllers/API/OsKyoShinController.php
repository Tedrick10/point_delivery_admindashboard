<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KyoShinBatch;
use App\Models\KyoShinItem;
use App\Services\OsSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OsKyoShinController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ((string) $user->user_type !== 'client') {
            return json_custom_response(['data' => []]);
        }
        if (! Schema::hasTable('kyo_shin_batches')) {
            return json_custom_response(['data' => []]);
        }

        $batches = KyoShinBatch::query()
            ->where('os_user_id', (int) $user->id)
            ->withCount([
                'items as item_count',
                'items as open_count' => static fn ($q) => $q->where('status', KyoShinItem::STATUS_ADVANCED_PAID),
                'items as finished_count' => static fn ($q) => $q->where('status', KyoShinItem::STATUS_FINISHED),
            ])
            ->orderByDesc('id')
            ->get();

        return json_custom_response([
            'data' => $batches->map(fn (KyoShinBatch $batch) => $this->serializeBatch($batch, false))->values(),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $user = auth()->user();
        if (! Schema::hasTable('kyo_shin_batches')) {
            abort(404);
        }

        $batch = KyoShinBatch::query()
            ->with(['items.dispatchItem'])
            ->findOrFail($id);

        if ((string) $user->user_type === 'client' && (int) $batch->os_user_id !== (int) $user->id) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        return json_custom_response([
            'data' => $this->serializeBatch($batch, true),
        ]);
    }

    public function download(Request $request, int $id, string $format): BinaryFileResponse
    {
        $user = auth()->user();
        $batch = KyoShinBatch::query()->findOrFail($id);

        if ((string) $user->user_type === 'client' && (int) $batch->os_user_id !== (int) $user->id) {
            abort(403);
        }

        $map = [
            'table' => ['path' => $batch->slip_table_path, 'name' => 'kyo-shin-slip-table.html'],
            'image' => ['path' => $batch->slip_photo_path, 'name' => 'kyo-shin-slip.jpg'],
        ];

        if (! isset($map[$format])) {
            abort(404);
        }

        $path = $map[$format]['path'];
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->download(Storage::disk('public')->path($path), $map[$format]['name']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeBatch(KyoShinBatch $batch, bool $withItems): array
    {
        $itemCount = (int) ($batch->item_count ?? $batch->items->count());
        $openCount = (int) ($batch->open_count ?? $batch->items->where('status', KyoShinItem::STATUS_ADVANCED_PAID)->count());
        $finishedCount = (int) ($batch->finished_count ?? $batch->items->where('status', KyoShinItem::STATUS_FINISHED)->count());

        $fromDate = optional($batch->created_at)?->timezone('Asia/Yangon')->format('d-m-Y');
        $toDate = $batch->due_finished_at?->format('d-m-Y');

        $payload = [
            'id' => (int) $batch->id,
            'amount' => (float) $batch->amount,
            'payment_method' => (string) ($batch->payment_method ?: 'kpay'),
            'kpay_name' => $batch->kpay_name,
            'kpay_no' => $batch->kpay_no,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'due_finished_at' => $toDate,
            'created_at' => $fromDate,
            'slip_photo_url' => $batch->slipPhotoUrl(),
            'slip_photo_urls' => $batch->slipPhotoUrls(),
            'kpay_image_url' => $batch->slipPhotoUrl(),
            'kpay_image_urls' => $batch->slipPhotoUrls(),
            'slip_table_url' => $batch->slipTableUrl(),
            'item_count' => $itemCount,
            'open_count' => $openCount,
            'finished_count' => $finishedCount,
        ];

        if ($withItems) {
            $dispatchItems = $batch->items
                ->map(static fn (KyoShinItem $row) => $row->dispatchItem)
                ->filter()
                ->values();
            $invoiceDate = $fromDate ?: now('Asia/Yangon')->format('d-m-Y');
            $slipData = app(OsSettlementService::class)->buildSlipRows($dispatchItems, $invoiceDate);
            foreach ($slipData['rows'] as $i => $row) {
                $slipData['rows'][$i]['is_kyo_shin'] = true;
            }
            $payload['slip_rows'] = $slipData['rows'];
            $payload['slip_totals'] = $slipData['totals'];
        }

        return $payload;
    }
}

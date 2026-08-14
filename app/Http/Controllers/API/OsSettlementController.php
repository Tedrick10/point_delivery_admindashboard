<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OsSettlementBatch;
use App\Services\OsSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OsSettlementController extends Controller
{
    public function show(Request $request, int $id)
    {
        $user = auth()->user();
        $batch = OsSettlementBatch::query()->findOrFail($id);

        if ((string) $user->user_type === 'client' && (int) $batch->os_user_id !== (int) $user->id) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $service = app(OsSettlementService::class);
        $urls = $service->publicUrls($batch);
        $slipData = $service->slipRowsForBatch($batch);

        return json_custom_response([
            'data' => [
                'id' => $batch->id,
                'amount' => (float) $batch->amount,
                'from_date' => $batch->from_date?->format('d-m-Y'),
                'to_date' => $batch->to_date?->format('d-m-Y'),
                'delivery_format' => $batch->delivery_format ?? 'table',
                'kpay_name' => $batch->kpay_name,
                'kpay_no' => $batch->kpay_no,
                'finished_at' => $batch->finished_at?->toIso8601String(),
                'slip_rows' => $slipData['rows'],
                'slip_totals' => $slipData['totals'],
                'slip_table_url' => $urls['slip_table_url'],
                'slip_image_url' => $urls['slip_image_url'],
                'slip_pdf_url' => $urls['slip_pdf_url'],
                'kpay_image_url' => $urls['kpay_image_url'],
                'kpay_pdf_url' => $urls['kpay_pdf_url'],
                'combined_pdf_url' => $urls['combined_pdf_url'],
            ],
        ]);
    }

    public function download(Request $request, int $id, string $format): BinaryFileResponse
    {
        $user = auth()->user();
        $batch = OsSettlementBatch::query()->findOrFail($id);

        if ((string) $user->user_type === 'client' && (int) $batch->os_user_id !== (int) $user->id) {
            abort(403);
        }

        $map = [
            'table' => ['path' => $batch->slip_table_path, 'name' => 'settlement-slip-table.html'],
            'image' => ['path' => $batch->slip_image_path, 'name' => 'settlement-slip.png'],
            'slip-pdf' => ['path' => $batch->slip_pdf_path, 'name' => 'settlement-slip.pdf'],
            'kpay-pdf' => ['path' => $batch->kpay_pdf_path, 'name' => 'kpay-slip.pdf'],
            'kpay-image' => ['path' => $batch->kpay_slip_path, 'name' => 'kpay-slip.jpg'],
            'combined-pdf' => ['path' => $batch->combined_pdf_path, 'name' => 'settlement-combined.pdf'],
        ];

        if (! isset($map[$format])) {
            abort(404);
        }

        $path = $map[$format]['path'];
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $absolute = Storage::disk('public')->path($path);

        return response()->download($absolute, $map[$format]['name']);
    }
}

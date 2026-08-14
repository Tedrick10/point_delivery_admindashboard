<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfofpicturesRequest;
use Illuminate\Http\Request;
use App\Models\Profofpictures;
use App\Models\Order;
use App\Http\Resources\ProfofpicturesResource;
use App\Services\PhotoOrderDispatchService;
use App\Services\PickupParcelDispatchService;

class ProfofPicturesController extends Controller
{
    public function profOfpictureList(Request $request)
    {
        $profpicture = Profofpictures::query();

        $profpicture->when(request('order_id'), function ($q) {
            return $q->where('order_id', request('order_id'));
        });
        $profpicture->when(request('type'), function ($q) {
            return $q->where('type', request('type'));
        });

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $profpicture->count();
            }
        }

        $profpicture = $profpicture->orderBy('id','asc')->paginate($per_page);
        $items = ProfofpicturesResource::collection($profpicture);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];

        return json_custom_response($response);
    }

    public function profOfpictureSave(ProfofpicturesRequest $request)
    {
        $data = $request->all();
        if ($request->is('api/*')) {
            $files = $this->normalizeUploadedFiles($request, 'prof_file');

            if ($request->type === 'gate_pass') {
                $files = array_slice($files, 0, 1);

                if ($request->order_id) {
                    Profofpictures::query()
                        ->where('order_id', $request->order_id)
                        ->where('type', 'gate_pass')
                        ->get()
                        ->each(function (Profofpictures $existing) {
                            $existing->clearMediaCollection('prof_file');
                            $existing->delete();
                        });
                }
            }

            if ($request->type === 'pickup_time' && $request->order_id) {
                $order = Order::find($request->order_id);
                $pickupService = app(PickupParcelDispatchService::class);
                if ($order && $pickupService->isDispatchPickupOrder($order)) {
                    $dispatchItemId = (int) $request->input('dispatch_item_id', 0);

                    // Per-item photo upload: never sync/create items mid-pickup.
                    if ($dispatchItemId <= 0) {
                        $pickupService->ensureDispatchItems($order);
                    }

                    if ($dispatchItemId > 0) {
                        $files = array_slice($files, 0, 1);
                        if (count($files) !== 1) {
                            return json_custom_response([
                                'status' => false,
                                'message' => __('message.pickup_item_photo_required'),
                            ], 422);
                        }
                    } elseif ($pickupService->requiresRiderPickupPhotos($order->fresh())) {
                        $required = $pickupService->requiredPickupPhotoCount($order->fresh());
                        if ($required > 0 && count($files) !== $required) {
                            return json_custom_response([
                                'status' => false,
                                'message' => __('message.pickup_parcel_photos_required', ['count' => $required]),
                            ], 422);
                        }
                    }
                }
            }

            $profpicture = Profofpictures::create($data);

            foreach ($files as $image) {
                $profpicture->addMedia($image)->toMediaCollection('prof_file');
            }

            if ($request->type === 'photo_order' && $request->order_id) {
                Order::where('id', $request->order_id)->update(['is_photo_order' => 1]);
            }

            if ($request->order_id) {
                $order = Order::find($request->order_id);
                if ($order && $request->type === 'photo_order') {
                    $photoService = app(PhotoOrderDispatchService::class);
                    $photoService->ensurePickUpState($order->fresh());
                    $photoService->sync($order->fresh());
                }

                if ($order && $request->type === 'gate_pass') {
                    app(\App\Services\TextOrderDispatchService::class)->ensurePickUpState($order->fresh());
                    app(\App\Services\TextOrderDispatchService::class)->sync($order->fresh());
                }

                $type = (string) ($request->type ?? '');
                if ($order && preg_match('/^recipient_(\d+)$/', $type, $matches)) {
                    $mediaIds = $profpicture->getMedia('prof_file')->pluck('id')->all();
                    $textService = app(\App\Services\TextOrderDispatchService::class);
                    $textService->ensurePickUpState($order->fresh());
                    $textService->attachRecipientPhotos($order->fresh(), (int) $matches[1], $mediaIds);
                }

                if ($order && $request->type === 'pickup_time') {
                    $mediaIds = $profpicture->getMedia('prof_file')->pluck('id')->all();
                    $dispatchItemId = (int) $request->input('dispatch_item_id', 0);
                    $pickupService = app(PickupParcelDispatchService::class);

                    if ($dispatchItemId > 0) {
                        if (empty($mediaIds)) {
                            return json_custom_response([
                                'status' => false,
                                'message' => __('message.pickup_item_photo_required'),
                            ], 422);
                        }

                        $attached = $pickupService->attachPickupPhotoToItem(
                            $order->fresh(),
                            $dispatchItemId,
                            (int) $mediaIds[0]
                        );

                        if (! $attached) {
                            return json_custom_response([
                                'status' => false,
                                'message' => __('message.pickup_item_photo_required'),
                            ], 422);
                        }

                        $item = \App\Models\DispatchOrderItem::query()
                            ->with(['fromBranch', 'toBranch', 'photoMedia'])
                            ->find($dispatchItemId);

                        if ($item) {
                            try {
                                app(\App\Services\DispatchOrderAuditService::class)
                                    ->logPhotoUploaded($order->fresh(), $item);
                            } catch (\Throwable $e) {
                                \Log::warning('dispatch audit failed after pickup photo upload', [
                                    'order_id' => $order->id,
                                    'item_id' => $item->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        return json_custom_response([
                            'status' => true,
                            'message' => __('message.save_form', ['form' => __('message.profofpicture')]),
                            'data' => $item
                                ? (new \App\Http\Resources\DispatchOrderItemResource($item))->resolve()
                                : null,
                        ]);
                    }

                    $pickupService->attachPickupPhotos($order->fresh(), $mediaIds);
                }
            }

            $message = __('message.save_form', ['form' => __('message.profofpicture')]);
            return json_message_response($message);
        }
    }

    protected function normalizeUploadedFiles(Request $request, string $key): array
    {
        $files = $request->file($key);
        if ($files === null) {
            return [];
        }

        return is_array($files) ? array_values(array_filter($files)) : [$files];
    }
}

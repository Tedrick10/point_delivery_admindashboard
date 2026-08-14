<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdvertisementResource;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function getList(Request $request)
    {
        $query = Advertisement::active();

        if ($request->placement) {
            $query->where('placement', $request->placement);
        }

        if ($request->target_app) {
            $target = $request->target_app;
            $query->where(function ($q) use ($target) {
                $q->where('target_app', $target)->orWhere('target_app', 'both');
            });
        }

        $ads = $query->orderBy('id', 'desc')->get();
        return json_custom_response(['data' => AdvertisementResource::collection($ads)]);
    }

    public function getDetail(Request $request)
    {
        $ad = Advertisement::find($request->id);
        if (!$ad) {
            return json_message_response(__('message.not_found_entry', ['name' => __('message.advertisement')]), 400);
        }
        return json_custom_response(['data' => new AdvertisementResource($ad)]);
    }
}

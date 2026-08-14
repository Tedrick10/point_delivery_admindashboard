<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderChatMessageResource;
use App\Models\OrderChatMessage;
use Illuminate\Http\Request;

class OrderChatMessageController extends Controller
{
    public function getList(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);
        $messages = OrderChatMessage::where('order_id', $request->order_id)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();
        return json_custom_response(['data' => OrderChatMessageResource::collection($messages)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'message' => 'nullable|string',
            'way_type' => 'nullable|string',
            'message_type' => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        $user = auth()->user();
        $data = $request->all();
        $data['sender_id'] = $user->id;
        $data['sender_type'] = $user->user_type;
        $data['way_type'] = $request->way_type ?? 'general';

        $message = OrderChatMessage::create($data);
        return json_custom_response([
            'message' => __('message.save_form', ['form' => __('message.chat')]),
            'data' => new OrderChatMessageResource($message->load('sender')),
        ]);
    }
}

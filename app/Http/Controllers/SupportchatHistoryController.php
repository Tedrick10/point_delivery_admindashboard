<?php

namespace App\Http\Controllers;

use App\Models\SupportChathistory;
use App\Models\CustomerSupport;
use Illuminate\Http\Request;

class SupportchatHistoryController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'support_id' => 'required|integer|exists:customer_supports,id',
            'message' => 'nullable|string|max:5000',
            'message_type' => 'nullable|in:text,image,emoji,audio,video,sticker,gif',
            'chat_image' => 'nullable|file|max:51200',
            'chat_media' => 'nullable|file|max:51200',
        ]);

        if (!$request->filled('message') && !$request->hasFile('chat_image') && !$request->hasFile('chat_media')) {
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => __('message.message_or_image_required')], 422);
            }
            return back()->withErrors(['message' => __('message.message_or_image_required')]);
        }

        $messageType = $request->input('message_type', 'text');
        if ($request->hasFile('chat_image')) {
            $messageType = in_array($messageType, ['sticker', 'gif'], true) ? $messageType : 'image';
        }
        if ($request->hasFile('chat_media')) {
            $messageType = resolveChatMediaMessageType(
                $request->file('chat_media'),
                $request->input('message_type')
            );
        }

        $supportchat = SupportChathistory::create([
            'support_id' => $request->support_id,
            'user_id' => auth()->id(),
            'message' => $request->input('message', ''),
            'message_type' => $messageType,
            'datetime' => now(),
        ]);

        if ($request->hasFile('chat_image')) {
            uploadMediaFile($supportchat, $request->file('chat_image'), 'chat_image');
        }
        if ($request->hasFile('chat_media')) {
            uploadMediaFile($supportchat, $request->file('chat_media'), 'chat_media');
        }

        $support = CustomerSupport::with('user')->find($request->support_id);
        if ($support) {
            $sender = auth()->user();
            $isMedia = in_array($messageType, ['image', 'gif', 'sticker', 'audio', 'video'], true);
            $notification_data = buildCustomerSupportNotificationPayload(
                $support,
                $sender,
                $request->input('message'),
                $isMedia
            );

            $isAdminSender = in_array((string) $sender->user_type, ['admin', 'super_admin', 'demo_admin'], true)
                || isSuperAdmin($sender);

            if ($isAdminSender) {
                if ($support->user) {
                    notifyUserCustomerSupport($support->user, $notification_data);
                }
            } else {
                notifyAdminsCustomerSupport($notification_data);
            }
        }

        $message = __('message.save_form', ['form' => __('message.customer_support')]);

        if (request()->is('api/*') || request()->ajax()) {
            return json_custom_response([
                'message' => $message,
                'data' => app(CustomerSupportController::class)->formatChatMessage($supportchat->fresh(), auth()->id()),
            ]);
        }

        return redirect()->route('customersupport.show', $supportchat->support_id)->withSuccess($message);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $supportchat = SupportChathistory::findOrFail($id);

        $supportchat->fill($request->all())->update();

        $message = __('message.update_form',['form' => __('message.supportchathistory')]);

        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        if(auth()->check()){
            return redirect()->route('customersupport.index')->withSuccess($message);
        }
        return redirect()->back()->withSuccess($message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(env('APP_DEMO')){
            $message = __('message.demo_permission_denied');
            if(request()->ajax()) {
                return response()->json(['status' => true, 'message' => $message ]);
            }
            return redirect()->route('customersupport.index')->withErrors($message);
        }
        $supportchat = SupportChathistory::find($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.customer_support')]);

        if($supportchat != '') {
            $supportchat->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.customer_support')]);
        }

        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}

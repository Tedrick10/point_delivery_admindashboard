<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerSupportDataTable;
use App\Http\Requests\CustomerSupportRequest;
use Illuminate\Http\Request;
use App\Models\CustomerSupport;
use App\Models\SupportChathistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Notifications\CustomerSupportNotification;
use App\Models\Order;

class CustomerSupportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CustomerSupportDataTable $dataTable)
    {
        $type = request('support_type');
        $formLabel = match ($type) {
            'Inquiry' => __('message.inquiry_chat'),
            'Complaint' => __('message.complaint_chat'),
            default => __('message.customer_support'),
        };
        $pageTitle = __('message.list_form_title', ['form' => $formLabel]);
        $auth_user = authSession();
        $assets = ['datatable'];

        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CustomerSupportRequest $request)
    {
        $data = $request->all();
        $data['user_id'] = Auth::id();

        if ($request->is('api/*')) {
            $customerSupport = CustomerSupport::create($data);
            if ($request->filled('order_id') && $request->support_type === 'Report against DeliveryMan') {
                $order = Order::find($request->order_id);

                if ($order && $order->delivery_man_id) {
                    User::where('id', $order->delivery_man_id)->update(['flag' => '1']);
                }
            }

            $data['support_id'] = $customerSupport->id;
            $data['datetime'] = now();
            if ($request->support_image != null) {
                uploadMediaFile($customerSupport, $request->support_image, 'support_image');
            }
            if ($request->support_videos != null) {
                uploadMediaFile($customerSupport, $request->support_videos, 'support_videos');
            }
            $supportChatHistory = SupportChathistory::create($data);
            $message = __('message.save_form', ['form' => __('message.customer_support')]);

            $notification_data = buildCustomerSupportNotificationPayload(
                $customerSupport,
                auth()->user(),
                $request->message ?? $customerSupport->message,
                $request->support_image != null
            );

            notifyAdminsCustomerSupport($notification_data);
            return json_message_response($message);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $current_user = auth()->user();
        $data = CustomerSupport::findOrFail($id);
        $pageTitle = match ((string) ($data->support_type ?? '')) {
            'Inquiry' => __('message.inquiry_chat'),
            'Complaint' => __('message.complaint_chat'),
            default => __('message.customer_support'),
        };
        $match  = SupportChathistory::with('user')->where('support_id', $id)->orderBy('datetime', 'asc')->get();

        if ($current_user && ! in_array($current_user->user_type, ['client', 'delivery_man'], true)) {
            $data->staff_read_at = now();
            $data->save();
        }

        if ($current_user && $current_user->unreadNotifications->isNotEmpty()) {
            $current_user->unreadNotifications
                ->filter(fn ($notification) => (int) ($notification->data['support_id'] ?? 0) === (int) $id)
                ->markAsRead();
        }

        return view('customer-suport.show', compact('data', 'pageTitle', 'current_user', 'match'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (env('APP_DEMO')) {
            $message = __('message.demo_permission_denied');
            if (request()->is('api/*')) {
                return response()->json(['status' => true, 'message' => $message]);
            }
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->route('customersupport.index')->withErrors($message);
        }
        $customersupport = CustomerSupport::find($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.customer_support')]);

        if ($customersupport != '') {
            $customersupport->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.customer_support')]);
        }
        if (request()->is('api/*')) {
            return json_message_response($message);
        }
        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }

    public function chatMessage(Request $request)
    {
        $request->validate([
            'support_id' => 'required|integer|exists:customer_supports,id',
            'message' => 'nullable|string|max:5000',
            'message_type' => 'nullable|in:text,image,emoji,audio,video,sticker,gif',
            'chat_image' => 'nullable|file|max:51200',
            'chat_media' => 'nullable|file|max:51200',
        ]);

        if (!$request->filled('message') && !$request->hasFile('chat_image') && !$request->hasFile('chat_media')) {
            return json_message_response(__('message.message_or_image_required'), 422);
        }

        $user = auth()->user();
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

        $data = [
            'support_id' => (int) $request->support_id,
            'user_id' => $user->id,
            'message' => $request->input('message', ''),
            'message_type' => $messageType,
            'datetime' => now(),
        ];

        $chat = SupportChathistory::create($data);

        if ($request->hasFile('chat_image')) {
            uploadMediaFile($chat, $request->file('chat_image'), 'chat_image');
        }
        if ($request->hasFile('chat_media')) {
            uploadMediaFile($chat, $request->file('chat_media'), 'chat_media');
        }

        $support = CustomerSupport::with('user')->find($request->support_id);
        if ($support) {
            $this->notifyChatParticipants(
                $support,
                $user,
                $request->input('message'),
                in_array($messageType, ['image', 'gif', 'sticker', 'audio', 'video'], true)
            );
        }

        return json_custom_response([
            'message' => __('message.message_sent_successfully'),
            'data' => $this->formatChatMessage($chat->fresh(), $user->id),
        ]);
    }

    public function formatChatMessage(SupportChathistory $chat, ?int $currentUserId = null): array
    {
        if (! $chat->relationLoaded('user')) {
            $chat->load('user');
        }
        $user = $chat->user;
        $userType = (string) (optional($user)->user_type ?? '');
        $rawMessageType = strtolower((string) ($chat->message_type ?? 'text'));
        $isBot = in_array($rawMessageType, ['bot', 'bot_image', 'auto_reply', 'ai'], true)
            || $this->isInquiryAutoReplyText((string) ($chat->message ?? ''));

        // Admin panel: align by party (customer left, staff/AI right) — not only "my" messages.
        $isCustomerSender = in_array($userType, ['client', 'delivery_man'], true);
        if ($isBot) {
            $senderRole = 'ai';
            $currentUserClass = 'mm-current-user';
            $messageClass = 'justify-content-end';
            $displayName = 'AI';
            $sendBy = 'ai';
        } elseif (! $isCustomerSender) {
            $senderRole = 'admin';
            $currentUserClass = 'mm-current-user';
            $messageClass = 'justify-content-end';
            $displayName = optional($user)->name ?: 'Admin';
            $sendBy = $userType !== '' ? $userType : 'admin';
        } else {
            $senderRole = 'user';
            $currentUserClass = 'mm-other-user';
            $messageClass = 'justify-content-start';
            $displayName = optional($user)->name;
            $sendBy = $userType !== '' ? $userType : 'client';
        }

        // Keep media types for the UI renderer; bot is a sender flag, not a media type.
        $messageType = match ($rawMessageType) {
            'bot', 'auto_reply', 'ai' => 'text',
            'bot_image' => 'image',
            default => $chat->message_type ?? 'text',
        };

        $mediaUrl = getMediaFileExit($chat, 'chat_media')
            ? getSingleMedia($chat, 'chat_media', null)
            : (getMediaFileExit($chat, 'chat_image') ? getSingleMedia($chat, 'chat_image', null) : null);

        return [
            'id' => $chat->id,
            'send_by' => $sendBy,
            'sender_role' => $senderRole,
            'is_bot' => $isBot,
            'message' => $chat->message,
            'message_type' => $messageType,
            'chat_image' => getMediaFileExit($chat, 'chat_image') ? getSingleMedia($chat, 'chat_image', null) : null,
            'chat_media' => $mediaUrl,
            'datetime' => optional($chat->datetime ?? $chat->created_at)->format('Y-m-d H:i:s'),
            'user_name' => $displayName,
            'profile_image' => getSingleMedia($user, 'profile_image', null),
            'time_label' => date('h:i A', strtotime(dateAgoFormate($chat->datetime ?? $chat->created_at))),
            'current_user_class' => $currentUserClass,
            'message_class' => $messageClass,
        ];
    }

    protected function isInquiryAutoReplyText(string $message): bool
    {
        $text = trim($message);
        if ($text === '') {
            return false;
        }

        static $autoReplyTexts = null;
        if ($autoReplyTexts === null) {
            try {
                $autoReplyTexts = \App\Models\InquiryAutoReply::query()
                    ->pluck('reply_message')
                    ->map(fn ($m) => trim((string) $m))
                    ->filter()
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                $autoReplyTexts = [];
            }
        }

        return in_array($text, $autoReplyTexts, true);
    }

    protected function notifyChatParticipants(CustomerSupport $support, User $sender, ?string $messageText, bool $isImage = false): void
    {
        $notification_data = buildCustomerSupportNotificationPayload(
            $support,
            $sender,
            $messageText,
            $isImage
        );
        $notification_data['support_type'] = $support->support_type;
        $notification_data['channel'] = $support->support_type;

        if (in_array($sender->user_type, ['admin', 'super_admin'], true) || isSuperAdmin($sender)) {
            if ($support->user) {
                notifyUserCustomerSupport($support->user, $notification_data);
            }
            return;
        }

        if ($support->support_type === 'Complaint') {
            notifySuperAdminsCustomerSupport($notification_data);
            return;
        }

        notifyAdminsCustomerSupport($notification_data);
    }

    public function chatMessages(Request $request, $id)
    {
        CustomerSupport::findOrFail($id);
        $currentUserId = auth()->id();

        $query = SupportChathistory::with('user')
            ->where('support_id', $id)
            ->orderBy('datetime', 'asc');

        if ($request->filled('since_id')) {
            $query->where('id', '>', (int) $request->since_id);
        }

        $messages = $query->get()->map(fn ($chat) => $this->formatChatMessage($chat, $currentUserId));

        return response()->json([
            'status' => true,
            'messages' => $messages,
        ]);
    }

    public function ensureOrderChat($orderId)
    {
        $order = Order::findOrFail($orderId);

        if (auth()->user()->user_type !== 'admin') {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $support = CustomerSupport::where('order_id', $order->id)
            ->where('support_type', 'Order Chat')
            ->latest('id')
            ->first();

        if (!$support) {
            $support = CustomerSupport::create([
                'user_id' => $order->client_id,
                'order_id' => $order->id,
                'support_type' => 'Order Chat',
                'message' => __('message.order') . ' #' . $order->id,
                'status' => 'open',
            ]);

            SupportChathistory::create([
                'support_id' => $support->id,
                'user_id' => auth()->id(),
                'message' => __('message.order_chat_started'),
                'message_type' => 'text',
                'datetime' => now(),
            ]);
        }

        return response()->json([
            'status' => true,
            'support_id' => $support->id,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([ 'status' => 'required|in:pending,inreview,resolved' ]);

        $support = CustomerSupport::findOrFail($id);

        $deliveryManId = optional(optional($support->order)->delivery_man)->id;
        if ($request->status === 'resolved' && $deliveryManId) {
            User::where('id', $deliveryManId)->update(['flag' => '0']);
        }

        $support->status = $request->input('status');
        $support->resolution_detail = $request->input('resolution_detail');
        $support->save();
        $message = __('message.status_updated');
        if (request()->is('api/*')) {
            return json_message_response($message);
        }
        return redirect()->back()->with('success', __('message.status_updated'));
    }
}

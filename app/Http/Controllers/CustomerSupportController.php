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
        $pageTitle = __('message.list_form_title', ['form' => __('message.customer_support')]);
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
        $pageTitle = __('message.add_form_title', ['form' => __('message.customer_support')]);
        $data = CustomerSupport::findOrFail($id);
        $match  = SupportChathistory::with('user')->where('support_id', $id)->orderBy('datetime', 'asc')->get();

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
            'message_type' => 'nullable|in:text,image,emoji',
            'chat_image' => 'nullable|image|max:10240',
        ]);

        if (!$request->filled('message') && !$request->hasFile('chat_image')) {
            return json_message_response(__('message.message_or_image_required'), 422);
        }

        $user = auth()->user();
        $messageType = $request->input('message_type', 'text');
        if ($request->hasFile('chat_image')) {
            $messageType = 'image';
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

        $support = CustomerSupport::with('user')->find($request->support_id);
        if ($support) {
            $this->notifyChatParticipants(
                $support,
                $user,
                $request->input('message'),
                $messageType === 'image'
            );
        }

        return json_custom_response([
            'message' => __('message.message_sent_successfully'),
            'data' => $this->formatChatMessage($chat->fresh(), $user->id),
        ]);
    }

    public function formatChatMessage(SupportChathistory $chat, ?int $currentUserId = null): array
    {
        $user = $chat->user;
        $currentUserId = $currentUserId ?? auth()->id();
        $isCurrentUser = $chat->user_id === $currentUserId;
        $userType = optional($user)->user_type;

        if ($isCurrentUser && $userType === 'admin') {
            $currentUserClass = 'mm-current-user';
            $messageClass = 'justify-content-end';
        } elseif ($isCurrentUser && $userType === 'client') {
            $currentUserClass = 'mm-other-user';
            $messageClass = 'justify-content-start';
        } else {
            $currentUserClass = 'mm-other-user';
            $messageClass = 'justify-content-start';
        }

        return [
            'id' => $chat->id,
            'send_by' => $userType,
            'message' => $chat->message,
            'message_type' => $chat->message_type ?? 'text',
            'chat_image' => getMediaFileExit($chat, 'chat_image') ? getSingleMedia($chat, 'chat_image', null) : null,
            'datetime' => optional($chat->datetime ?? $chat->created_at)->format('Y-m-d H:i:s'),
            'user_name' => optional($user)->name,
            'profile_image' => getSingleMedia($user, 'profile_image', null),
            'time_label' => date('h:i A', strtotime(dateAgoFormate($chat->datetime ?? $chat->created_at))),
            'current_user_class' => $currentUserClass,
            'message_class' => $messageClass,
        ];
    }

    protected function notifyChatParticipants(CustomerSupport $support, User $sender, ?string $messageText, bool $isImage = false): void
    {
        $notification_data = buildCustomerSupportNotificationPayload(
            $support,
            $sender,
            $messageText,
            $isImage
        );

        if ($sender->user_type === 'admin') {
            if ($support->user) {
                notifyUserCustomerSupport($support->user, $notification_data);
            }
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

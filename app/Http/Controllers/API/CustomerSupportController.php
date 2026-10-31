<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;
use App\Http\Resources\CityResource;
use App\Http\Resources\CustomerSupportResource;
use App\Models\CustomerSupport;
use App\Models\SupportChathistory;
use App\Models\User;
use App\Notifications\CustomerSupportNotification;

class CustomerSupportController extends Controller
{
    public const ADMIN_CHAT_SUPPORT_TYPE = 'Admin Chat';
    public const INQUIRY_SUPPORT_TYPE = 'Inquiry';
    public const COMPLAINT_SUPPORT_TYPE = 'Complaint';

    /** @var list<string> */
    public const CHANNEL_TYPES = [
        self::ADMIN_CHAT_SUPPORT_TYPE,
        self::INQUIRY_SUPPORT_TYPE,
        self::COMPLAINT_SUPPORT_TYPE,
    ];

    public function getList(Request $request)
    {
        $user = auth()->user();
        if ($user && (isSuperAdmin($user) || $user->hasRole('admin') || ($user->user_type ?? '') === 'admin')) {
            $support = CustomerSupport::query();
        } else {
            $support = CustomerSupport::myCustomersupport();
        }
        $support->when(request('support_type'), function ($q) {
            return $q->where('support_type', request('support_type'));
        });
        // $support = CustomerSupport::myCustomersupport();

        $support->when(request('support_id'), function ($q) {
            return $q->where('id', request('support_id'));
        });
        if( $request->has('status') && isset($request->status) ) {
            $support = $support->where('status',request('status'));
        }
        
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $support->count();
            }
        }

        $support = $support->orderBy('id', 'desc')->paginate($per_page);
        $items = CustomerSupportResource::collection($support);

        $current_user = auth()->user();
        if(count($current_user->unreadNotifications) > 0 ) {
            $current_user->unreadNotifications->where('data.sender_id',request('sender_id'))->markAsRead();
        }
        $response = [
            'pagination' => json_pagination_response($items),
            'customersupport' => $items,
            // 'supportchat_histriry' =>SupportchatHistoryResource::collection($support),
        ];
        
        return json_custom_response($response);
    }

    public function initAdminChat(Request $request)
    {
        return $this->initChannelChat($request, self::ADMIN_CHAT_SUPPORT_TYPE);
    }

    /**
     * Start or resume Inquiry / Complaint / Admin Chat channels.
     * Body: { "channel": "Inquiry" | "Complaint" | "Admin Chat" }
     */
    public function initChannelChat(Request $request, ?string $forcedType = null)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->user_type, ['client', 'delivery_man'], true)) {
            return json_message_response(__('message.demo_permission_denied'), 403);
        }

        $type = $forcedType ?: (string) $request->input('channel', self::ADMIN_CHAT_SUPPORT_TYPE);
        if (!in_array($type, self::CHANNEL_TYPES, true)) {
            return json_message_response('Invalid chat channel.', 422);
        }

        $support = CustomerSupport::where('user_id', $user->id)
            ->where('support_type', $type)
            ->whereIn('status', ['open', 'pending', 'inreview'])
            ->orderByDesc('id')
            ->first();

        if (!$support) {
            $support = CustomerSupport::create([
                'user_id' => $user->id,
                'support_type' => $type,
                'message' => $type.' chat started',
                'status' => 'open',
            ]);

            SupportChathistory::create([
                'support_id' => $support->id,
                'user_id' => $user->id,
                'message' => 'Chat started',
                'message_type' => 'text',
                'datetime' => now(),
            ]);

            $notification_data = buildCustomerSupportNotificationPayload(
                $support,
                $user,
                __('message.notification_user_started_chat', ['user' => $user->name]),
                false
            );
            $notification_data['message'] = __('message.notification_user_started_chat', ['user' => $user->name]);
            $notification_data['support_type'] = $type;
            $notification_data['channel'] = $type;

            if ($type === self::COMPLAINT_SUPPORT_TYPE) {
                notifySuperAdminsCustomerSupport($notification_data);
            } else {
                notifyAdminsCustomerSupport($notification_data);
            }
        }

        $payload = [
            'status' => true,
            'data' => new CustomerSupportResource($support->load('supportchathistory')),
        ];

        if ($type === self::INQUIRY_SUPPORT_TYPE) {
            $payload['auto_replies'] = $this->activeAutoRepliesPayload();
        }

        return json_custom_response($payload);
    }

    public function inquiryAutoReplies()
    {
        return json_custom_response([
            'status' => true,
            'data' => $this->activeAutoRepliesPayload(),
        ]);
    }

    /**
     * User tapped an auto-reply chip → store user label + bot automation reply.
     */
    public function triggerInquiryAutoReply(Request $request)
    {
        $request->validate([
            'support_id' => 'required|integer|exists:customer_supports,id',
            'auto_reply_id' => 'required|integer|exists:inquiry_auto_replies,id',
        ]);

        $user = auth()->user();
        $support = CustomerSupport::where('id', $request->support_id)
            ->where('user_id', $user->id)
            ->where('support_type', self::INQUIRY_SUPPORT_TYPE)
            ->firstOrFail();

        $auto = \App\Models\InquiryAutoReply::where('id', $request->auto_reply_id)
            ->where('is_active', true)
            ->firstOrFail();

        $userMsg = SupportChathistory::create([
            'support_id' => $support->id,
            'user_id' => $user->id,
            'message' => $auto->label,
            'message_type' => 'text',
            'datetime' => now(),
        ]);

        $botUserId = User::query()
            ->where(function ($q) {
                $q->where('user_type', 'admin')->orWhere('user_type', 'super_admin');
            })
            ->orderBy('id')
            ->value('id') ?: $user->id;

        $mediaItems = $auto->replyMediaItems();
        $hasImages = $mediaItems->isNotEmpty();
        $replyText = trim((string) $auto->reply_message);
        if ($replyText === '' || $replyText === ' ') {
            $replyText = '';
        }

        $botMessages = [];
        $tick = 1;

        if ($replyText !== '') {
            $textMsg = SupportChathistory::create([
                'support_id' => $support->id,
                'user_id' => $botUserId,
                'message' => $replyText,
                'message_type' => 'bot',
                'datetime' => now()->addSeconds($tick++),
            ]);
            $botMessages[] = [
                'id' => $textMsg->id,
                'send_by' => 'ai',
                'message' => $textMsg->message,
                'message_type' => 'text',
                'chat_image' => null,
                'datetime' => optional($textMsg->datetime)->format('Y-m-d H:i:s'),
            ];
        }

        foreach ($mediaItems as $media) {
            $imgMsg = SupportChathistory::create([
                'support_id' => $support->id,
                'user_id' => $botUserId,
                'message' => '',
                'message_type' => 'bot_image',
                'datetime' => now()->addSeconds($tick++),
            ]);

            $chatImageUrl = null;
            try {
                $media->copy($imgMsg, 'chat_image');
            } catch (\Throwable $e) {
                try {
                    $imgMsg->addMediaFromUrl($media->getFullUrl())->toMediaCollection('chat_image');
                } catch (\Throwable $e2) {
                    // ignore copy failure for this frame
                }
            }
            $chatImageUrl = getMediaFileExit($imgMsg, 'chat_image')
                ? getSingleMedia($imgMsg, 'chat_image', null)
                : $media->getFullUrl();

            $botMessages[] = [
                'id' => $imgMsg->id,
                'send_by' => 'ai',
                'message' => '',
                'message_type' => 'image',
                'chat_image' => $chatImageUrl,
                'datetime' => optional($imgMsg->datetime)->format('Y-m-d H:i:s'),
            ];
        }

        // Fallback: ensure at least one bot message if somehow empty.
        if ($botMessages === []) {
            $emptyMsg = SupportChathistory::create([
                'support_id' => $support->id,
                'user_id' => $botUserId,
                'message' => (string) $auto->reply_message,
                'message_type' => 'bot',
                'datetime' => now()->addSecond(),
            ]);
            $botMessages[] = [
                'id' => $emptyMsg->id,
                'send_by' => 'ai',
                'message' => $emptyMsg->message,
                'message_type' => 'text',
                'chat_image' => null,
                'datetime' => optional($emptyMsg->datetime)->format('Y-m-d H:i:s'),
            ];
        }

        $notification_data = buildCustomerSupportNotificationPayload(
            $support,
            $user,
            $auto->label,
            $hasImages
        );
        $notification_data['support_type'] = self::INQUIRY_SUPPORT_TYPE;
        $notification_data['channel'] = self::INQUIRY_SUPPORT_TYPE;
        notifyAdminsCustomerSupport($notification_data);

        return json_custom_response([
            'status' => true,
            'user_message' => [
                'id' => $userMsg->id,
                'send_by' => $user->user_type,
                'message' => $userMsg->message,
                'message_type' => 'text',
                'chat_image' => null,
                'datetime' => optional($userMsg->datetime)->format('Y-m-d H:i:s'),
            ],
            'bot_message' => $botMessages[0],
            'bot_messages' => $botMessages,
        ]);
    }

    protected function activeAutoRepliesPayload(): array
    {
        return \App\Models\InquiryAutoReply::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'label' => $row->label,
                'reply_message' => $row->reply_message,
                'image' => $row->replyImageUrl(),
                'images' => $row->replyImageUrls(),
            ])
            ->values()
            ->all();
    }



    public function getDetail(Request $request)
    {
        $id = $request->id;

        $city = City::where('id',$id)->first();
        if(empty($city))
        {
            $message = __('message.not_found_entry',['name' =>__('message.city')]);
            return json_message_response($message,400);
        }

        $city_detail = new CityResource($city);
        $response = [
            'data' => $city_detail
        ];
        return json_custom_response($response);
    }

    public function multipleDeleteRecords(Request $request)
    {
        $multi_ids = $request->ids;
        $user_type = $request->user_type != null ? $request->user_type : 'client';
        $message = __('message.msg_fail_to_delete', ['item' => __('message.'.$user_type)]);

        foreach ($multi_ids as $id) {
            $user = City::withTrashed()->where('id',$id)->first();
            if ($user) {
                if( $user->deleted_at != null ) {
                    $user->forceDelete();
                } else {
                    $user->delete();
                }
                $message = __('message.msg_deleted',['name' => __('message.'.$user->user_type) ]);
            }
        }

        return json_custom_response(['message'=> $message , 'status' => true]);
    }
}
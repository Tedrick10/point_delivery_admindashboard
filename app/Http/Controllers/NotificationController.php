<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\NotificationDataTable;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(NotificationDataTable $dataTable, Request $request)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.notification')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $activeCategory = $request->get('category', 'all');
        $button = view('notification.category-filter', compact('activeCategory'))->render();
        return $dataTable->render('global.datatable', compact('assets','pageTitle','button','auth_user', 'activeCategory'));
    }

    public function notificationList(Request $request)
    {
        $user = auth()->user();

        $type = $request->type;
        $category = $request->get('category', 'all');
        $panel = $request->get('panel') === 'super_admin' ? 'super_admin' : 'admin';

        if ($type == 'markas_read') {
            $user->last_notification_seen = now();
            $user->save();
            $user->unreadNotifications()
                ->orderByDesc('created_at')
                ->limit(300)
                ->get()
                ->each(function ($notification) use ($panel) {
                    $data = is_array($notification->data) ? $notification->data : [];
                    if (notificationVisibleOnPanel($data, $panel)) {
                        $notification->markAsRead();
                    }
                });
        }

        // Keep Notification "User Messages" in sync with Message menu unread chats.
        try {
            clearNoisyAdminUnreadNotifications($user);
            syncUnreadDispatchItemMessageNotifications($user);
        } catch (\Throwable $e) {
            // Still return list/counts if backfill fails.
        }

        $query = $user->notifications()->latest();
        if ($category !== 'all') {
            $query->where(function ($q) use ($category) {
                if ($category === 'user_messages') {
                    $q->where('data->category', 'user_messages')
                        ->orWhereIn('data->type', ['dispatch_item_message', 'customersupport']);
                } elseif ($category === 'user_orders') {
                    $q->where('data->category', 'user_orders')
                        ->orWhereIn('data->type', [
                            'create', 'draft', 'active', 'cancelled', 'bid_placed', 'payment_status_message',
                        ]);
                } elseif ($category === 'points') {
                    $q->where('data->category', 'points')
                        ->orWhere('data->type', 'like', 'point_%');
                } else {
                    $q->where('data->category', $category);
                }
            });
        }

        // Fetch a small recent window, then de-dupe message threads for the dropdown.
        $fetched = $query->limit(80)->get();
        $seenMessageItems = [];
        $notifications = $fetched->filter(function ($notification) use (&$seenMessageItems, $panel) {
            $data = is_array($notification->data) ? $notification->data : [];
            if (! notificationVisibleOnPanel($data, $panel)) {
                return false;
            }
            if (($data['type'] ?? '') !== 'dispatch_item_message') {
                return true;
            }
            $itemId = (int) ($data['dispatch_order_item_id'] ?? 0);
            if ($itemId <= 0) {
                return true;
            }
            if (isset($seenMessageItems[$itemId])) {
                return false;
            }
            $seenMessageItems[$itemId] = true;

            return true;
        })->take(5)->values();

        $all_unread_count = adminNotificationUnreadCount($user, $panel);
        $active_category = $category;
        $response = [
            'status'     => true,
            'type'       => $type,
            'category'   => $category,
            'panel'      => $panel,
            'unread_count' => $all_unread_count,
            'unread_text' => __('message.you_have_unread_notification', ['number' => $all_unread_count]),
            'data'       => view('notification.dropdown-items', compact('notifications', 'all_unread_count', 'user', 'active_category'))->render()
        ];

        return json_custom_response($response);
    }

    public function notificationCounts(Request $request)
    {
        $user = auth()->user();
        $panel = $request->get('panel') === 'super_admin' ? 'super_admin' : 'admin';

        try {
            clearNoisyAdminUnreadNotifications($user);
            syncUnreadDispatchItemMessageNotifications($user);
        } catch (\Throwable $e) {
            // Still return counts if backfill fails.
        }

        $unread_count = adminNotificationUnreadCount($user, $panel);
        $response = [
            'status'            => true,
            'counts'            => $unread_count,
            'unread_total_count'=> $unread_count,
            'panel'             => $panel,
        ];

        return json_custom_response($response);
    }

    public function markRead(Request $request, string $id)
    {
        $user = auth()->user();
        $panel = $request->get('panel') === 'super_admin' ? 'super_admin' : 'admin';
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }

        return json_custom_response([
            'status' => true,
            'unread_count' => adminNotificationUnreadCount($user, $panel),
        ]);
    }
}

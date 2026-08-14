<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\NotificationDataTable;
use App\Models\Notification;
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

        if ($type == 'markas_read') {
            $user->last_notification_seen = now();
            $user->save();

            if (count($user->unreadNotifications) > 0) {
                $user->unreadNotifications->markAsRead();
            }
        }

        $notifications = $user->notifications;
        if ($category !== 'all') {
            $notifications = $notifications->filter(function ($notification) use ($category) {
                $data = is_array($notification->data) ? $notification->data : [];
                return inferNotificationCategory($data) === $category;
            });
        }

        $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;
        $active_category = $category;
        $response = [
            'status'     => true,
            'type'       => $type,
            'category'   => $category,
            'unread_count' => $all_unread_count,
            'unread_text' => __('message.you_have_unread_notification', ['number' => $all_unread_count]),
            'data'       => view('notification.dropdown-items', compact('notifications', 'all_unread_count', 'user', 'active_category'))->render()
        ];

        return json_custom_response($response);
    }

    public function notificationCounts(Request $request)
    {
        $user = auth()->user();

        $unread_count = 0;
        $unread_total_count = 0;

        if(isset($user->unreadNotifications)){
            $unread_count = $user->unreadNotifications->count();
            $unread_total_count = $unread_count;
        }
        $response = [
            'status'            => true,
            'counts'            => $unread_count,
            'unread_total_count'=> $unread_total_count
        ];

        return json_custom_response($response);
    }
}
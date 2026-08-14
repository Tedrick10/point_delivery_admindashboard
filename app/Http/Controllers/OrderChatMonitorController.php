<?php

namespace App\Http\Controllers;

use App\DataTables\OrderChatDataTable;
use App\Models\Order;
use App\Models\OrderChatMessage;
use Illuminate\Http\Request;

class OrderChatMonitorController extends Controller
{
    public function index(OrderChatDataTable $dataTable)
    {
        if (!auth()->user()->can('order-chat-list')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        $pageTitle = __('message.order_chat_monitoring');
        $auth_user = authSession();
        $assets = ['datatable'];
        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user', 'assets'));
    }

    public function show($orderId)
    {
        if (!auth()->user()->can('order-chat-list')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        $order = Order::with(['client', 'delivery_man'])->findOrFail($orderId);
        $messages = OrderChatMessage::where('order_id', $orderId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();
        $pageTitle = __('message.order_chat_monitoring') . ' - #' . $order->id;
        return view('order_chat.show', compact('order', 'messages', 'pageTitle'));
    }
}

<?php

namespace Database\Seeders;

use App\Models\DispatchItemMessage;
use App\Models\DispatchOrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo chat threads for Admin → Message (dispatch-assigned-items).
 */
class DispatchItemMessageDemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = (int) (User::query()
            ->whereIn('user_type', ['admin', 'demo_admin'])
            ->orderBy('id')
            ->value('id') ?: 1);

        $now = Carbon::now('Asia/Yangon');

        $threads = [
            [
                'item_id' => 194,
                'messages' => [
                    ['sender_type' => 'client', 'message' => 'ပါဆယ် ပို့ချိန် ညနေ ၅ နာရီ ရပါမလား?', 'minutes' => 45, 'read' => false],
                    ['sender_type' => 'admin', 'message' => 'ရပါမယ်။ လိပ်စာ အတည်ပြုပေးပါ။', 'minutes' => 30, 'read' => true],
                    ['sender_type' => 'client', 'message' => '74 Street / between 28 & 29 ပါ။', 'minutes' => 8, 'read' => false],
                ],
            ],
            [
                'item_id' => 193,
                'messages' => [
                    ['sender_type' => 'client', 'message' => 'Item value ကို ပြင်ပေးပါ။', 'minutes' => 120, 'read' => false],
                ],
            ],
            [
                'item_id' => 192,
                'messages' => [
                    ['sender_type' => 'client', 'message' => 'Delivery delay ရှိပါသလား?', 'minutes' => 200, 'read' => true],
                    ['sender_type' => 'client', 'message' => 'ဖုန်းမကိုင်လို့ ထပ်ခေါ်ပေးပါ။', 'minutes' => 90, 'read' => true],
                ],
            ],
            [
                'item_id' => 191,
                'messages' => [
                    ['sender_type' => 'client', 'message' => 'Address မှားနေပါတယ်။', 'minutes' => 400, 'read' => true],
                    ['sender_type' => 'admin', 'message' => 'ပြင်ပြီးပါပြီ။ Rider ထံ အကြောင်းကြားပြီးပါပြီ။', 'minutes' => 350, 'read' => true],
                ],
            ],
            [
                'item_id' => 190,
                'messages' => [
                    ['sender_type' => 'client', 'message' => 'ပါဆယ် ပြန်ယူမလား?', 'minutes' => 500, 'read' => true],
                    ['sender_type' => 'admin', 'message' => 'OS Return လုပ်ပြီးပါပြီ။', 'minutes' => 480, 'read' => true],
                ],
            ],
        ];

        foreach ($threads as $thread) {
            $item = DispatchOrderItem::query()->with('order')->find($thread['item_id']);
            if (! $item || ! $item->order) {
                continue;
            }

            DispatchItemMessage::query()
                ->where('dispatch_order_item_id', $item->id)
                ->delete();

            $clientId = (int) $item->order->client_id;

            foreach ($thread['messages'] as $message) {
                $created = $now->copy()->subMinutes((int) $message['minutes']);

                DispatchItemMessage::query()->create([
                    'dispatch_order_item_id' => $item->id,
                    'order_id' => $item->order_id,
                    'client_id' => $clientId,
                    'sender_id' => $message['sender_type'] === 'client' ? $clientId : $adminId,
                    'sender_type' => $message['sender_type'],
                    'message_type' => 'text',
                    'message' => $message['message'],
                    'read_at' => $message['read'] ? $created->copy()->addMinutes(2) : null,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]);
            }
        }
    }
}

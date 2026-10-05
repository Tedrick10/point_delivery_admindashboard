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

        $items = DispatchOrderItem::query()
            ->with('order')
            ->whereNotNull('order_id')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->filter(fn (DispatchOrderItem $item) => (bool) $item->order)
            ->values();

        if ($items->isEmpty()) {
            $this->command?->warn('No dispatch items found for Message demo.');

            return;
        }

        $clientLines = [
            'ပါဆယ် ပို့ချိန် ညနေ ၅ နာရီ ရပါမလား?',
            'Item value ကို ပြင်ပေးပါ။',
            'Delivery delay ရှိပါသလား?',
            'Address မှားနေပါတယ်။',
            'ပါဆယ် ပြန်ယူမလား?',
            'ဖုန်းမကိုင်လို့ ထပ်ခေါ်ပေးပါ။',
            'OS ငွေ ဘယ်တော့ ပေးမလဲ?',
            'စာရင်း ပြန်စစ်ပေးပါ။',
        ];
        $adminLines = [
            'ရပါမယ်။ လိပ်စာ အတည်ပြုပေးပါ။',
            'ပြင်ပြီးပါပြီ။ Rider ထံ အကြောင်းကြားပြီးပါပြီ။',
            'OS Return လုပ်ပြီးပါပြီ။',
            'နောက်ဆုံးအခြေအနေ စစ်ပြီး အကြောင်းပြန်ပါမယ်။',
        ];

        $created = 0;
        foreach ($items as $i => $item) {
            DispatchItemMessage::query()
                ->where('dispatch_order_item_id', $item->id)
                ->delete();

            $clientId = (int) $item->order->client_id;
            $createdAt = $now->copy()->subMinutes(20 + ($i * 7));

            DispatchItemMessage::query()->create([
                'dispatch_order_item_id' => $item->id,
                'order_id' => $item->order_id,
                'client_id' => $clientId,
                'sender_id' => $clientId,
                'sender_type' => 'client',
                'message_type' => 'text',
                'message' => $clientLines[$i % count($clientLines)].' (Demo '.($i + 1).')',
                'read_at' => $i % 3 === 0 ? null : $createdAt->copy()->addMinutes(2),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            DispatchItemMessage::query()->create([
                'dispatch_order_item_id' => $item->id,
                'order_id' => $item->order_id,
                'client_id' => $clientId,
                'sender_id' => $adminId,
                'sender_type' => 'admin',
                'message_type' => 'text',
                'message' => $adminLines[$i % count($adminLines)],
                'read_at' => $createdAt->copy()->addMinutes(4),
                'created_at' => $createdAt->copy()->addMinutes(3),
                'updated_at' => $createdAt->copy()->addMinutes(3),
            ]);

            $created++;
        }

        $this->command?->info('Message demo: '.$created.' chat threads.');
    }
}

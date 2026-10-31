<?php

namespace Database\Seeders;

use App\Models\InquiryAutoReply;
use Illuminate\Database\Seeder;

class InquiryAutoReplySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'label' => 'ပစ္စည်းပို့ခ ဘယ်လောက်ကျမလဲ?',
                'reply_message' => "မင်္ဂလာပါ 🙏\nပို့ခသည် မြို့တွင်း / မြို့ပြင်၊ အလေးချိန်နှင့် ပစ္စည်းအမျိုးအစားပေါ် မူတည်ပါတယ်။\nပို့မည့်မြို့နဲ့ ပစ္စည်းအလေးချိန်ကို ပြောပေးပါက စျေးနှုန်း ခန့်မှန်းတွက်ပေးပါမည်။",
                'sort_order' => 1,
            ],
            [
                'label' => 'ဘယ်အချိန်လောက်ရောက်မလဲ?',
                'reply_message' => "ပို့ဆောင်ချိန်အကြောင်း အသိပေးပါမည် ⏱️\n• မြို့တွင်း — တစ်ရက်အတွင်း\n• မြို့ပြင် — ၁–၃ ရက်\nအရေးပေါ်ပို့ဆောင်မှု လိုအပ်ပါက ပြောပေးပါ၊ ကူညီပေးပါမည်။",
                'sort_order' => 2,
            ],
            [
                'label' => 'Tracking ဘယ်လိုကြည့်ရမလဲ?',
                'reply_message' => "Tracking ကြည့်နည်း 📦\nHome စာမျက်နှာက Tracking ရှာမယ် နေရာမှာ Tracking Number ထည့်ပြီး ရှာနိုင်ပါတယ်။\nTracking No. မရှိသေးရင် Order အသေးစိတ်ထဲမှာ ကြည့်နိုင်ပါတယ်။",
                'sort_order' => 3,
            ],
            [
                'label' => 'COD (ပစ္စည်းရောက်မှပေး) လုပ်လို့ရလား?',
                'reply_message' => "ဟုတ်ပါတယ် ✅\nCOD (Cash on Delivery) လက်ခံပါတယ်။ ပစ္စည်းလက်ခံသည့်အခါ ငွေပေးချေနိုင်ပါတယ်။\nCOD ပမာဏနှင့် လက်ခံသူအချက်အလက်ကို ပြည့်စုံရေးပေးပါ။",
                'sort_order' => 4,
            ],
            [
                'label' => 'ဆိုင်လိပ်စာ / ဆက်သွယ်ရန်',
                'reply_message' => "ဆက်သွယ်ရန် အချက်အလက် 📍\nPoint Delivery Services\nဖုန်း — Admin ထံမှ အတည်ပြုပေးပါမည်\nလုပ်ငန်းချိန် — မနက် ၉ နာရီ မှ ညနေ ၆ နာရီ\nမြို့/ရပ်ကွက် ပြောပေးပါက အနီးဆုံးဆိုင်လိပ်စာ ပေးပို့ပါမည်။",
                'sort_order' => 5,
            ],
            [
                'label' => 'ပစ္စည်းပျက်စီး / ပျောက်ဆုံးရင် ဘယ်လိုလုပ်မလဲ?',
                'reply_message' => "စိတ်မကောင်းပါဘူး 🙏\nပျက်စီး / ပျောက်ဆုံးမှုရှိပါက Tracking No. + ဓာတ်ပုံများ ပေးပို့ပေးပါ။\nAdmin က စစ်ဆေးပြီး လျော်ကြေး / ပြန်ပို့ဆောင်မှု ကူညီပေးပါမည်။",
                'sort_order' => 6,
            ],
            [
                'label' => 'Wallet ထဲငွေ ဘယ်လိုထည့်ရမလဲ?',
                'reply_message' => "Wallet Top-up လုပ်နည်း 💳\nApp ထဲက Wallet စာမျက်နှာမှ Top-up လုပ်နိုင်ပါတယ်။\nငွေလွှဲပြီးပါက Screenshot ပို့ပေးပါက Admin က အတည်ပြုပေးပါမည်။",
                'sort_order' => 7,
            ],
            [
                'label' => 'Same Day Delivery ရပါသလား?',
                'reply_message' => "Same Day Delivery ရပါတယ် 🚚\nသတ်မှတ်ချိန်မတိုင်မီ Order တင်မှသာ တစ်ရက်အတွင်း ပို့ပေးနိုင်ပါတယ်။\nသင့်မြို့မှာ Same Day ရ/မရ ကို မြို့နာမည်ပြောပေးပါက စစ်ပေးပါမည်။",
                'sort_order' => 8,
            ],
            [
                'label' => 'Discount Store မှာ ဘယ်ဖုန်းတွေရရှိနိုင်ပါလဲ?',
                'reply_message' => "မင်္ဂလာပါ 🙏\nDiscount Store မှာ ရရှိနိုင်သော ဖုန်းမော်ဒယ်များနှင့် စျေးနှုန်းများကို အောက်ပါအတိုင်း စုံစမ်းပေးပါမည်။\nလိုချင်သော မော်ဒယ် / ဘတ်ဂျက်ကို ပြောပေးပါ။",
                'sort_order' => 9,
            ],
            [
                'label' => 'Admin နဲ့ တိုက်ရိုက်စကားပြောချင်ပါတယ်',
                'reply_message' => "ကျေးဇူးတင်ပါတယ်။\nAdmin က မကြာမီ ပြန်လည်ဖြေကြားပေးပါမည်။\nမေးလိုသော အကြောင်းအရာကို အသေးစိတ် ရေးပေးထားနိုင်ပါတယ်။",
                'sort_order' => 10,
            ],
        ];

        foreach ($rows as $row) {
            InquiryAutoReply::updateOrCreate(
                ['label' => $row['label']],
                array_merge($row, ['is_active' => true])
            );
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $cities = DB::table('delivery_cities')->pluck('id', 'name');

        foreach ($this->townshipsByCity() as $cityName => $townships) {
            $cityId = (int) ($cities[$cityName] ?? 0);
            if ($cityId <= 0) {
                continue;
            }

            $existing = DB::table('delivery_townships')
                ->where('delivery_city_id', $cityId)
                ->pluck('name')
                ->map(fn ($name) => mb_strtolower(trim((string) $name)))
                ->all();

            $sort = (int) DB::table('delivery_townships')->where('delivery_city_id', $cityId)->max('sort_order');

            foreach ($townships as $name) {
                $name = trim($name);
                if ($name === '' || in_array(mb_strtolower($name), $existing, true)) {
                    continue;
                }

                $sort++;
                DB::table('delivery_townships')->insert([
                    'delivery_city_id' => $cityId,
                    'name' => $name,
                    'name_mm' => $name,
                    'sort_order' => $sort,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $existing[] = mb_strtolower($name);
            }
        }
    }

    public function down(): void
    {
        // Keep manually added townships; this seed is additive.
    }

    /**
     * @return array<string, list<string>>
     */
    private function townshipsByCity(): array
    {
        return [
            'Mandalay' => [
                'ချမ်းမြသာစည်',
                'ချမ်းအေးသာဇံ',
                'မဟာအောင်မြေ',
                'အောင်မြေသာဇံ',
                'ပြည်ကြီးတံခွန်',
                'အမရပူရ',
                'ပုသိမ်ကြီး',
            ],
            'Yangon' => [
                'အလုံ',
                'ဗဟန်း',
                'ဗိုလ်တထောင်',
                'ဒဂုံ',
                'ဒဂုံမြို့သစ် (အရှေ့ပိုင်း)',
                'ဒဂုံမြို့သစ် (ဆိပ်ကမ်း)',
                'ဒဂုံမြို့သစ်(မြောက်ပိုင်း)',
                'ဒဂုံမြို့သစ်(တောင်ပိုင်း)',
                'ဒလ',
                'ဒေါပုံ',
                'လှည်းကူး',
                'လှိုင်',
                'လှိုင်သာယာ',
                'မှော်ဘီ',
                'ထန်းတပင်',
                'အင်းစိန်',
                'ကမာရွတ်',
                'ကော့မှူး',
                'ခရမ်း',
                'ကွမ်းခြံကုန်း',
                'ကျောက်တံတား',
                'ကျောက်တန်း',
                'ကြည့်မြင်တိုင်',
                'လမ်းမတော်',
                'လသာ',
                'မရမ်းကုန်း',
                'မင်္ဂလာဒုံ',
                'မင်္ဂလာတောင်ညွန့်',
                'မြောက်ဥက္ကလာပ',
                'ပန်းဘဲတန်း',
                'ပုဇွန်တောင်',
                'စမ်းချောင်း',
                'ဆိပ်ကမ်း',
                'ရွှေပြည်သာ',
                'တောင်ဥက္ကလာပ',
                'တာမွေ',
                'တိုက်ကြီး',
                'သာကေတ',
                'သန်လျင်',
                'သင်္ဃန်းကျွန်း',
                'သုံးခွ',
                'တွံတေး',
                'ရန်ကင်း',
            ],
            'Nay Pyi Taw' => [
                'ဇေယျာသီရိ',
                'ဇမ္ဗူသီရိ',
                'ပုဗ္ဗသီရိ',
                'ဥတ္တရသီရိ',
                'ဒက္ခိဏသီရိ',
                'ပျဉ်းမနား',
                'လယ်ဝေး',
                'တပ်ကုန်း',
            ],
            'Lashio' => [
                'လားရှိုး',
                'သိန္နီ',
                'သီပေါ',
                'နောင်ချို',
                'ကွတ်ခိုင်',
                'တန့်ယန်း',
                'ကျောက်မဲ',
            ],
            'Taunggyi' => [
                'တောင်ကြီး',
                'အေးသာယာ',
                'ညောင်ရွှေ',
                'ကလော',
                'ရပ်စောက်',
                'ဟိုပုံး',
                'ပင်လောင်း',
                'ရွာငံ',
            ],
            'Pyin Oo Lwin' => [
                'ပြင်ဦးလွင်',
                'မတ္တရာ',
                'စဉ့်ကူး',
                'သပိတ်ကျင်း',
                'မိုးကုတ်',
            ],
            'Monywa' => [
                'မုံရွာ',
                'ချောင်းဦး',
                'ဘုတလင်',
                'အရာတော်',
                'ယင်းမာပင်',
                'ဆားလင်းကြီး',
                'ပုလဲ',
            ],
            'Myitkyina' => [
                'မြစ်ကြီးနား',
                'ဝိုင်းမော်',
                'ဗန်းမော်',
                'မိုးကောင်း',
                'မိုးညှင်း',
                'မိုးမောက်',
                'ဖားကန့်',
            ],
            'Sagaing' => [
                'စစ်ကိုင်း',
                'မြင်းမူ',
                'မြောင်',
                'ရွှေဘို',
                'ခင်ဦး',
                'ဝက်လက်',
            ],
        ];
    }
};

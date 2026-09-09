<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_townships') && ! Schema::hasColumn('delivery_townships', 'deli_amount')) {
            Schema::table('delivery_townships', function (Blueprint $table) {
                $table->decimal('deli_amount', 12, 2)->default(0)->after('name_mm');
            });
        }

        $this->seedTownshipAmounts();
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_townships') && Schema::hasColumn('delivery_townships', 'deli_amount')) {
            Schema::table('delivery_townships', function (Blueprint $table) {
                $table->dropColumn('deli_amount');
            });
        }
    }

    private function seedTownshipAmounts(): void
    {
        if (! Schema::hasColumn('delivery_townships', 'deli_amount')) {
            return;
        }

        $now = now();
        $explicit = $this->explicitAmounts();
        $cityBases = [
            'Mandalay' => 2000,
            'Yangon' => 2500,
            'Lashio' => 4500,
            'Taunggyi' => 4000,
            'Pyin Oo Lwin' => 3000,
            'Monywa' => 3500,
            'Myitkyina' => 5000,
            'Nay Pyi Taw' => 3000,
            'Sagaing' => 2800,
        ];

        $cities = DB::table('delivery_cities')->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        foreach ($cities as $city) {
            $townships = DB::table('delivery_townships')
                ->where('delivery_city_id', $city->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'name_mm']);

            $base = (int) ($cityBases[$city->name] ?? 3000);
            foreach ($townships as $index => $township) {
                $amount = $explicit[$township->name]
                    ?? $explicit[$township->name_mm]
                    ?? ($base + ($index * 200));

                DB::table('delivery_townships')->where('id', $township->id)->update([
                    'deli_amount' => $amount,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function explicitAmounts(): array
    {
        return [
            'ချမ်းမြသာစည်' => 2000,
            'ချမ်းအေးသာဇံ' => 2200,
            'ချမ်းအေးသာစံ' => 2200,
            'မဟာအောင်မြေ' => 2500,
            'အောင်မြေသာဇံ' => 2700,
            'အောင်မြေသာစံ' => 2700,
            'ပြည်ကြီးတံခွန်' => 3000,
            'အမရပူရ' => 3500,
            'ပုသိမ်ကြီး' => 4000,
        ];
    }
};

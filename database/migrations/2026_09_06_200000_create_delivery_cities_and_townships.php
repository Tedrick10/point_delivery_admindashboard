<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_cities')) {
            Schema::create('delivery_cities', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('name_mm', 120)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamps();
                $table->unique('name');
            });
        }

        if (! Schema::hasTable('delivery_townships')) {
            Schema::create('delivery_townships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('delivery_city_id')->index();
                $table->string('name', 120);
                $table->string('name_mm', 120)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamps();
                $table->unique(['delivery_city_id', 'name']);
            });
        }

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_townships');
        Schema::dropIfExists('delivery_cities');
    }

    private function seedDefaults(): void
    {
        if (DB::table('delivery_cities')->exists()) {
            return;
        }

        $now = now();
        $cities = [
            ['name' => 'Mandalay', 'name_mm' => 'မန္တလေး'],
            ['name' => 'Yangon', 'name_mm' => 'ရန်ကုန်'],
            ['name' => 'Lashio', 'name_mm' => 'လားရှိုး'],
            ['name' => 'Taunggyi', 'name_mm' => 'တောင်ကြီး'],
            ['name' => 'Pyin Oo Lwin', 'name_mm' => 'ပြင်ဦးလွင်'],
            ['name' => 'Monywa', 'name_mm' => 'မုံရွာ'],
            ['name' => 'Myitkyina', 'name_mm' => 'မြစ်ကြီးနား'],
            ['name' => 'Nay Pyi Taw', 'name_mm' => 'နေပြည်တော်'],
            ['name' => 'Sagaing', 'name_mm' => 'စစ်ကိုင်း'],
        ];

        foreach ($cities as $index => $city) {
            DB::table('delivery_cities')->insert([
                'name' => $city['name'],
                'name_mm' => $city['name_mm'],
                'sort_order' => $index + 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $mandalayId = DB::table('delivery_cities')->where('name', 'Mandalay')->value('id');
        $yangonId = DB::table('delivery_cities')->where('name', 'Yangon')->value('id');

        $this->insertTownships((int) $mandalayId, [
            'ချမ်းမြသာစည်',
            'ချမ်းအေးသာဇံ',
            'မဟာအောင်မြေ',
            'အောင်မြေသာဇံ',
            'ပြည်ကြီးတံခွန်',
            'အမရပူရ',
            'ပုသိမ်ကြီး',
        ], $now);

        $this->insertTownships((int) $yangonId, [
            'လှိုင်',
            'ကမာရွတ်',
            'စမ်းချောင်း',
            'ဗဟန်း',
            'ရန်ကင်း',
            'မရမ်းကုန်း',
            'တောင်ဥက္ကလာပ',
        ], $now);
    }

    /**
     * @param  list<string>  $names
     */
    private function insertTownships(int $cityId, array $names, $now): void
    {
        if ($cityId <= 0) {
            return;
        }

        foreach ($names as $index => $name) {
            DB::table('delivery_townships')->insert([
                'delivery_city_id' => $cityId,
                'name' => $name,
                'name_mm' => $name,
                'sort_order' => $index + 1,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};

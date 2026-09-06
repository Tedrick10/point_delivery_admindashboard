<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'code')) {
                $table->string('code', 32)->nullable()->after('name');
            }
            if (! Schema::hasColumn('branches', 'city_name')) {
                $table->string('city_name', 100)->nullable()->after('code');
            }
            if (! Schema::hasColumn('branches', 'address')) {
                $table->string('address', 500)->nullable()->after('city_name');
            }
            if (! Schema::hasColumn('branches', 'phone')) {
                $table->string('phone', 40)->nullable()->after('address');
            }
            if (! Schema::hasColumn('branches', 'is_hub')) {
                $table->boolean('is_hub')->default(false)->after('phone');
            }
        });

        $hubs = [
            ['name' => 'MDY Branch', 'code' => 'MDY', 'city_name' => 'Mandalay'],
            ['name' => 'Yangon Branch', 'code' => 'YGN', 'city_name' => 'Yangon'],
            ['name' => 'Naypyitaw Branch', 'code' => 'NPT', 'city_name' => 'Naypyidaw'],
            ['name' => 'Taungyi Branch', 'code' => 'TGY', 'city_name' => 'Taunggyi'],
            ['name' => 'Lashio Branch', 'code' => 'LSO', 'city_name' => 'Lashio'],
        ];

        foreach ($hubs as $hub) {
            $existing = DB::table('branches')
                ->whereNull('deleted_at')
                ->where(function ($q) use ($hub) {
                    $q->where('name', $hub['name'])
                        ->orWhere('name', $hub['code'])
                        ->orWhere('name', 'like', $hub['code'].'%');
                })
                ->orderBy('id')
                ->first();

            if ($existing) {
                DB::table('branches')->where('id', $existing->id)->update([
                    'name' => $hub['name'],
                    'code' => $hub['code'],
                    'city_name' => $hub['city_name'],
                    'is_hub' => 1,
                    'status' => 1,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('branches')->insert([
                    'name' => $hub['name'],
                    'code' => $hub['code'],
                    'city_name' => $hub['city_name'],
                    'is_hub' => 1,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            foreach (['code', 'city_name', 'address', 'phone', 'is_hub'] as $col) {
                if (Schema::hasColumn('branches', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

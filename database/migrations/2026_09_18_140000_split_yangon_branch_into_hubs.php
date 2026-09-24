<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $now = now();
        $yangon = DB::table('branches')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('name', 'ရန်ကုန်')
                    ->orWhere('name', 'Yangon')
                    ->orWhere('name', 'Yangon Branch')
                    ->orWhere('code', 'YGN');
            })
            ->orderBy('id')
            ->first();

        if (! $yangon) {
            return;
        }

        $nlsId = (int) $yangon->id;
        DB::table('branches')->where('id', $nlsId)->update([
            'name' => 'Yangon Ngwe Latt Saung',
            'code' => 'YGN-NLS',
            'city_name' => 'Yangon',
            'status' => 1,
            'updated_at' => $now,
        ]);

        $m2m = DB::table('branches')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('name', 'Yangon M2M')
                    ->orWhere('code', 'YGN-M2M');
            })
            ->orderBy('id')
            ->first();

        if ($m2m) {
            $m2mId = (int) $m2m->id;
            DB::table('branches')->where('id', $m2mId)->update([
                'name' => 'Yangon M2M',
                'code' => 'YGN-M2M',
                'city_name' => 'Yangon',
                'status' => 1,
                'delivery_settlement_mode' => $m2m->delivery_settlement_mode ?? $yangon->delivery_settlement_mode ?? 'half_deli',
                'updated_at' => $now,
            ]);
        } else {
            $m2mId = (int) DB::table('branches')->insertGetId([
                'name' => 'Yangon M2M',
                'code' => 'YGN-M2M',
                'city_name' => 'Yangon',
                'status' => 1,
                'delivery_settlement_mode' => $yangon->delivery_settlement_mode ?? 'half_deli',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($m2mId <= 0 || $m2mId === $nlsId) {
            return;
        }

        $m2mHubIds = DB::table('users')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('email', 'rider.ygn2@demo.local')
                    ->orWhere('name', 'Yangon M2M')
                    ->orWhere('name', 'like', '%M2M%');
            })
            ->where('user_type', 'delivery_man')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($m2mHubIds !== []) {
            DB::table('users')->whereIn('id', $m2mHubIds)->update([
                'branch_id' => $m2mId,
                'updated_at' => $now,
            ]);

            if (Schema::hasColumn('users', 'hub_parent_id')) {
                DB::table('users')
                    ->whereIn('hub_parent_id', $m2mHubIds)
                    ->update([
                        'branch_id' => $m2mId,
                        'updated_at' => $now,
                    ]);
            }

            if (Schema::hasTable('dispatch_order_items')) {
                DB::table('dispatch_order_items')
                    ->whereIn('hub_user_id', $m2mHubIds)
                    ->where('to_branch_id', $nlsId)
                    ->update([
                        'to_branch_id' => $m2mId,
                        'updated_at' => $now,
                    ]);
            }
        }

        if (Schema::hasTable('kyo_shin_caps')) {
            $source = DB::table('kyo_shin_caps')->where('scope_key', 'branch_'.$nlsId)->first();
            $targetKey = 'branch_'.$m2mId;
            if ($source && ! DB::table('kyo_shin_caps')->where('scope_key', $targetKey)->exists()) {
                DB::table('kyo_shin_caps')->insert([
                    'scope_key' => $targetKey,
                    'total_amount' => $source->total_amount ?? 0,
                    'cash_on_hand' => $source->cash_on_hand ?? null,
                    'returned_amount' => $source->returned_amount ?? null,
                    'updated_by' => $source->updated_by ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $nls = DB::table('branches')->whereNull('deleted_at')->where('name', 'Yangon Ngwe Latt Saung')->first();
        $m2m = DB::table('branches')->whereNull('deleted_at')->where('name', 'Yangon M2M')->first();
        if (! $nls || ! $m2m) {
            return;
        }

        $nlsId = (int) $nls->id;
        $m2mId = (int) $m2m->id;
        $now = now();

        if (Schema::hasTable('dispatch_order_items')) {
            DB::table('dispatch_order_items')->where('to_branch_id', $m2mId)->update([
                'to_branch_id' => $nlsId,
                'updated_at' => $now,
            ]);
            DB::table('dispatch_order_items')->where('from_branch_id', $m2mId)->update([
                'from_branch_id' => $nlsId,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->where('branch_id', $m2mId)->update([
                'branch_id' => $nlsId,
                'updated_at' => $now,
            ]);
        }

        DB::table('branches')->where('id', $nlsId)->update([
            'name' => 'ရန်ကုန်',
            'code' => 'YGN',
            'city_name' => 'ရန်ကုန်',
            'updated_at' => $now,
        ]);
    }
};

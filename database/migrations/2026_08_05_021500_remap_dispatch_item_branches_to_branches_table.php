<?php

use App\Models\Branch;
use App\Models\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TEMP_OFFSET = 900000000;

    public function up(): void
    {
        if (! Schema::hasTable('branches') || ! Schema::hasTable('dispatch_order_items')) {
            return;
        }

        $cityIds = DB::table('dispatch_order_items')
            ->whereNotNull('from_branch_id')
            ->pluck('from_branch_id')
            ->merge(
                DB::table('dispatch_order_items')
                    ->whereNotNull('to_branch_id')
                    ->pluck('to_branch_id')
            )
            ->unique()
            ->filter(fn ($id) => (int) $id > 0 && (int) $id < self::TEMP_OFFSET)
            ->values();

        $map = [];
        foreach ($cityIds as $cityId) {
            $city = City::withTrashed()->find($cityId);
            $name = trim((string) ($city?->name ?? ''));
            if ($name === '') {
                continue;
            }

            $branch = Branch::withTrashed()->firstOrCreate(
                ['name' => $name],
                ['status' => 1]
            );
            if ($branch->trashed()) {
                $branch->restore();
            }
            if ((int) $branch->status !== 1) {
                $branch->update(['status' => 1]);
            }
            $map[(int) $cityId] = (int) $branch->id;
        }

        if ($map === []) {
            return;
        }

        // Two-phase remap avoids collisions when old city ids overlap new branch ids.
        foreach ($map as $oldId => $newId) {
            if ($oldId === $newId) {
                continue;
            }
            $tempId = self::TEMP_OFFSET + (int) $oldId;
            DB::table('dispatch_order_items')->where('from_branch_id', $oldId)->update(['from_branch_id' => $tempId]);
            DB::table('dispatch_order_items')->where('to_branch_id', $oldId)->update(['to_branch_id' => $tempId]);
        }

        foreach ($map as $oldId => $newId) {
            if ($oldId === $newId) {
                continue;
            }
            $tempId = self::TEMP_OFFSET + (int) $oldId;
            DB::table('dispatch_order_items')->where('from_branch_id', $tempId)->update(['from_branch_id' => $newId]);
            DB::table('dispatch_order_items')->where('to_branch_id', $tempId)->update(['to_branch_id' => $newId]);
        }
    }

    public function down(): void
    {
        // Irreversible remap of branch FKs.
    }
};

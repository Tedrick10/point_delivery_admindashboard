<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mdyBranchId = (int) (DB::table('branches')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('name', 'MDY Branch')
                    ->orWhere('name', 'like', '%MDY%');
            })
            ->orderBy('id')
            ->value('id') ?? 0);

        if ($mdyBranchId <= 0) {
            $mdyBranchId = (int) (DB::table('branches')
                ->whereNull('deleted_at')
                ->where('status', 1)
                ->orderBy('id')
                ->value('id') ?? 0);
        }

        if ($mdyBranchId <= 0) {
            return;
        }

        DB::table('users')
            ->where('user_type', 'delivery_man')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('branch_id')->orWhere('branch_id', 0);
            })
            ->update([
                'branch_id' => $mdyBranchId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Non-destructive: branch assignments are kept on rollback.
    }
};

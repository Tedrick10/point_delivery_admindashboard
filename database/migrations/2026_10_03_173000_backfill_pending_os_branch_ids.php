<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (! function_exists('destinationBranchIdFromOsLocation')) {
            return;
        }

        User::query()
            ->where('user_type', 'client')
            ->where(function ($q) {
                $q->whereNull('branch_id')->orWhere('branch_id', 0);
            })
            ->orderBy('id')
            ->each(function (User $user) {
                $profile = is_array($user->os_profile) ? $user->os_profile : [];
                $branchId = destinationBranchIdFromOsLocation(
                    $profile['state_division'] ?? null,
                    $profile['township'] ?? null
                );
                if (! $branchId) {
                    return;
                }
                $user->forceFill(['branch_id' => $branchId])->saveQuietly();
            });
    }

    public function down(): void
    {
        // Mapping is derived; do not unset production branch_id.
    }
};

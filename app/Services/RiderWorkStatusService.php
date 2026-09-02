<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class RiderWorkStatusService
{
    public function resetExpiredOffRiders(): int
    {
        if (! Schema::hasColumn('users', 'rider_work_on')) {
            return 0;
        }

        $today = now('Asia/Yangon')->toDateString();
        $cacheKey = 'riders.work_on.reset.'.$today;
        if (Cache::get($cacheKey)) {
            return 0;
        }

        $updated = $this->resetQuery($today);
        Cache::put($cacheKey, true, now('Asia/Yangon')->endOfDay());

        return $updated;
    }

    /**
     * Turn Off (rest day) back to On when the calendar day rolls over (12:01 AM Yangon).
     * Applies to riders and office employees (Account Creation / Employee List).
     */
    public function resetQuery(string $today): int
    {
        $query = User::query()
            ->whereNull('deleted_at')
            ->where('rider_work_on', false)
            ->where(function ($q) {
                $q->where('user_type', 'delivery_man')
                    ->orWhereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin']);
            });

        if (Schema::hasColumn('users', 'rider_work_off_date')) {
            $query->where(function ($inner) use ($today) {
                $inner->whereNull('rider_work_off_date')
                    ->orWhereDate('rider_work_off_date', '<', $today);
            });
        }

        $payload = ['rider_work_on' => true];
        if (Schema::hasColumn('users', 'rider_work_off_date')) {
            $payload['rider_work_off_date'] = null;
        }

        return $query->update($payload);
    }

    /**
     * Rider Remit Submit → pending "Off for today" becomes actual Off.
     *
     * @param  array<int, int>  $riderIds
     */
    public function applyPendingOffAfterSubmit(array $riderIds): int
    {
        if (! Schema::hasColumn('users', 'rider_work_on')) {
            return 0;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $riderIds))));
        if ($ids === []) {
            return 0;
        }

        $today = now('Asia/Yangon')->toDateString();
        $query = User::query()
            ->where('user_type', 'delivery_man')
            ->whereNull('deleted_at')
            ->whereIn('id', $ids);

        if (Schema::hasColumn('users', 'rider_work_off_date')) {
            $query->whereDate('rider_work_off_date', $today);
        }

        return $query->update(['rider_work_on' => false]);
    }
}

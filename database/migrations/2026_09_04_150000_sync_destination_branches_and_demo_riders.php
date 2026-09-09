<?php

use App\Models\Branch;
use App\Models\DispatchOrderItem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Destination branches used for From / To + Assign 100 tabs.
     *
     * @return list<array{name:string,code:?string,city_name:string}>
     */
    private function destinations(): array
    {
        return [
            ['name' => 'Food(မန္တလေး)', 'code' => 'FOOD', 'city_name' => 'Mandalay'],
            ['name' => 'မန္တလေး', 'code' => 'MDY', 'city_name' => 'Mandalay'],
            ['name' => 'ရန်ကုန်', 'code' => 'YGN', 'city_name' => 'Yangon'],
            ['name' => 'လားရှိုး', 'code' => 'LSO', 'city_name' => 'Lashio'],
            ['name' => 'တောင်ကြီး', 'code' => 'TGY', 'city_name' => 'Taunggyi'],
            ['name' => 'ပြင်ဦးလွင်', 'code' => 'POL', 'city_name' => 'Pyin Oo Lwin'],
        ];
    }

    public function up(): void
    {
        // Rename unique legacy names first (avoid unique collisions).
        $uniqueRenames = [
            'Food' => 'Food(မန္တလေး)',
            'MDY To MDY' => 'မန္တလေး',
            'Yangon Branch' => 'ရန်ကုန်',
            'Lashio Branch' => 'လားရှိုး',
            'Taungyi Branch' => 'တောင်ကြီး',
        ];

        foreach ($uniqueRenames as $from => $to) {
            $existsTarget = Branch::withTrashed()->where('name', $to)->exists();
            $source = Branch::withTrashed()->where('name', $from)->first();
            if (! $source) {
                continue;
            }
            if ($existsTarget) {
                // Target already exists — remap then remove source.
                $targetId = (int) Branch::withTrashed()->where('name', $to)->orderBy('id')->value('id');
                $this->remapBranchId((int) $source->id, $targetId);
                if (! $source->trashed()) {
                    $source->delete();
                }
                continue;
            }
            $source->fill([
                'name' => $to,
                'status' => 1,
                'deleted_at' => null,
            ])->save();
        }

        // Duplicates that collide with already-renamed targets → remap + soft-delete.
        foreach ([
            'MDY Branch' => 'မန္တလေး',
            'Ygn to Ygn' => 'ရန်ကုန်',
        ] as $from => $to) {
            $source = Branch::withTrashed()->where('name', $from)->first();
            $target = Branch::withTrashed()->where('name', $to)->orderBy('id')->first();
            if (! $source || ! $target) {
                continue;
            }
            $this->remapBranchId((int) $source->id, (int) $target->id);
            if (! $source->trashed()) {
                $source->delete();
            }
        }

        foreach ($this->destinations() as $row) {
            $branch = Branch::withTrashed()->where('name', $row['name'])->first();
            if ($branch) {
                $branch->fill([
                    'code' => $row['code'],
                    'city_name' => $row['city_name'],
                    'status' => 1,
                    'deleted_at' => null,
                ])->save();
            } else {
                Branch::create([
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'city_name' => $row['city_name'],
                    'status' => 1,
                ]);
            }
        }

        $keepIds = Branch::query()
            ->whereIn('name', collect($this->destinations())->pluck('name')->all())
            ->pluck('id', 'name');

        $fallbackId = (int) ($keepIds['မန္တလေး'] ?? $keepIds->first());

        $extras = Branch::withTrashed()
            ->whereNotIn('name', $keepIds->keys()->all())
            ->get();

        foreach ($extras as $extra) {
            $targetId = $fallbackId;
            $city = strtolower((string) ($extra->city_name ?? ''));
            $name = strtolower((string) $extra->name);
            if (str_contains($city, 'yangon') || str_contains($name, 'ygn') || str_contains($name, 'yangon')) {
                $targetId = (int) ($keepIds['ရန်ကုန်'] ?? $fallbackId);
            } elseif (str_contains($city, 'lashio') || str_contains($name, 'lashio')) {
                $targetId = (int) ($keepIds['လားရှိုး'] ?? $fallbackId);
            } elseif (str_contains($city, 'taung') || str_contains($name, 'taung')) {
                $targetId = (int) ($keepIds['တောင်ကြီး'] ?? $fallbackId);
            } elseif (str_contains($city, 'pyin') || str_contains($name, 'pyin')) {
                $targetId = (int) ($keepIds['ပြင်ဦးလွင်'] ?? $fallbackId);
            } elseif (str_contains($name, 'food')) {
                $targetId = (int) ($keepIds['Food(မန္တလေး)'] ?? $fallbackId);
            }

            $this->remapBranchId((int) $extra->id, $targetId);
            if (! $extra->trashed()) {
                $extra->delete();
            }
        }

        // Deduplicate destination names (keep lowest id).
        foreach ($keepIds->keys() as $name) {
            $ids = Branch::withTrashed()->where('name', $name)->orderBy('id')->pluck('id');
            if ($ids->count() <= 1) {
                continue;
            }
            $keep = (int) $ids->first();
            foreach ($ids->slice(1) as $dupId) {
                $this->remapBranchId((int) $dupId, $keep);
                Branch::withTrashed()->where('id', $dupId)->update([
                    'name' => $name.'__dup_'.$dupId,
                    'deleted_at' => now(),
                    'status' => 0,
                ]);
            }
        }

        $this->seedDemoRiders(
            Branch::query()
                ->whereIn('name', collect($this->destinations())->pluck('name')->all())
                ->pluck('id', 'name')
                ->all()
        );
    }

    private function remapBranchId(int $fromId, int $toId): void
    {
        if ($fromId <= 0 || $toId <= 0 || $fromId === $toId) {
            return;
        }

        if (Schema::hasTable('dispatch_order_items')) {
            DispatchOrderItem::withTrashed()
                ->where('from_branch_id', $fromId)
                ->update(['from_branch_id' => $toId]);
            DispatchOrderItem::withTrashed()
                ->where('to_branch_id', $fromId)
                ->update(['to_branch_id' => $toId]);
        }

        User::withTrashed()
            ->where('branch_id', $fromId)
            ->update(['branch_id' => $toId]);
    }

    /**
     * @param  array<string,int>  $branchIds
     */
    private function seedDemoRiders(array $branchIds): void
    {
        $demos = [
            ['branch' => 'Food(မန္တလေး)', 'name' => 'Food Rider 1', 'email' => 'rider.food1@demo.local', 'phone' => '+95911110001'],
            ['branch' => 'Food(မန္တလေး)', 'name' => 'Food Rider 2', 'email' => 'rider.food2@demo.local', 'phone' => '+95911110002'],
            ['branch' => 'မန္တလေး', 'name' => 'Aung Aung', 'email' => 'rider.demo1@demo.local', 'phone' => '+95911100001'],
            ['branch' => 'မန္တလေး', 'name' => 'Hla Hla', 'email' => 'rider.demo2@demo.local', 'phone' => '+95911100002'],
            ['branch' => 'မန္တလေး', 'name' => 'Ko Ko', 'email' => 'rider.demo3@demo.local', 'phone' => '+95911100003'],
            ['branch' => 'ရန်ကုန်', 'name' => 'Yangon Ngwe Latt Saung', 'email' => 'rider.ygn1@demo.local', 'phone' => '+95922220001'],
            ['branch' => 'ရန်ကုန်', 'name' => 'Yangon M2M', 'email' => 'rider.ygn2@demo.local', 'phone' => '+95922220002'],
            ['branch' => 'လားရှိုး', 'name' => 'LSO Rider 1', 'email' => 'rider.lso1@demo.local', 'phone' => '+95933330001'],
            ['branch' => 'လားရှိုး', 'name' => 'LSO Rider 2', 'email' => 'rider.lso2@demo.local', 'phone' => '+95933330002'],
            ['branch' => 'တောင်ကြီး', 'name' => 'TGY Rider 1', 'email' => 'rider.tgy1@demo.local', 'phone' => '+95944440001'],
            ['branch' => 'တောင်ကြီး', 'name' => 'TGY Rider 2', 'email' => 'rider.tgy2@demo.local', 'phone' => '+95944440002'],
            ['branch' => 'ပြင်ဦးလွင်', 'name' => 'POL Rider 1', 'email' => 'rider.pol1@demo.local', 'phone' => '+95955550001'],
            ['branch' => 'ပြင်ဦးလွင်', 'name' => 'POL Rider 2', 'email' => 'rider.pol2@demo.local', 'phone' => '+95955550002'],
        ];

        $now = now();
        foreach ($demos as $demo) {
            $branchId = (int) ($branchIds[$demo['branch']] ?? 0);
            if ($branchId <= 0) {
                continue;
            }

            $user = User::withTrashed()->where('email', $demo['email'])->first();
            $payload = [
                'name' => $demo['name'],
                'username' => strstr($demo['email'], '@', true) ?: $demo['email'],
                'contact_number' => $demo['phone'],
                'user_type' => 'delivery_man',
                'status' => 1,
                'branch_id' => $branchId,
                'country_id' => 1,
                'city_id' => 1,
                'email_verified_at' => $now,
                'otp_verify_at' => $now,
                'document_verified_at' => $now,
                'is_autoverified_email' => 1,
                'is_autoverified_mobile' => 1,
                'is_autoverified_document' => 1,
                'deleted_at' => null,
            ];

            if (Schema::hasColumn('users', 'is_dispatch_hub')) {
                $payload['is_dispatch_hub'] = in_array($demo['email'], [
                    'rider.ygn1@demo.local',
                    'rider.ygn2@demo.local',
                ], true) ? 1 : 0;
            }

            if ($user) {
                $user->fill($payload)->save();
            } else {
                $payload['email'] = $demo['email'];
                $payload['password'] = Hash::make('12345678');
                User::create($payload);
            }
        }

        // Move leftover demo riders onto မန္တလေး if still on a deleted branch.
        $mdyId = (int) ($branchIds['မန္တလေး'] ?? 0);
        if ($mdyId > 0) {
            User::query()
                ->where('user_type', 'delivery_man')
                ->where('email', 'like', 'rider.demo%@demo.local')
                ->where(function ($q) use ($mdyId) {
                    $q->whereNull('branch_id')->orWhere('branch_id', '!=', $mdyId);
                })
                ->whereIn('email', [
                    'rider.demo1@demo.local',
                    'rider.demo2@demo.local',
                    'rider.demo3@demo.local',
                    'rider.demo5@demo.local',
                    'rider.demo6@demo.local',
                ])
                ->update(['branch_id' => $mdyId]);
        }
    }

    public function down(): void
    {
        // Irreversible rename/seed — leave data as-is.
    }
};

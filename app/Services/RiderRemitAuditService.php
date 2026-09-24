<?php

namespace App\Services;

use App\Models\RiderRemit;
use App\Models\RiderRemitLog;
use App\Models\User;
use Illuminate\Support\Collection;

class RiderRemitAuditService
{
    public function logSaveChanges(
        string $day,
        int $branchId,
        int $riderId,
        int $actorId,
        ?RiderRemit $before,
        RiderRemit $after
    ): void {
        $actorName = $this->actorName($actorId);
        $riderName = $this->riderName($riderId, $after);

        foreach ($this->changedFields($before, $after) as $change) {
            RiderRemitLog::query()->create([
                'remit_date' => $day,
                'branch_id' => $branchId,
                'delivery_man_id' => $riderId,
                'actor_id' => $actorId,
                'actor_name' => $actorName,
                'rider_name' => $riderName,
                'action' => RiderRemitLog::ACTION_TYPED,
                'field' => $change['field'],
                'old_value' => $change['old'],
                'new_value' => $change['new'],
                'message' => __('message.rider_remit_audit_typed', [
                    'actor' => $actorName,
                    'rider' => $riderName,
                    'field' => $change['label'],
                    'old' => $change['old'],
                    'new' => $change['new'],
                ]),
            ]);
        }
    }

    public function logSubmitted(string $day, int $branchId, int $actorId, int $riderCount = 0): void
    {
        $actorName = $this->actorName($actorId);

        RiderRemitLog::query()->create([
            'remit_date' => $day,
            'branch_id' => $branchId,
            'delivery_man_id' => null,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'rider_name' => null,
            'action' => RiderRemitLog::ACTION_SUBMITTED,
            'field' => null,
            'old_value' => null,
            'new_value' => (string) $riderCount,
            'message' => __('message.rider_remit_audit_submitted', [
                'actor' => $actorName,
                'count' => $riderCount,
            ]),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(string $day, int $branchId): Collection
    {
        return RiderRemitLog::query()
            ->whereDate('remit_date', $day)
            ->where('branch_id', $branchId)
            ->orderBy('id')
            ->limit(400)
            ->get()
            ->map(function (RiderRemitLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor' => $log->actor_name ?: '-',
                    'rider' => $log->rider_name,
                    'field' => $log->field,
                    'field_label' => $this->fieldLabel($log->field),
                    'old_value' => $log->old_value,
                    'new_value' => $log->new_value,
                    'message' => $log->message,
                    'time' => optional($log->created_at)?->timezone('Asia/Yangon')->format('d-m-Y h:i A'),
                ];
            })
            ->values();
    }

    /**
     * @return list<array{field:string,label:string,old:string,new:string}>
     */
    protected function changedFields(?RiderRemit $before, RiderRemit $after): array
    {
        $changes = [];
        $moneyFields = [
            'prepaid_amount' => __('message.rider_remit_prepaid'),
            'fuel_amount' => __('message.rider_remit_fuel'),
            'fee_amount' => __('message.rider_remit_fee'),
            'kpay_amount' => 'Kpay',
            'kyo_shin_incharge_amount' => __('message.rider_remit_kyo_shin_incharge'),
        ];

        foreach ($moneyFields as $field => $label) {
            $old = $this->money((float) ($before?->{$field} ?? 0));
            $new = $this->money((float) ($after->{$field} ?? 0));
            if ($old === $new) {
                continue;
            }
            $changes[] = [
                'field' => $field,
                'label' => $label,
                'old' => $old,
                'new' => $new,
            ];
        }

        $oldDenoms = $this->denomsMap($before?->denominations);
        $newDenoms = $this->denomsMap($after->denominations);
        foreach (RiderRemit::DENOMS as $note) {
            $key = (string) $note;
            $old = (int) ($oldDenoms[$key] ?? 0);
            $new = (int) ($newDenoms[$key] ?? 0);
            if ($old === $new) {
                continue;
            }
            $changes[] = [
                'field' => 'denom_'.$note,
                'label' => number_format($note),
                'old' => (string) $old,
                'new' => (string) $new,
            ];
        }

        return $changes;
    }

    /**
     * @param  mixed  $denoms
     * @return array<string, int>
     */
    protected function denomsMap($denoms): array
    {
        $map = [];
        foreach (is_array($denoms) ? $denoms : [] as $note => $count) {
            $map[(string) $note] = (int) $count;
        }

        return $map;
    }

    protected function fieldLabel(?string $field): ?string
    {
        return match ($field) {
            'prepaid_amount' => __('message.rider_remit_prepaid'),
            'fuel_amount' => __('message.rider_remit_fuel'),
            'fee_amount' => __('message.rider_remit_fee'),
            'kpay_amount' => 'Kpay',
            'kyo_shin_incharge_amount' => __('message.rider_remit_kyo_shin_incharge'),
            default => $field && str_starts_with($field, 'denom_')
                ? number_format((int) substr($field, 6))
                : $field,
        };
    }

    protected function money(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }

    protected function actorName(int $actorId): string
    {
        $name = trim((string) (User::query()->where('id', $actorId)->value('name') ?? ''));

        return $name !== '' ? $name : __('message.rider_remit_audit_unknown_user');
    }

    protected function riderName(int $riderId, ?RiderRemit $row): string
    {
        $name = trim((string) ($row?->deliveryMan?->name ?? ''));
        if ($name === '') {
            $name = trim((string) (User::query()->where('id', $riderId)->value('name') ?? ''));
        }

        return $name !== '' ? $name : ('#'.$riderId);
    }
}

<?php

namespace App\Services;

use App\Models\PointHistory;
use App\Models\PointWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PointWalletService
{
    public function getOrCreateWallet(int $userId): PointWallet
    {
        return PointWallet::firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0]
        );
    }

    public function balance(int $userId): float
    {
        return (float) $this->getOrCreateWallet($userId)->balance;
    }

    /**
     * Manual credit by Super Admin only. Points are NOT earned from orders.
     */
    public function credit(
        int $userId,
        float $amount,
        string $transactionType = 'admin_credit',
        ?int $createdBy = null,
        ?string $description = null,
        array $extra = []
    ): PointHistory {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Point amount must be greater than zero.');
        }

        return $this->apply($userId, 'credit', $amount, $transactionType, $createdBy, $description, $extra);
    }

    public function debit(
        int $userId,
        float $amount,
        string $transactionType,
        ?int $createdBy = null,
        ?string $description = null,
        array $extra = []
    ): PointHistory {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Point amount must be greater than zero.');
        }

        $wallet = $this->getOrCreateWallet($userId);
        if ((float) $wallet->balance < $amount) {
            throw new InvalidArgumentException(__('message.point_insufficient_balance'));
        }

        return $this->apply($userId, 'debit', $amount, $transactionType, $createdBy, $description, $extra);
    }

    protected function apply(
        int $userId,
        string $type,
        float $amount,
        string $transactionType,
        ?int $createdBy,
        ?string $description,
        array $extra
    ): PointHistory {
        return DB::transaction(function () use ($userId, $type, $amount, $transactionType, $createdBy, $description, $extra) {
            $wallet = PointWallet::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = PointWallet::create(['user_id' => $userId, 'balance' => 0]);
                $wallet = PointWallet::query()->where('id', $wallet->id)->lockForUpdate()->first();
            }

            $balance = (float) $wallet->balance;
            if ($type === 'credit') {
                $balance += $amount;
            } else {
                if ($balance < $amount) {
                    throw new InvalidArgumentException(__('message.point_insufficient_balance'));
                }
                $balance -= $amount;
            }

            $wallet->balance = round($balance, 2);
            $wallet->save();

            return PointHistory::create([
                'user_id' => $userId,
                'type' => $type,
                'transaction_type' => $transactionType,
                'amount' => round($amount, 2),
                'balance' => $wallet->balance,
                'order_id' => $extra['order_id'] ?? null,
                'reference_id' => $extra['reference_id'] ?? null,
                'reference_type' => $extra['reference_type'] ?? null,
                'created_by' => $createdBy,
                'description' => $description,
                'data' => $extra['data'] ?? null,
            ]);
        });
    }

    public function assertEligibleRecipient(User $user): void
    {
        $allowed = ['client', 'delivery_man', 'admin'];
        $type = (string) ($user->user_type ?? '');

        if (in_array($type, $allowed, true)) {
            return;
        }

        // Staff linked via HR
        if ($user->hrStaff()->exists()) {
            return;
        }

        throw new InvalidArgumentException(__('message.point_recipient_not_allowed'));
    }
}

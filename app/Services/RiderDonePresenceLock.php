<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Per-order presence lock for Rider Done Order Detail checking.
 * First admin in an order's items page holds it; others are blocked until release/expiry.
 */
class RiderDonePresenceLock
{
    public const INDEX_KEY = 'dispatch:admin_rider_done:presence_index';

    public const TTL_SECONDS = 90;

    public function cacheKey(int $orderId): string
    {
        return 'dispatch:admin_rider_done:presence:'.$orderId;
    }

    /**
     * @return array{ok: bool, lock: array|null, message?: string}
     */
    public function acquire(User $user, int $orderId): array
    {
        if ($orderId <= 0) {
            return [
                'ok' => false,
                'lock' => null,
                'message' => __('message.not_found_entry', ['name' => __('message.order')]),
            ];
        }

        $existing = $this->currentForOrder($orderId);

        if ($existing && (int) ($existing['user_id'] ?? 0) === (int) $user->id) {
            $lock = $this->writeLock($user, $orderId, (string) ($existing['token'] ?? Str::uuid()->toString()));

            return ['ok' => true, 'lock' => $lock];
        }

        if ($existing) {
            return $this->busyResult($existing);
        }

        $token = Str::uuid()->toString();
        $lock = $this->makeLockPayload($user, $orderId, $token);

        if (! Cache::add($this->cacheKey($orderId), $lock, self::TTL_SECONDS)) {
            $existing = $this->currentForOrder($orderId);
            if ($existing && (int) ($existing['user_id'] ?? 0) === (int) $user->id) {
                $lock = $this->writeLock($user, $orderId, (string) ($existing['token'] ?? $token));

                return ['ok' => true, 'lock' => $lock];
            }

            return $this->busyResult($existing);
        }

        $this->indexAdd($orderId);

        return ['ok' => true, 'lock' => $lock];
    }

    /**
     * @return array{ok: bool, lock: array|null, message?: string}
     */
    public function heartbeat(User $user, int $orderId, ?string $token = null): array
    {
        if ($orderId <= 0) {
            return $this->busyResult(null);
        }

        $existing = $this->currentForOrder($orderId);

        if (! $existing) {
            return $this->acquire($user, $orderId);
        }

        if ((int) ($existing['user_id'] ?? 0) !== (int) $user->id) {
            return $this->busyResult($existing);
        }

        if ($token && isset($existing['token']) && ! hash_equals((string) $existing['token'], (string) $token)) {
            return $this->busyResult($existing);
        }

        $lock = $this->writeLock(
            $user,
            $orderId,
            (string) ($existing['token'] ?? Str::uuid()->toString())
        );

        return ['ok' => true, 'lock' => $lock];
    }

    public function release(User $user, int $orderId, ?string $token = null): bool
    {
        if ($orderId <= 0) {
            return true;
        }

        $existing = $this->currentForOrder($orderId);
        if (! $existing) {
            $this->indexRemove($orderId);

            return true;
        }

        if ((int) ($existing['user_id'] ?? 0) !== (int) $user->id) {
            return false;
        }

        if ($token && isset($existing['token']) && ! hash_equals((string) $existing['token'], (string) $token)) {
            return false;
        }

        Cache::forget($this->cacheKey($orderId));
        $this->indexRemove($orderId);

        return true;
    }

    public function currentForOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $raw = Cache::get($this->cacheKey($orderId));
        if (! is_array($raw) || empty($raw['user_id'])) {
            return null;
        }

        // Drop zombie locks whose locked_at is older than TTL (cache TTL missed / stuck entry).
        $lockedAt = $raw['locked_at'] ?? null;
        if (is_string($lockedAt) && $lockedAt !== '') {
            try {
                $age = now()->getTimestamp() - \Carbon\Carbon::parse($lockedAt)->getTimestamp();
                if ($age > self::TTL_SECONDS) {
                    Cache::forget($this->cacheKey($orderId));
                    $this->indexRemove($orderId);

                    return null;
                }
            } catch (\Throwable $e) {
                // keep lock if timestamp unreadable
            }
        }

        return $raw;
    }

    /**
     * Active locks for Order List highlighting.
     * Keys are order ids; values include holder info.
     *
     * @return array<int, array{user_id:int,user_name:string,order_id:int,locked_at?:string}>
     */
    public function activeLocks(?int $exceptUserId = null): array
    {
        $index = Cache::get(self::INDEX_KEY, []);
        if (! is_array($index) || $index === []) {
            return [];
        }

        $out = [];
        $stale = [];
        foreach (array_keys($index) as $orderId) {
            $orderId = (int) $orderId;
            $lock = $this->currentForOrder($orderId);
            if (! $lock) {
                $stale[] = $orderId;
                continue;
            }
            if ($exceptUserId !== null && (int) ($lock['user_id'] ?? 0) === (int) $exceptUserId) {
                // Still show own locks as "mine" on the list — include them.
            }
            $out[$orderId] = [
                'user_id' => (int) ($lock['user_id'] ?? 0),
                'user_name' => (string) ($lock['user_name'] ?? 'Admin'),
                'order_id' => $orderId,
                'locked_at' => $lock['locked_at'] ?? null,
            ];
        }

        foreach ($stale as $orderId) {
            $this->indexRemove($orderId);
        }

        return $out;
    }

    public function isHeldBy(User $user, int $orderId): bool
    {
        $existing = $this->currentForOrder($orderId);

        return $existing && (int) ($existing['user_id'] ?? 0) === (int) $user->id;
    }

    /** @deprecated Use currentForOrder / activeLocks */
    public function current(): ?array
    {
        $locks = $this->activeLocks();
        if ($locks === []) {
            return null;
        }

        return reset($locks) ?: null;
    }

    private function writeLock(User $user, int $orderId, string $token): array
    {
        $lock = $this->makeLockPayload($user, $orderId, $token);
        Cache::put($this->cacheKey($orderId), $lock, self::TTL_SECONDS);
        $this->indexAdd($orderId);

        return $lock;
    }

    private function makeLockPayload(User $user, int $orderId, string $token): array
    {
        return [
            'user_id' => (int) $user->id,
            'user_name' => (string) ($user->name ?: ('#'.$user->id)),
            'order_id' => $orderId,
            'token' => $token,
            'locked_at' => now()->toIso8601String(),
        ];
    }

    private function indexAdd(int $orderId): void
    {
        $index = Cache::get(self::INDEX_KEY, []);
        if (! is_array($index)) {
            $index = [];
        }
        $index[(string) $orderId] = time();
        Cache::put(self::INDEX_KEY, $index, self::TTL_SECONDS + 30);
    }

    private function indexRemove(int $orderId): void
    {
        $index = Cache::get(self::INDEX_KEY, []);
        if (! is_array($index)) {
            return;
        }
        unset($index[(string) $orderId]);
        if ($index === []) {
            Cache::forget(self::INDEX_KEY);
        } else {
            Cache::put(self::INDEX_KEY, $index, self::TTL_SECONDS + 30);
        }
    }

    /**
     * @return array{ok: false, lock: array|null, message: string}
     */
    private function busyResult(?array $existing): array
    {
        $name = (string) ($existing['user_name'] ?? 'Admin');

        return [
            'ok' => false,
            'lock' => $existing,
            'message' => __('message.admin_rider_done_busy', ['name' => $name]),
        ];
    }
}

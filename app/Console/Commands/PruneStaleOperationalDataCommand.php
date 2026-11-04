<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps MySQL lean on 16GB servers without touching active order/business rows.
 */
class PruneStaleOperationalDataCommand extends Command
{
    protected $signature = 'ops:prune-stale
        {--days=45 : Delete read notifications older than this many days}
        {--history-days=180 : Delete order_histories older than this many days}
        {--dry-run : Show counts only}';

    protected $description = 'Prune old read notifications, expired API tokens, and aged order history rows';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $notifDays = max(14, (int) $this->option('days'));
        $historyDays = max(60, (int) $this->option('history-days'));
        $notifCutoff = now()->subDays($notifDays);
        $historyCutoff = now()->subDays($historyDays);

        $this->info(($dry ? '[dry-run] ' : '').'Pruning operational clutter…');

        if (Schema::hasTable('notifications')) {
            $q = DB::table('notifications')
                ->whereNotNull('read_at')
                ->where('created_at', '<', $notifCutoff);
            $count = (clone $q)->count();
            $this->line("read notifications older than {$notifDays}d: {$count}");
            if (! $dry && $count > 0) {
                $deleted = 0;
                do {
                    $chunk = (clone $q)->limit(2000)->delete();
                    $deleted += $chunk;
                } while ($chunk > 0);
                $this->info("deleted notifications: {$deleted}");
            }
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $q = DB::table('personal_access_tokens')
                ->where(function ($outer) {
                    $outer->whereNotNull('expires_at')->where('expires_at', '<', now())
                        ->orWhere(function ($inner) {
                            $inner->whereNull('expires_at')
                                ->where('last_used_at', '<', now()->subDays(90));
                        })
                        ->orWhere(function ($inner) {
                            $inner->whereNull('expires_at')
                                ->whereNull('last_used_at')
                                ->where('created_at', '<', now()->subDays(90));
                        });
                });
            $count = (clone $q)->count();
            $this->line("stale personal_access_tokens: {$count}");
            if (! $dry && $count > 0) {
                $this->info('deleted tokens: '.(clone $q)->delete());
            }
        }

        if (Schema::hasTable('order_histories')) {
            // Keep recent history for audit/UI; age off very old rows that inflate dumps (~200k+).
            $q = DB::table('order_histories')->where('created_at', '<', $historyCutoff);
            $count = (clone $q)->count();
            $this->line("order_histories older than {$historyDays}d: {$count}");
            if (! $dry && $count > 0) {
                $deleted = 0;
                do {
                    $chunk = (clone $q)->limit(5000)->delete();
                    $deleted += $chunk;
                } while ($chunk > 0);
                $this->info("deleted order_histories: {$deleted}");
            }
        }

        if (Schema::hasTable('sessions')) {
            $lifetime = (int) config('session.lifetime', 120);
            $q = DB::table('sessions')->where('last_activity', '<', now()->subMinutes($lifetime)->getTimestamp());
            $count = (clone $q)->count();
            $this->line("expired sessions: {$count}");
            if (! $dry && $count > 0) {
                $this->info('deleted sessions: '.(clone $q)->delete());
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}

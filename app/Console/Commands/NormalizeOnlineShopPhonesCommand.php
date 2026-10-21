<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class NormalizeOnlineShopPhonesCommand extends Command
{
    protected $signature = 'users:normalize-online-shop-phones
                            {--dry-run : Show how many would change without writing}';

    protected $description = 'Normalize Online Shop contact numbers from +959… to local 09… format';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $changed = 0;
        $unchanged = 0;
        $empty = 0;

        User::query()
            ->where('user_type', 'client')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($dryRun, &$checked, &$changed, &$unchanged, &$empty) {
                foreach ($users as $user) {
                    $checked++;
                    $raw = (string) ($user->contact_number ?? '');
                    if (trim($raw) === '') {
                        $empty++;

                        continue;
                    }

                    $normalized = normalizeContactNumber($raw);
                    if ($normalized === '' || $normalized === $raw) {
                        $unchanged++;

                        continue;
                    }

                    $changed++;
                    if ($dryRun) {
                        $this->line("#{$user->id}: {$raw} → {$normalized}");

                        continue;
                    }

                    $user->forceFill(['contact_number' => $normalized])->saveQuietly();
                }
            });

        $this->info(($dryRun ? '[dry-run] ' : '')."checked={$checked} changed={$changed} unchanged={$unchanged} empty={$empty}");

        return Command::SUCCESS;
    }
}

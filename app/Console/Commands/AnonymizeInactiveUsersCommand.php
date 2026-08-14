<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use FontLib\Table\Type\name;
use Illuminate\Console\Command;

class AnonymizeInactiveUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:anonymize-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymize users inactive for more than 24 months';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cutoffDate = Carbon::now()->subMonths(24);
        $anonymizedCount = 0;

        User::query()
            ->whereNotNull('last_actived_at')
            ->where('last_actived_at', '<=', $cutoffDate)
            ->where('user_type', '!=', 'admin')
            ->where(function ($query) {
                $query->whereNull('email')
                    ->orWhere('email', 'not like', '%@anonymized.com');
            })
            ->chunkById(100, function ($users) use (&$anonymizedCount) {
                foreach ($users as $user) {
                    $now = now();

                    $user->forceFill([
                        'name' => 'Anonymized ' . $user->id . ' User',
                        'username' => 'Anonymized ' . $user->id . ' User',
                        'address' => null,
                        'email' => $now->format('YmdHis') . $user->id . '@anonymized.com',
                        'contact_number' => $now->format('ymdHis') . $user->id,
                    ])->save();

                    $user->userBankAccount()->delete();
                    $user->userAddress()->delete();

                    $anonymizedCount++;
                }
            });

        $this->info("Inactive users anonymized: {$anonymizedCount}");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckApiEncryptionCommand extends Command
{
    protected $signature = 'encryption:check';

    protected $description = 'Verify SECRET_KEY and VIKEY are configured for mobile API login';

    public function handle(): int
    {
        $secretKey = config('api_encryption.secret_key');
        $vikey = config('api_encryption.vikey');

        if (empty($secretKey) || empty($vikey)) {
            $this->error('SECRET_KEY and/or VIKEY are missing.');
            $this->line('Add them to .env, then run: php artisan config:cache');
            $this->line('Example (must match Flutter encrypt_data.dart):');
            $this->line('  SECRET_KEY=11a1215l0119a140409p0919');
            $this->line('  VIKEY=23a1dfr5lyhd9a1404845001');

            return self::FAILURE;
        }

        $this->info('API encryption keys are configured.');
        $this->line('SECRET_KEY length: ' . strlen($secretKey));
        $this->line('VIKEY length: ' . strlen($vikey));

        return self::SUCCESS;
    }
}

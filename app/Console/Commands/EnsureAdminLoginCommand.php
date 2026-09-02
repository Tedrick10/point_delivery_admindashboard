<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EnsureAdminLoginCommand extends Command
{
    protected $signature = 'pds:ensure-admin
                            {--email=admin@admin.com : Branch Admin email}
                            {--password=12345678 : Branch Admin password}
                            {--name=Admin : Display name}';

    protected $description = 'Create or reset Branch Admin login (admin role) for production/cPanel';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->option('email')));
        $password = (string) $this->option('password');
        $name = trim((string) $this->option('name')) ?: 'Admin';

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid --email value.');

            return self::FAILURE;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters.');

            return self::FAILURE;
        }

        Role::findOrCreate('admin', 'web');

        $user = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && $user->trashed()) {
            $user->restore();
        }

        if (! $user) {
            $user = new User();
            $user->email = $email;
            $user->username = strstr($email, '@', true) ?: 'admin';
            $user->contact_number = $user->contact_number ?: '09000000000';
            $this->info("Creating new admin user: {$email}");
        } else {
            $this->warn("Updating existing user #{$user->id}: {$email}");
        }

        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->user_type = 'admin';
        $user->status = 1;
        $user->deleted_at = null;
        $user->save();

        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles(['admin']);
        } else {
            $roleId = Role::findByName('admin', 'web')->id;
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ],
                []
            );
        }

        $this->info('Branch Admin is ready.');
        $this->line("  Email   : {$email}");
        $this->line("  Password: {$password}");
        $this->line('  Login   : /adminHub (SECURE_ADMIN_ROUTE)');

        return self::SUCCESS;
    }
}

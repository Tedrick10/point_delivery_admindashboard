<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_summary_triple_checks')) {
            Schema::create('expense_summary_triple_checks', function (Blueprint $table) {
                $table->id();
                $table->date('check_date');
                $table->string('checker_key', 32);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();

                $table->unique(['check_date', 'checker_key'], 'expense_summary_triple_checks_date_key');
                $table->index('check_date');
            });
        }

        $this->ensureMaPhyuSin();
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_summary_triple_checks');
    }

    private function ensureMaPhyuSin(): void
    {
        $email = 'maphyusin@pointdeli.com';
        $existing = DB::table('users')->where('email', $email)->first();
        $now = now();

        if ($existing) {
            $userId = (int) $existing->id;
            DB::table('users')->where('id', $userId)->update([
                'name' => 'Ma Shwe Sin',
                'username' => $existing->username ?: 'maphyusin',
                'user_type' => $existing->user_type ?: 'staff',
                'status' => 1,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
        } else {
            $userId = (int) DB::table('users')->insertGetId([
                'name' => 'Ma Shwe Sin',
                'username' => 'maphyusin',
                'email' => $email,
                'password' => Hash::make('12345678'),
                'contact_number' => '09988887777',
                'user_type' => 'staff',
                'status' => 1,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $role = Role::findOrCreate('staff', 'web');
        $hasRole = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', $userId)
            ->exists();
        if (! $hasRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
            ]);
        }
    }
};

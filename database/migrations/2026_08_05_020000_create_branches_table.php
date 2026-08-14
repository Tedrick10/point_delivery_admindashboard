<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('city_id')->constrained('branches')->nullOnDelete();
        });

        $defaultBranches = ['Food', 'MDY', 'Other City', 'Yangon', 'Ygn to Ygn'];
        $now = now();
        foreach ($defaultBranches as $name) {
            DB::table('branches')->updateOrInsert(
                ['name' => $name],
                [
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $parent = Permission::firstOrCreate(
            ['name' => 'branch', 'guard_name' => 'web'],
            ['parent_id' => null]
        );

        $permissionNames = [
            'branch-add',
            'branch-edit',
            'branch-list',
            'branch-show',
            'branch-delete',
        ];

        $permissionIds = [$parent->id];
        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['parent_id' => $parent->id]
            );
            $permissionIds[] = $permission->id;
        }

        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo($permissionIds);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::dropIfExists('branches');

        $names = ['branch', 'branch-add', 'branch-edit', 'branch-list', 'branch-show', 'branch-delete'];
        Permission::whereIn('name', $names)->where('guard_name', 'web')->delete();
    }
};

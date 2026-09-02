<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_staff', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('staff_group', 20)->default('office'); // office | rider
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->unsignedInteger('allowance_minutes')->default(60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->index(['staff_group', 'status']);
        });

        Schema::create('hr_late_fine_rows', function (Blueprint $table) {
            $table->id();
            $table->date('period_month'); // first day of month
            $table->unsignedBigInteger('staff_id');
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('allowance_minutes')->default(60);
            $table->unsignedInteger('fine_per_minute')->default(100);
            $table->string('absent_dates')->nullable();
            $table->unsignedInteger('absent_days')->default(0);
            $table->unsignedInteger('absent_day_rate')->default(6000);
            $table->decimal('way_amount', 12, 2)->default(0);
            $table->decimal('ako_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('hr_staff')->cascadeOnDelete();
            $table->unique(['period_month', 'staff_id']);
            $table->index('period_month');
        });

        Schema::create('hr_late_fine_items', function (Blueprint $table) {
            $table->id();
            $table->date('period_month');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('staff_code', 50)->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('hr_staff')->nullOnDelete();
            $table->index(['period_month', 'staff_id']);
        });

        Schema::create('hr_office_salary_rows', function (Blueprint $table) {
            $table->id();
            $table->date('period_month');
            $table->unsignedBigInteger('staff_id');
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->unsignedTinyInteger('salary_day_base')->default(28);
            $table->unsignedTinyInteger('rest_days')->default(3);
            $table->unsignedInteger('way_count')->default(0);
            $table->decimal('way_rate', 12, 2)->default(0);
            $table->decimal('late_minute_amount', 12, 2)->default(0);
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->decimal('bag_deduction', 12, 2)->default(0);
            $table->decimal('personal_expense', 12, 2)->default(0);
            $table->decimal('deposit', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('hr_staff')->cascadeOnDelete();
            $table->unique(['period_month', 'staff_id']);
            $table->index('period_month');
        });

        $parent = Permission::firstOrCreate(
            ['name' => 'hr-payroll', 'guard_name' => 'web'],
            ['parent_id' => null]
        );

        foreach (['hr-payroll-list', 'hr-payroll-add', 'hr-payroll-edit', 'hr-payroll-delete'] as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['parent_id' => $parent->id]
            );
        }

        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $permissionIds = Permission::whereIn('name', [
                'hr-payroll',
                'hr-payroll-list',
                'hr-payroll-add',
                'hr-payroll-edit',
                'hr-payroll-delete',
            ])->pluck('id');

            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $adminRole->id)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $adminRole->id,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_office_salary_rows');
        Schema::dropIfExists('hr_late_fine_items');
        Schema::dropIfExists('hr_late_fine_rows');
        Schema::dropIfExists('hr_staff');

        $names = ['hr-payroll', 'hr-payroll-list', 'hr-payroll-add', 'hr-payroll-edit', 'hr-payroll-delete'];
        $ids = Permission::whereIn('name', $names)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            Permission::whereIn('id', $ids)->delete();
        }
    }
};

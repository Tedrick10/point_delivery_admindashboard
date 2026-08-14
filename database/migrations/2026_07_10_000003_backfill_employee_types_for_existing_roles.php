<?php

use App\Models\EmployeeType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $protectedRoles = ['admin', 'client', 'delivery_man', 'demo_admin'];

        $roles = Role::query()
            ->whereNull('employee_type_id')
            ->whereNotIn('name', $protectedRoles)
            ->get();

        if ($roles->isEmpty()) {
            return;
        }

        $grouped = $roles->groupBy(function ($role) {
            return match ($role->name) {
                'staff', 'manager', 'accountant' => 'Operations',
                default => 'General',
            };
        });

        foreach ($grouped as $typeName => $typeRoles) {
            $employeeType = EmployeeType::firstOrCreate(
                ['name' => $typeName],
                ['status' => 1]
            );

            foreach ($typeRoles as $role) {
                $role->employee_type_id = $employeeType->id;
                $role->save();
            }
        }
    }

    public function down(): void
    {
        Role::query()
            ->whereNotNull('employee_type_id')
            ->update(['employee_type_id' => null]);
    }
};

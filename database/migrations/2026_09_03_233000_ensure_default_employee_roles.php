<?php

use App\Models\EmployeeType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Ensure Account Creation has assignable employee roles
     * (staff / accountant / manager) so the Role dropdown is not empty.
     */
    public function up(): void
    {
        $operations = EmployeeType::firstOrCreate(
            ['name' => 'Operations'],
            ['status' => 1]
        );

        foreach (['staff', 'accountant', 'manager'] as $roleName) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['status' => 1]
            );

            $dirty = false;
            if ((int) $role->status !== 1) {
                $role->status = 1;
                $dirty = true;
            }
            if (empty($role->employee_type_id)) {
                $role->employee_type_id = $operations->id;
                $dirty = true;
            }
            if ($dirty) {
                $role->save();
            }
        }
    }

    public function down(): void
    {
        // Keep seeded roles; safe to leave in place.
    }
};

<?php

namespace App\Services;

use App\Models\EmployeeType;
use App\Models\Role;
use App\Models\User;

class EmployeeTypeService
{
    protected array $protectedRoles = ['admin', 'client', 'delivery_man', 'demo_admin', 'super_admin'];

    public function resolveEmployeeTypeId(array $data): ?int
    {
        if (!empty($data['employee_type_id'])) {
            return (int) $data['employee_type_id'];
        }

        $name = trim($data['employee_type_name'] ?? '');
        if ($name === '') {
            return null;
        }

        $type = EmployeeType::firstOrCreate(
            ['name' => $name],
            ['status' => 1]
        );

        return $type->id;
    }

    public function roleHasEmployees(Role $role): bool
    {
        return User::query()
            ->where('user_type', $role->name)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function cleanupEmptyType(?int $employeeTypeId): void
    {
        if (!$employeeTypeId) {
            return;
        }

        $type = EmployeeType::find($employeeTypeId);
        if (!$type || $type->roles()->count() > 0) {
            return;
        }

        if ($type->hasEmployees()) {
            return;
        }

        $type->delete();
    }

    public function deleteRole(Role $role): array
    {
        if (in_array($role->name, $this->protectedRoles, true)) {
            return [
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ];
        }

        if ($this->roleHasEmployees($role)) {
            return [
                'status' => false,
                'message' => __('message.role_has_employees'),
            ];
        }

        $employeeTypeId = $role->employee_type_id;
        $role->delete();
        $this->cleanupEmptyType($employeeTypeId);

        return [
            'status' => true,
            'message' => __('message.delete_form', ['form' => __('message.role')]),
        ];
    }

    public function sidebarTypes()
    {
        return EmployeeType::query()
            ->where('status', 1)
            ->whereHas('roles', function ($query) {
                $query->where('status', 1)
                    ->whereNotIn('name', ['admin', 'client', 'delivery_man', 'demo_admin']);
            })
            ->with(['activeRoles'])
            ->orderBy('name')
            ->get();
    }
}

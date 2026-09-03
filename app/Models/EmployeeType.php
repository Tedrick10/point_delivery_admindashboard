<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeType extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'employee_type_id');
    }

    public function activeRoles(): HasMany
    {
        return $this->roles()
            ->where('status', 1)
            ->whereNotIn('name', ['admin', 'client', 'delivery_man', 'demo_admin', 'super_admin'])
            ->orderBy('name');
    }

    public function employeeCount(): int
    {
        $roleNames = $this->roles()->pluck('name');

        if ($roleNames->isEmpty()) {
            return 0;
        }

        return User::query()
            ->whereIn('user_type', $roleNames)
            ->whereNotIn('user_type', ['admin', 'client', 'delivery_man'])
            ->count();
    }

    public function hasEmployees(): bool
    {
        return $this->employeeCount() > 0;
    }
}

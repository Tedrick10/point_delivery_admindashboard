<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PermissionController;
use App\Models\EmployeeType;
use App\Models\Role;
use App\Services\EmployeeTypeService;
use Illuminate\Http\Request;

class RolesPermissionsController extends Controller
{
    public static function screenPayload(): array
    {
        $auth_user = auth()->user();
        $roles = Role::where('status', 1)->orderBy('name', 'ASC');
        if (! $auth_user->hasRole('admin')) {
            $roles->where('name', '!=', 'admin');
        }
        $roles = $roles->get();

        /** @var PermissionController $permission */
        $permission = app(PermissionController::class);

        return [
            'roles' => $roles,
            'modules' => $permission->adminPanelPermissionModules(),
            'auth_user' => $auth_user,
            'pageTitle' => __('message.roles_and_permission'),
            'canManageRoles' => true,
            'embedReturn' => 'super-admin',
            'formAction' => route('permission.store'),
            'addRoleUrl' => route('super-admin.roles-permissions.create-role'),
            'employeeTypes' => EmployeeType::where('status', 1)->orderBy('name')->get(),
        ];
    }

    public function createRole()
    {
        return view('super-admin.roles-permissions.create-role', [
            'employeeTypes' => EmployeeType::where('status', 1)->orderBy('name')->get(),
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:191|regex:/^[\pL\s\-]+$/u|unique:roles,name',
            'employee_type_id' => 'nullable|exists:employee_types,id',
            'employee_type_name' => 'nullable|max:191|regex:/^[\pL\s\-]+$/u',
        ]);

        $employeeTypeId = app(EmployeeTypeService::class)->resolveEmployeeTypeId($data);
        if (! $employeeTypeId) {
            return redirect()->back()->withInput()->withErrors([
                'employee_type_id' => __('message.employee_type_required'),
            ]);
        }

        Role::create([
            'name' => $data['name'],
            'status' => '1',
            'employee_type_id' => $employeeTypeId,
        ]);

        return redirect()
            ->route('super-admin.screens.show', ['screen' => 'roles-permissions'])
            ->withSuccess(__('message.save_form', ['name' => __('message.role')]));
    }
}

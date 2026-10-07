<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PermissionController;
use App\Models\EmployeeType;
use App\Models\Permission;
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
            'storeRoleUrl' => route('super-admin.roles-permissions.store-role'),
            'togglePermissionUrl' => route('super-admin.roles-permissions.toggle-permission'),
            'employeeTypes' => EmployeeType::where('status', 1)->orderBy('name')->get(),
        ];
    }

    public function createRole()
    {
        return view('super-admin.roles-permissions.create-role');
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:191|regex:/^[\pL\s\-]+$/u|unique:roles,name',
            'employee_type_name' => 'required|max:191|regex:/^[\pL\s\-]+$/u',
        ]);

        $employeeTypeId = app(EmployeeTypeService::class)->resolveEmployeeTypeId($data);
        if (! $employeeTypeId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('message.employee_type_required'),
                    'errors' => ['employee_type_name' => [__('message.employee_type_required')]],
                ], 422);
            }

            return redirect()->back()->withInput()->withErrors([
                'employee_type_name' => __('message.employee_type_required'),
            ]);
        }

        Role::create([
            'name' => $data['name'],
            'status' => '1',
            'employee_type_id' => $employeeTypeId,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['status' => true]);
        }

        return redirect()
            ->route('super-admin.screens.show', ['screen' => 'roles-permissions'])
            ->withSuccess(__('message.save_form', ['name' => __('message.role')]));
    }

    public function togglePermission(Request $request)
    {
        $data = $request->validate([
            'role' => 'required|string|max:191',
            'permission' => 'required|string|max:191',
            'allowed' => 'required|boolean',
        ]);

        $role = Role::query()->where('name', $data['role'])->where('status', 1)->firstOrFail();
        if (in_array($role->name, ['admin'], true) || $role->is_hidden) {
            return response()->json(['status' => false], 403);
        }

        $allowedNames = collect(app(PermissionController::class)->adminPanelPermissionModules())
            ->flatMap(fn ($module) => collect($module['permissions'] ?? [])->pluck('name'))
            ->filter()
            ->unique()
            ->values();

        if (! $allowedNames->contains($data['permission'])) {
            return response()->json(['status' => false], 422);
        }

        $permission = Permission::findOrCreate($data['permission']);
        if ($data['allowed']) {
            $role->givePermissionTo($permission);
        } else {
            $role->revokePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json(['status' => true, 'allowed' => (bool) $data['allowed']]);
    }

    public function destroyRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $result = app(EmployeeTypeService::class)->deleteRole($role);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $result['status'],
                'message' => $result['message'],
            ], $result['status'] ? 200 : 422);
        }

        if (! $result['status']) {
            return redirect()->back()->withErrors($result['message']);
        }

        return redirect()
            ->route('super-admin.screens.show', ['screen' => 'roles-permissions'])
            ->withSuccess($result['message']);
    }
}

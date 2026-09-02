<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;
use App\Models\EmployeeType;
use App\Services\EmployeeTypeService;
use Illuminate\Support\Facades\Artisan;

class PermissionController extends Controller
{
    public function __construct()
    {
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pageTitle = __('message.roles_and_permission');
        $auth_user = authSession();

        $roles = Role::where('status', 1)->orderBy('name', 'ASC');
        if (! \Auth::user()->hasRole('admin')) {
            $roles->where('name', '!=', 'admin');
        }
        $roles = $roles->get();

        $modules = $this->adminPanelPermissionModules();

        return view('permission.index', compact('roles', 'modules', 'pageTitle', 'auth_user'));
    }

    /**
     * Only features currently shown in the Admin Panel sidebar.
     */
    protected function adminPanelPermissionModules(): array
    {
        $catalog = [
            'order' => [
                'label' => __('message.order'),
                'hint' => __('message.roles_module_order_hint'),
                'icon' => 'fas fa-file-alt',
            ],
            'users' => [
                'label' => __('message.online_shop'),
                'hint' => __('message.roles_module_users_hint'),
                'icon' => 'fas fa-store',
            ],
            'deliveryman' => [
                'label' => __('message.delivery_man'),
                'hint' => __('message.roles_module_deliveryman_hint'),
                'icon' => 'fas fa-user-tie',
            ],
            'hr-payroll' => [
                'label' => __('message.hr_payroll'),
                'hint' => __('message.roles_module_payroll_hint'),
                'icon' => 'fas fa-wallet',
            ],
            'subadmin' => [
                'label' => __('message.account_creation'),
                'hint' => __('message.roles_module_account_hint'),
                'icon' => 'fas fa-user-plus',
            ],
            'role' => [
                'label' => __('message.role'),
                'hint' => __('message.roles_module_role_hint'),
                'icon' => 'fas fa-user-tag',
            ],
            'permission' => [
                'label' => __('message.permission'),
                'hint' => __('message.roles_module_permission_hint'),
                'icon' => 'fas fa-key',
            ],
        ];

        $parents = Permission::query()
            ->whereNull('parent_id')
            ->whereIn('name', array_keys($catalog))
            ->with(['subpermission' => fn ($q) => $q->orderBy('id')])
            ->get()
            ->keyBy('name');

        $modules = [];
        foreach ($catalog as $key => $meta) {
            $parent = $parents->get($key);
            if (! $parent) {
                continue;
            }

            $actionOrder = ['list' => 1, 'show' => 2, 'add' => 3, 'edit' => 4, 'delete' => 5];

            $permissions = $parent->subpermission->map(function (Permission $perm) {
                $suffix = str_contains($perm->name, '-')
                    ? substr($perm->name, strrpos($perm->name, '-') + 1)
                    : $perm->name;

                $action = match ($suffix) {
                    'list' => __('message.list'),
                    'show' => __('message.view'),
                    'add' => __('message.add'),
                    'edit' => __('message.edit'),
                    'delete' => __('message.delete'),
                    default => ucfirst($suffix),
                };

                return [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'action' => $action,
                    'suffix' => $suffix,
                ];
            })
                ->sortBy(fn ($perm) => $actionOrder[$perm['suffix']] ?? 99)
                ->map(fn ($perm) => [
                    'id' => $perm['id'],
                    'name' => $perm['name'],
                    'action' => $perm['action'],
                ])
                ->values()
                ->all();

            if ($permissions === []) {
                continue;
            }

            $modules[] = [
                'key' => $key,
                'label' => $meta['label'],
                'hint' => $meta['hint'],
                'icon' => $meta['icon'],
                'permissions' => $permissions,
            ];
        }

        return $modules;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $managedParentNames = array_column($this->adminPanelPermissionModules(), 'key');
        if ($managedParentNames === []) {
            $managedParentNames = ['order', 'users', 'deliveryman', 'hr-payroll', 'subadmin', 'role', 'permission'];
        }

        $managedPermissionIds = Permission::query()
            ->where(function ($q) use ($managedParentNames) {
                $q->whereIn('name', $managedParentNames)
                    ->orWhereIn('parent_id', function ($sub) use ($managedParentNames) {
                        $sub->select('id')
                            ->from('permissions')
                            ->whereNull('parent_id')
                            ->whereIn('name', $managedParentNames);
                    });
            })
            ->pluck('id');

        $managedPermissions = Permission::query()->whereIn('id', $managedPermissionIds)->get();
        $submitted = is_array($request->permission) ? $request->permission : [];

        $roles = Role::query()->whereNotIn('name', ['admin'])->get();
        foreach ($roles as $role) {
            if ($managedPermissions->isNotEmpty()) {
                $role->revokePermissionTo($managedPermissions);
            }

            foreach ($submitted as $permissionName => $roleNames) {
                if (! in_array($role->name, (array) $roleNames, true)) {
                    continue;
                }
                $permission = Permission::findOrCreate($permissionName);
                if (! $managedPermissionIds->contains($permission->id)) {
                    continue;
                }
                $role->givePermissionTo($permission);
            }
        }

        Artisan::call('permission:cache-reset');

        return redirect()->route('permission.index')->withSuccess(__('message.save_form', ['form' => __('message.roles_and_permission')]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function addPermission($type){
        switch ($type){
            case 'permission' :
                $title = __('message.add_form_title',['form' => __('message.permission')  ]);
                break;
            case 'role' :
                $title = __('message.add_form_title',['form' => __('message.role')  ]);
                break;
            default :
                $title = __('message.add_form_title',['form' => __('message.permission')  ]);
                break;
        }
        $employeeTypes = collect();
        if ($type === 'role') {
            $employeeTypes = EmployeeType::where('status', 1)->orderBy('name')->get();
        }

        return view('permission.add_permission', compact(['title', 'type', 'employeeTypes']));
    }

    public function savePermission(Request $request)
    {
        $data = $request->all();

        switch ($data['type']){
            case 'permission' :
                    $validator = \Validator::make($data,[
                        'name' => 'required|max:191|unique:permissions,name',
                    ]);

                    if($validator->fails()) {
                        $message = $validator->errors()->first();
                        return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
                    }
                    $permission = Permission::create([
                        'name' => $data['name'],
                        'parent_id' => isset($request->parent_id) ? $request->parent_id : null,
                    ]);
                    $admin_role = Role::findByName('admin');
                    $admin_role->givePermissionTo($permission);
                    break;
            case 'role' :
                    $validator = \Validator::make($data, [
                        'name' => 'required|max:191|regex:/^[\pL\s\-]+$/u|unique:roles,name',
                        'employee_type_id' => 'nullable|exists:employee_types,id',
                        'employee_type_name' => 'nullable|max:191|regex:/^[\pL\s\-]+$/u',
                    ]);

                    if ($validator->fails()) {
                        $message = $validator->errors()->first();
                        return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
                    }

                    $employeeTypeService = app(EmployeeTypeService::class);
                    $employeeTypeId = $employeeTypeService->resolveEmployeeTypeId($data);

                    if (!$employeeTypeId) {
                        return response()->json([
                            'status' => false,
                            'message' => __('message.employee_type_required'),
                            'event' => 'validation',
                        ]);
                    }

                    Role::create([
                        'name' => $data['name'],
                        'status' => '1',
                        'employee_type_id' => $employeeTypeId,
                    ]);
                    break;
            default :
                    return response()->json(['status'=>false,'event' => 'validation' , 'message' => 'Try Again']);
                    break;
        }
        $message = __('message.save_form',['name'=> __('message.'.$data['type']) ] );

        return response()->json(['status' => true,'event' => 'refresh' , 'message' => $message]);
    }
}

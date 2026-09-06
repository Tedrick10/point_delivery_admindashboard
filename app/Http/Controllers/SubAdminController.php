<?php

namespace App\Http\Controllers;

use App\DataTables\SubAdminDataTable;
use App\Models\Role;
use App\Models\EmployeeType;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SubAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(SubAdminDataTable $dataTable)
    {
        if (!auth()->user()->can('subadmin-list')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.list_form_title', ['form' => __('message.sub_admin')]);
        $employeeType = null;
        $roleFilter = request('role');

        if (request('employee_type')) {
            $employeeType = EmployeeType::find(request('employee_type'));
            if ($employeeType) {
                $pageTitle = $employeeType->name . ' - ' . __('message.list_form_title', ['form' => __('message.sub_admin')]);
                if ($roleFilter) {
                    $pageTitle = $employeeType->name . ' / ' . ucfirst($roleFilter) . ' - ' . __('message.list_form_title', ['form' => __('message.sub_admin')]);
                }
            }
        }

        $auth_user = authSession();
        $assets = ['datatable'];
        $multi_checkbox_delete = $auth_user->can('users-delete') ? '<button id="deleteSelectedBtn" checked-title = "users-checked" class="float-left btn btn-sm ">' . __('message.delete_selected') . '</button>' : '';
        $createRoute = route('sub-admin.create', array_filter([
            'employee_type' => request('employee_type'),
            'role' => $roleFilter,
        ]));
        $button = $auth_user->can('subadmin-add') ? '<a href="' . $createRoute . '" class="float-right btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> ' . __('message.add_form_title', ['form' => __('message.sub_admin')]) . '</a>' : '';
        return $dataTable->render('subadmin.index', compact('assets', 'pageTitle', 'button', 'auth_user','multi_checkbox_delete', 'employeeType', 'roleFilter'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('subadmin-add')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle =  __('message.sub_admin');
        $employeeType = null;
        $selectedRole = request('role');

        if (request('employee_type')) {
            $employeeType = EmployeeType::with('activeRoles')->find(request('employee_type'));
        }

        $roles = $this->employeeRoleOptions($employeeType);

        $assets = ['phone'];
        return view('subadmin.form', compact('pageTitle','roles','assets','employeeType','selectedRole'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        if (! auth()->user()->can('subadmin-add')) {
            return redirect()->back()->withInput()->withErrors(__('message.demo_permission_denied'));
        }

        $allowedRoles = $this->employeeRoleOptions()->keys()->all();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username',
            'contact_number' => 'required|string|max:30|unique:users,contact_number',
            'password' => 'required|string|min:6',
            'user_type' => 'required|string|in:'.implode(',', $allowedRoles),
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        $contactNumber = $this->normalizeContactNumber($data['contact_number'] ?? null);
        if ($contactNumber === null) {
            return redirect()->back()->withInput()->withErrors([
                'contact_number' => __('message.contact_number').' is required.',
            ]);
        }

        if (User::withTrashed()->where('contact_number', $contactNumber)->exists()) {
            return redirect()->back()->withInput()->withErrors([
                'contact_number' => __('message.contact_number_already_taken'),
            ]);
        }

        try {
            $result = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => bcrypt($data['password']),
                'user_type' => $data['user_type'],
                'contact_number' => $contactNumber,
                'status' => 1,
                'email_verified_at' => now(),
                'otp_verify_at' => now(),
            ]);

            uploadMediaFile($result, $request->file('profile_image'), 'profile_image');
            $result->assignRole($data['user_type']);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->withErrors($e->getMessage());
        }

        $message = __('message.save_form', ['form' => __('message.sub_admin')]);
        if ($request->is('api/*')) {
            return json_message_response($message);
        }

        return redirect()->route('sub-admin.index')->withSuccess($message);
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
        if (!auth()->user()->can('subadmin-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.sub_admin')]);
        $data = User::whereNotIn('user_type',['admin','client','delivery_man'])->findOrFail($id);
        $profileImage = getSingleMedia($data, 'profile_image');
        $assets = ['phone'];
        $employeeType = null;
        $selectedRole = $data->user_type;
        if ($data->user_type) {
            $role = Role::where('name', $data->user_type)->first();
            $employeeType = $role?->employeeType;
        }
        $roles = $this->employeeRoleOptions($employeeType, $selectedRole);

        return view('subadmin.form', compact('data', 'pageTitle', 'id', 'assets','roles','profileImage','employeeType','selectedRole'));
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
        if (!auth()->user()->can('subadmin-edit')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $user = User::whereNotIn('user_type', ['admin', 'client', 'delivery_man'])->find($id);

        $message = __('message.not_found_entry', ['name' => __('message.sub_admin')]);
        if ($user == null) {
            return response()->json(['status' => false, 'message' => $message]);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'user_type' => 'required|string|max:100',
            'contact_number' => 'nullable|string|max:30',
            'password' => 'nullable|string|min:6',
            'profile_image' => 'nullable|image',
        ]);

        $user->removeRole($user->user_type);

        $payload = [
            'name' => $data['name'],
            'user_type' => $data['user_type'],
            'contact_number' => $data['contact_number'] ?? $user->contact_number,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = bcrypt($data['password']);
            $payload['is_temp_password'] = 0;
        }

        $user->fill($payload)->update();

        if ($request->hasFile('profile_image')) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        $user->assignRole($data['user_type']);

        $message = __('message.update_form', ['form' => __('message.sub_admin')]);

        if ($request->is('api/*')) {
            return json_message_response($message);
        }

        return redirect()->route('sub-admin.index')->withSuccess($message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('subadmin-delete')) {
            $message = __('message.demo_permission_denied');
            return redirect()->back()->withErrors($message);
        }
        $user = User::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' => __('message.sub_admin')]);

        if($user != '') {
            $user->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.sub_admin')]);
        }

        if(request()->is('api/*')){
            return response()->json(['status' => true, 'message' => $message ]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }

    public function action(Request $request)
    {
        $id = $request->id;
        $sub_admin = User::withTrashed()->where('id', $id)->first();

        $message = __('message.not_found_entry', ['name' => __('message.sub_admin')]);
        if ($request->type === 'restore') {
            $sub_admin->restore();
            $message = __('message.msg_restored', ['name' => __('message.sub_admin')]);
        }

        if ($request->type === 'forcedelete') {
            if(env('APP_DEMO')){
                $message = __('message.demo_permission_denied');
                if(request()->is('api/*')){
                    return response()->json(['status' => true, 'message' => $message ]);
                }
                if(request()->ajax()) {
                    return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
                }
                return redirect()->route('sub-admin.index')->withErrors($message);
            }
            $sub_admin->forceDelete();
            $message = __('message.force_delete_msg', ['name' => __('message.sub_admin')]);
        }
        if (request()->is('api/*')) {
            return json_custom_response(['message' => $message, 'status' => true]);
        }

        return redirect()->route('sub-admin.index')->withSuccess($message);
    }

    /**
     * Employee List On/Off (rest day). Off auto-returns On at 12:01 AM next day.
     * Off → နားရက် +1. On before 17:00 → နားရက် −1. After 17:00 On keeps နားရက် locked.
     */
    public function updateWorkStatus(Request $request, $id)
    {
        if (! auth()->user()->can('subadmin-edit') && ! auth()->user()->can('users-edit')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        if (env('APP_DEMO')) {
            return response()->json(['message' => __('message.demo_permission_denied')], 403);
        }

        $request->validate([
            'work_on' => 'required|boolean',
        ]);

        $employee = User::query()
            ->whereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin'])
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $workOn = $request->boolean('work_on');
        $today = now('Asia/Yangon')->toDateString();

        $employee->rider_work_on = $workOn;
        if (Schema::hasColumn('users', 'rider_work_off_date')) {
            $employee->rider_work_off_date = $workOn ? null : $today;
        }
        $employee->save();

        $payroll = app(\App\Services\HrPayrollService::class);
        $restResult = [
            'rest_days' => null,
            'rest_off_dates' => [],
            'applied' => false,
            'reverted' => false,
            'locked' => false,
        ];

        if (! $workOn) {
            $restResult = array_merge($restResult, $payroll->applyRestDayOffForUser($employee));
        } else {
            $restResult = array_merge($restResult, $payroll->revertRestDayOffForUser($employee));
        }

        $message = __('message.employee_work_status_updated');
        if ($workOn && ! empty($restResult['locked'])) {
            $message = __('message.employee_work_on_rest_locked');
        } elseif (! $workOn && ! empty($restResult['applied'])) {
            $message = __('message.employee_work_off_rest_added');
        } elseif ($workOn && ! empty($restResult['reverted'])) {
            $message = __('message.employee_work_on_rest_reverted');
        }

        return response()->json([
            'message' => $message,
            'work_on' => (bool) $employee->isRiderWorkOn(),
            'label' => $employee->isRiderWorkOn()
                ? __('message.rider_work_on')
                : __('message.rider_work_off'),
            'rest_days' => $restResult['rest_days'],
            'rest_off_dates' => $restResult['rest_off_dates'] ?? [],
            'rest_locked' => (bool) ($restResult['locked'] ?? false),
            'off_date' => $workOn ? null : $today,
        ]);
    }

    /**
     * Roles assignable on Account Creation / Employee form.
     * System app roles (client, delivery man, super admin) stay out of this list.
     * Admin + custom employee roles (staff, manager, …) are shown.
     *
     * @return \Illuminate\Support\Collection<string, string>
     */
    protected function employeeRoleOptions(?EmployeeType $employeeType = null, ?string $includeRole = null)
    {
        $excluded = ['client', 'delivery_man', 'super_admin', 'demo_admin'];

        $rolesQuery = Role::query()
            ->where('status', 1)
            ->whereNotIn('name', $excluded)
            ->orderBy('name');

        if ($employeeType) {
            $rolesQuery->where(function ($query) use ($employeeType, $includeRole) {
                $query->where('employee_type_id', $employeeType->id);
                if ($includeRole) {
                    $query->orWhere('name', $includeRole);
                }
            });
        }

        return $rolesQuery->get()->mapWithKeys(function (Role $role) {
            $label = ucwords(str_replace('_', ' ', $role->name));

            return [$role->name => $label];
        });
    }

    /**
     * Normalize intl-tel / dial-code quirks (e.g. +95+9598… → +9598…).
     */
    protected function normalizeContactNumber(?string $value): ?string
    {
        $number = normalizeContactNumber((string) $value);
        if ($number === '' || preg_match('/^\+\d{1,4}$/', $number)) {
            return null;
        }

        return $number;
    }
}

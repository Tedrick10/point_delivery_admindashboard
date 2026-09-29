<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeType;
use App\Models\User;
use App\Services\HrPayrollService;
use App\Services\RiderWorkStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class AccountCreationController extends Controller
{
    public static function screenPayload(Request $request): array
    {
        app(RiderWorkStatusService::class)->resetExpiredOffRiders();

        $q = User::query()
            ->whereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin'])
            ->whereNull('deleted_at')
            ->orderBy('name');

        if ($request->filled('role')) {
            $q->where('user_type', $request->get('role'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->get('q'));
            $q->where(function ($inner) use ($term) {
                $inner->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('username', 'like', '%'.$term.'%')
                    ->orWhere('contact_number', 'like', '%'.$term.'%');
            });
        }

        return [
            'users' => $q->paginate(40)->withQueryString(),
            'roleOptions' => self::employeeRoleOptions(),
            'filterRole' => $request->get('role'),
            'filterQ' => $request->get('q'),
        ];
    }

    public function create()
    {
        return view('super-admin.account-creation.form', [
            'user' => null,
            'roleOptions' => self::employeeRoleOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $allowed = self::employeeRoleOptions()->keys()->all();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username',
            'contact_number' => 'required|string|max:30',
            'password' => 'required|string|min:6|confirmed',
            'user_type' => ['required', 'string', Rule::in($allowed)],
            'status' => 'nullable|in:0,1',
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

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => bcrypt($data['password']),
            'user_type' => $data['user_type'],
            'contact_number' => $contactNumber,
            'status' => (int) ($data['status'] ?? 1),
            'email_verified_at' => now(),
            'otp_verify_at' => now(),
        ]);
        $user->assignRole($data['user_type']);

        return redirect()
            ->route('super-admin.screens.show', ['screen' => 'account-creation'])
            ->withSuccess(__('message.save_form', ['form' => __('message.sub_admin')]));
    }

    public function edit($id)
    {
        $user = User::query()
            ->whereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin'])
            ->findOrFail($id);

        return view('super-admin.account-creation.form', [
            'user' => $user,
            'roleOptions' => self::employeeRoleOptions(null, $user->user_type),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::query()
            ->whereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin'])
            ->findOrFail($id);

        $allowed = self::employeeRoleOptions(null, $user->user_type)->keys()->all();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'contact_number' => 'required|string|max:30',
            'password' => 'nullable|string|min:6|confirmed',
            'user_type' => ['required', 'string', Rule::in($allowed)],
            'status' => 'nullable|in:0,1',
        ]);

        $contactNumber = $this->normalizeContactNumber($data['contact_number'] ?? null);
        if ($contactNumber === null) {
            return redirect()->back()->withInput()->withErrors([
                'contact_number' => __('message.contact_number').' is required.',
            ]);
        }

        if (User::withTrashed()->where('contact_number', $contactNumber)->where('id', '!=', $user->id)->exists()) {
            return redirect()->back()->withInput()->withErrors([
                'contact_number' => __('message.contact_number_already_taken'),
            ]);
        }

        $user->removeRole($user->user_type);
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'user_type' => $data['user_type'],
            'contact_number' => $contactNumber,
            'status' => (int) ($data['status'] ?? $user->status),
        ];
        if (! empty($data['password'])) {
            $payload['password'] = bcrypt($data['password']);
            $payload['is_temp_password'] = 0;
        }
        $user->fill($payload)->update();
        $user->assignRole($data['user_type']);

        return redirect()
            ->route('super-admin.screens.show', ['screen' => 'account-creation'])
            ->withSuccess(__('message.update_form', ['form' => __('message.sub_admin')]));
    }

    public function destroy($id)
    {
        $user = User::query()
            ->whereNotIn('user_type', ['admin', 'client', 'delivery_man', 'super_admin'])
            ->findOrFail($id);
        $user->delete();

        return redirect()
            ->route('super-admin.screens.show', ['screen' => 'account-creation'])
            ->withSuccess(__('message.delete_form', ['form' => __('message.sub_admin')]));
    }

    public function updateWorkStatus(Request $request, $id)
    {
        $request->validate(['work_on' => 'required|boolean']);

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

        $payroll = app(HrPayrollService::class);
        $restResult = [];
        if (! $workOn) {
            $restResult = $payroll->applyRestDayOffForUser($employee);
        } else {
            $restResult = $payroll->revertRestDayOffForUser($employee);
        }

        return response()->json([
            'message' => __('message.employee_work_status_updated'),
            'work_on' => (bool) $employee->isRiderWorkOn(),
            'label' => $employee->isRiderWorkOn()
                ? __('message.rider_work_on')
                : __('message.rider_work_off'),
            'rest_days' => $restResult['rest_days'] ?? null,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<string, string>
     */
    public static function employeeRoleOptions(?EmployeeType $employeeType = null, ?string $includeRole = null)
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
            return [$role->name => ucwords(str_replace('_', ' ', $role->name))];
        });
    }

    protected function normalizeContactNumber(?string $value): ?string
    {
        $number = normalizeContactNumber((string) $value);
        if ($number === '' || preg_match('/^\+\d{1,4}$/', $number)) {
            return null;
        }

        return $number;
    }
}

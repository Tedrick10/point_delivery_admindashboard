<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class BranchAdminController extends Controller
{
    /**
     * Destination branches used by Dispatch / Daily Check (မန္တလေး, ရန်ကုန်, …).
     */
    public static function operationalBranches()
    {
        return Branch::query()
            ->whereNull('deleted_at')
            ->where('status', 1)
            ->orderByRaw(destinationBranchOrderSql())
            ->orderBy('name')
            ->get(['id', 'name', 'city_name', 'status']);
    }

    public function index()
    {
        $branches = self::operationalBranches();

        $admins = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->whereIn('branch_id', $branches->pluck('id')->all() ?: [0])
            ->with('branch:id,name')
            ->orderBy('name')
            ->get();

        $adminsByBranch = $admins->keyBy('branch_id');

        return view('super-admin.branch-admins.index', compact('branches', 'admins', 'adminsByBranch'));
    }

    public function create(Request $request)
    {
        $branches = self::operationalBranches();
        $prefillBranchId = (int) $request->get('branch_id');
        if ($prefillBranchId > 0 && ! $branches->contains('id', $prefillBranchId)) {
            $prefillBranchId = 0;
        }

        return view('super-admin.branch-admins.form', [
            'admin' => null,
            'branches' => $branches,
            'prefillBranchId' => old('branch_id', $prefillBranchId),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            DB::transaction(function () use ($data) {
                $branch = $this->resolveBranch((int) $data['branch_id']);

                if ($this->branchHasAdmin((int) $branch->id)) {
                    throw new \RuntimeException(__('message.sa_branch_already_has_admin'));
                }

                $username = trim((string) ($data['username'] ?? ''));
                if ($username === '') {
                    $username = strtolower(preg_replace('/\s+/', '', $data['name']));
                }

                $user = User::create([
                    'name' => $data['name'],
                    'username' => $username,
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'contact_number' => function_exists('normalizeContactNumber')
                        ? normalizeContactNumber($data['contact_number'] ?? '')
                        : ($data['contact_number'] ?? null),
                    'user_type' => 'admin',
                    'branch_id' => (int) $branch->id,
                    'status' => (int) ($data['status'] ?? 1),
                    'email_verified_at' => now(),
                ]);

                $role = Role::findOrCreate('admin', 'web');
                $user->syncRoles([$role->name]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['branch_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('super-admin.branch-admins.index')
            ->with('success', 'Branch Admin account created.');
    }

    public function edit(int $id)
    {
        $admin = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->with('branch:id,name')
            ->findOrFail($id);

        return view('super-admin.branch-admins.form', [
            'admin' => $admin,
            'branches' => self::operationalBranches(),
            'prefillBranchId' => old('branch_id', (int) ($admin->branch_id ?? 0)),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $admin = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $data = $this->validated($request, $admin->id);

        try {
            DB::transaction(function () use ($data, $admin) {
                $branch = $this->resolveBranch((int) $data['branch_id']);

                if ($this->branchHasAdmin((int) $branch->id, $admin->id)) {
                    throw new \RuntimeException(__('message.sa_branch_already_has_admin'));
                }

                $payload = [
                    'name' => $data['name'],
                    'username' => trim((string) ($data['username'] ?? '')) ?: $admin->username,
                    'email' => $data['email'],
                    'contact_number' => function_exists('normalizeContactNumber')
                        ? normalizeContactNumber($data['contact_number'] ?? '')
                        : ($data['contact_number'] ?? null),
                    'branch_id' => (int) $branch->id,
                    'status' => (int) ($data['status'] ?? 1),
                    'user_type' => 'admin',
                ];

                if (! empty($data['password'])) {
                    $payload['password'] = Hash::make($data['password']);
                }

                $admin->update($payload);
                $admin->syncRoles(['admin']);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['branch_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('super-admin.branch-admins.index')
            ->with('success', 'Branch Admin account updated.');
    }

    public function destroy(int $id)
    {
        $admin = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $admin->delete();

        return redirect()
            ->route('super-admin.branch-admins.index')
            ->with('success', 'Branch Admin account removed.');
    }

    private function validated(Request $request, ?int $ignoreUserId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'username' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('users', 'username')->ignore($ignoreUserId)->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($ignoreUserId)->whereNull('deleted_at'),
            ],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['required', 'integer', Rule::in(self::operationalBranches()->pluck('id')->all())],
            'password' => [$ignoreUserId ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'status' => ['nullable', 'in:0,1'],
        ]);
    }

    private function resolveBranch(int $branchId): Branch
    {
        $branch = self::operationalBranches()->firstWhere('id', $branchId);
        if (! $branch) {
            throw new \RuntimeException(__('message.sa_select_branch'));
        }

        return Branch::query()->whereNull('deleted_at')->findOrFail($branchId);
    }

    private function branchHasAdmin(int $branchId, ?int $ignoreUserId = null): bool
    {
        return User::query()
            ->where('user_type', 'admin')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists();
    }
}

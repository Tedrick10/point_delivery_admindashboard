<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SuperAdmin\BranchAdminController as SuperAdminBranchAdminController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class BranchAdminController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('branch-admin-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $branches = SuperAdminBranchAdminController::operationalBranches();

        $admins = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->whereIn('branch_id', $branches->pluck('id')->all() ?: [0])
            ->with('branch:id,name')
            ->orderBy('name')
            ->get();

        $adminsByBranch = $admins->keyBy('branch_id');
        $pageTitle = __('message.sa_branch_admins');
        $canAdd = auth()->user()->can('branch-admin-add');
        $canEdit = auth()->user()->can('branch-admin-edit');
        $canDelete = auth()->user()->can('branch-admin-delete');

        return view('branch-admin.index', compact(
            'pageTitle',
            'branches',
            'admins',
            'adminsByBranch',
            'canAdd',
            'canEdit',
            'canDelete'
        ));
    }

    public function create(Request $request)
    {
        if (! auth()->user()->can('branch-admin-add')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $branches = SuperAdminBranchAdminController::operationalBranches();
        $prefillBranchId = (int) $request->get('branch_id');
        if ($prefillBranchId > 0 && ! $branches->contains('id', $prefillBranchId)) {
            $prefillBranchId = 0;
        }

        $pageTitle = __('message.sa_new_branch_admin');
        $occupiedBranchIds = $this->occupiedBranchIds();

        return view('branch-admin.form', [
            'pageTitle' => $pageTitle,
            'admin' => null,
            'branches' => $branches,
            'prefillBranchId' => old('branch_id', $prefillBranchId),
            'occupiedBranchIds' => $occupiedBranchIds,
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('branch-admin-add')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $data = $this->validated($request);

        try {
            DB::transaction(function () use ($data) {
                $branch = $this->resolveBranch((int) $data['branch_id']);

                if ($this->branchHasAdmin((int) $branch->id)) {
                    throw new \RuntimeException(__('message.sa_branch_already_has_admin'));
                }

                $username = $this->uniqueUsername(
                    trim((string) ($data['username'] ?? '')),
                    (string) $data['name'],
                    (int) $branch->id
                );

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
                    'approval_status' => User::APPROVAL_APPROVED,
                    'email_verified_at' => now(),
                    'created_by_admin' => 1,
                ]);

                $role = Role::findOrCreate('admin', 'web');
                $user->syncRoles([$role->name]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['branch_id' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::warning('branch-admin.store failed', ['error' => $e->getMessage()]);

            return back()->withInput()->withErrors([
                'email' => __('message.something_went_wrong').' ('.$e->getMessage().')',
            ]);
        }

        return redirect()
            ->route('branch-admins.index')
            ->withSuccess(__('message.save_form', ['form' => __('message.sa_branch_admins')]));
    }

    public function edit(int $id)
    {
        if (! auth()->user()->can('branch-admin-edit')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $admin = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->with('branch:id,name')
            ->findOrFail($id);

        $pageTitle = __('message.sa_edit_branch_admin');

        return view('branch-admin.form', [
            'pageTitle' => $pageTitle,
            'admin' => $admin,
            'branches' => SuperAdminBranchAdminController::operationalBranches(),
            'prefillBranchId' => old('branch_id', (int) ($admin->branch_id ?? 0)),
            'occupiedBranchIds' => $this->occupiedBranchIds((int) $admin->id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        if (! auth()->user()->can('branch-admin-edit')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

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

                $username = trim((string) ($data['username'] ?? ''));
                if ($username === '') {
                    $username = (string) $admin->username;
                } else {
                    $username = $this->uniqueUsername($username, (string) $data['name'], (int) $branch->id, (int) $admin->id);
                }

                $payload = [
                    'name' => $data['name'],
                    'username' => $username,
                    'email' => $data['email'],
                    'contact_number' => function_exists('normalizeContactNumber')
                        ? normalizeContactNumber($data['contact_number'] ?? '')
                        : ($data['contact_number'] ?? null),
                    'branch_id' => (int) $branch->id,
                    'status' => (int) ($data['status'] ?? 1),
                    'user_type' => 'admin',
                    'approval_status' => User::APPROVAL_APPROVED,
                ];

                if (! empty($data['password'])) {
                    $payload['password'] = Hash::make($data['password']);
                }

                $admin->update($payload);
                $admin->syncRoles(['admin']);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['branch_id' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::warning('branch-admin.update failed', ['error' => $e->getMessage()]);

            return back()->withInput()->withErrors([
                'email' => __('message.something_went_wrong').' ('.$e->getMessage().')',
            ]);
        }

        return redirect()
            ->route('branch-admins.index')
            ->withSuccess(__('message.update_form', ['form' => __('message.sa_branch_admins')]));
    }

    public function destroy(int $id)
    {
        if (! auth()->user()->can('branch-admin-delete')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $admin = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $admin->delete();

        return redirect()
            ->route('branch-admins.index')
            ->withSuccess(__('message.delete_form', ['form' => __('message.sa_branch_admins')]));
    }

    private function validated(Request $request, ?int $ignoreUserId = null): array
    {
        // Match DB unique indexes (include soft-deleted rows) so validation
        // fails clearly instead of a silent insert error.
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'username' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('users', 'username')->ignore($ignoreUserId),
            ],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($ignoreUserId),
            ],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['required', 'integer', Rule::in(SuperAdminBranchAdminController::operationalBranches()->pluck('id')->all())],
            'password' => [$ignoreUserId ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'status' => ['nullable', 'in:0,1'],
        ]);
    }

    private function resolveBranch(int $branchId)
    {
        $branch = SuperAdminBranchAdminController::operationalBranches()->firstWhere('id', $branchId);
        if (! $branch) {
            throw new \RuntimeException(__('message.sa_select_branch'));
        }

        return \App\Models\Branch::query()->whereNull('deleted_at')->findOrFail($branchId);
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

    /**
     * @return list<int>
     */
    private function occupiedBranchIds(?int $ignoreUserId = null): array
    {
        return User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->whereNotNull('branch_id')
            ->where('branch_id', '>', 0)
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->pluck('branch_id')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function uniqueUsername(string $requested, string $name, int $branchId, ?int $ignoreUserId = null): string
    {
        $base = trim($requested);
        if ($base === '') {
            $base = strtolower((string) preg_replace('/\s+/u', '', $name));
            $base = preg_replace('/[^a-z0-9_\.\-@]/i', '', $base) ?? '';
        }
        if ($base === '') {
            $base = 'branch'.$branchId.'_admin';
        }

        $candidate = $base;
        $i = 0;
        while (
            User::withTrashed()
                ->where('username', $candidate)
                ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
                ->exists()
        ) {
            $i++;
            $candidate = $base.'_'.$i.($i > 3 ? Str::lower(Str::random(3)) : '');
            if ($i > 20) {
                $candidate = 'ba'.$branchId.'_'.Str::lower(Str::random(8));
                break;
            }
        }

        return $candidate;
    }
}

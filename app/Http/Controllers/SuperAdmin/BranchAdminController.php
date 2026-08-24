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
    /** Regional branches shown on Accounts by branch. */
    public const REGIONAL_BRANCHES = [
        'Yangon Branch',
        'Naypyitaw Branch',
        'Taungyi Branch',
        'MDY Branch',
    ];

    public function index()
    {
        $this->ensureRegionalBranches();

        $order = array_flip(self::REGIONAL_BRANCHES);
        $branches = Branch::query()
            ->whereNull('deleted_at')
            ->whereIn('name', self::REGIONAL_BRANCHES)
            ->get()
            ->sortBy(fn (Branch $b) => $order[$b->name] ?? 99)
            ->values();

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
        $this->ensureRegionalBranches();

        $branchName = '';
        $prefillBranchId = (int) $request->get('branch_id');
        if ($prefillBranchId > 0) {
            $branchName = (string) (Branch::query()->whereKey($prefillBranchId)->value('name') ?? '');
            if (! in_array($branchName, self::REGIONAL_BRANCHES, true)) {
                $branchName = '';
            }
        }

        return view('super-admin.branch-admins.form', [
            'admin' => null,
            'branchName' => old('branch_name', $branchName),
            'regionalBranches' => self::REGIONAL_BRANCHES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            DB::transaction(function () use ($data) {
                $branch = $this->resolveBranchByName($data['branch_name']);

                if ($this->branchHasAdmin((int) $branch->id)) {
                    throw new \RuntimeException('This branch already has an Admin account (1 Admin per Branch).');
                }

                $user = User::create([
                    'name' => $data['name'],
                    'username' => $data['username'] ?? strtolower(str_replace(' ', '', $data['name'])),
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'contact_number' => $data['contact_number'] ?? null,
                    'user_type' => 'admin',
                    'branch_id' => (int) $branch->id,
                    'status' => (int) ($data['status'] ?? 1),
                    'email_verified_at' => now(),
                ]);

                $role = Role::findOrCreate('admin', 'web');
                $user->syncRoles([$role->name]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['branch_name' => $e->getMessage()]);
        }

        return redirect()
            ->route('super-admin.branch-admins.index')
            ->with('success', 'Branch Admin account created.');
    }

    public function edit(int $id)
    {
        $this->ensureRegionalBranches();

        $admin = User::query()
            ->where('user_type', 'admin')
            ->whereNull('deleted_at')
            ->with('branch:id,name')
            ->findOrFail($id);

        return view('super-admin.branch-admins.form', [
            'admin' => $admin,
            'branchName' => old('branch_name', $admin->branch->name ?? ''),
            'regionalBranches' => self::REGIONAL_BRANCHES,
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
                $branch = $this->resolveBranchByName($data['branch_name']);

                if ($this->branchHasAdmin((int) $branch->id, $admin->id)) {
                    throw new \RuntimeException('This branch already has an Admin account (1 Admin per Branch).');
                }

                $payload = [
                    'name' => $data['name'],
                    'username' => $data['username'] ?? $admin->username,
                    'email' => $data['email'],
                    'contact_number' => $data['contact_number'] ?? null,
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
            return back()->withInput()->withErrors(['branch_name' => $e->getMessage()]);
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
            'username' => ['nullable', 'string', 'max:191'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($ignoreUserId)->whereNull('deleted_at'),
            ],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'branch_name' => ['required', 'string', 'max:191', Rule::in(self::REGIONAL_BRANCHES)],
            'password' => [$ignoreUserId ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'status' => ['nullable', 'in:0,1'],
        ]);
    }

    private function resolveBranchByName(string $name): Branch
    {
        $name = trim($name);
        if (! in_array($name, self::REGIONAL_BRANCHES, true)) {
            throw new \RuntimeException('Please choose Yangon Branch, Naypyitaw Branch, Taungyi Branch, or MDY Branch.');
        }

        $this->ensureRegionalBranches();

        $existing = Branch::query()
            ->whereNull('deleted_at')
            ->where('name', $name)
            ->first();

        if ($existing) {
            if ((int) $existing->status !== 1) {
                $existing->update(['status' => 1]);
            }

            return $existing;
        }

        return Branch::create([
            'name' => $name,
            'status' => 1,
        ]);
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

    private function ensureRegionalBranches(): void
    {
        foreach (self::REGIONAL_BRANCHES as $name) {
            $branch = Branch::withTrashed()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if ($branch) {
                if ($branch->trashed()) {
                    $branch->restore();
                }
                if ($branch->name !== $name || (int) $branch->status !== 1) {
                    $branch->update(['name' => $name, 'status' => 1]);
                }
                continue;
            }

            // Prefer renaming legacy short names → "* Branch" (keep ids + admins).
            $legacyMap = [
                'Yangon Branch' => ['yangon'],
                'MDY Branch' => ['mdy'],
            ];
            foreach ($legacyMap[$name] ?? [] as $legacyName) {
                $legacy = Branch::withTrashed()
                    ->whereRaw('LOWER(name) = ?', [$legacyName])
                    ->first();
                if ($legacy) {
                    if ($legacy->trashed()) {
                        $legacy->restore();
                    }
                    $legacy->update(['name' => $name, 'status' => 1]);
                    continue 2;
                }
            }

            Branch::create(['name' => $name, 'status' => 1]);
        }
    }
}

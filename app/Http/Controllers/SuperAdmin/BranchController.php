<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::query()
            ->whereNull('deleted_at')
            ->orderByRaw(destinationBranchOrderSql())
            ->orderBy('name')
            ->get();

        return view('super-admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('super-admin.branches.form', [
            'branch' => null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Branch::query()->create($this->payload($data));

        return redirect()
            ->route('super-admin.branches.index')
            ->with('success', __('message.save_form', ['form' => __('message.branch')]));
    }

    public function edit(int $id)
    {
        $branch = Branch::query()->whereNull('deleted_at')->findOrFail($id);

        return view('super-admin.branches.form', compact('branch'));
    }

    public function update(Request $request, int $id)
    {
        $branch = Branch::query()->whereNull('deleted_at')->findOrFail($id);
        $data = $this->validated($request, $branch->id);
        $branch->fill($this->payload($data))->save();

        return redirect()
            ->route('super-admin.branches.index')
            ->with('success', __('message.update_form', ['form' => __('message.branch')]));
    }

    public function destroy(int $id)
    {
        $branch = Branch::query()->whereNull('deleted_at')->findOrFail($id);
        $branch->delete();

        return redirect()
            ->route('super-admin.branches.index')
            ->with('success', __('message.delete_form', ['form' => __('message.branch')]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $branchId = null): array
    {
        $uniqueName = Rule::unique('branches', 'name')->whereNull('deleted_at');
        if ($branchId) {
            $uniqueName = $uniqueName->ignore($branchId);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'code' => ['nullable', 'string', 'max:32'],
            'city_name' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:40'],
            'status' => ['required', 'in:0,1'],
        ];

        if (Schema::hasColumn('branches', 'delivery_settlement_mode')) {
            $rules['delivery_settlement_mode'] = ['nullable', 'string', 'in:'.implode(',', Branch::SETTLEMENT_MODES)];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $name = trim((string) $data['name']);
        $city = trim((string) ($data['city_name'] ?? ''));
        if ($city === '') {
            $city = $name;
        }

        $payload = [
            'name' => $name,
            'code' => trim((string) ($data['code'] ?? '')) ?: null,
            'city_name' => $city,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'status' => (int) ($data['status'] ?? 1),
        ];

        if (Schema::hasColumn('branches', 'delivery_settlement_mode')) {
            $payload['delivery_settlement_mode'] = Branch::normalizeSettlementMode(
                $data['delivery_settlement_mode'] ?? Branch::SETTLEMENT_MANUAL
            );
        }

        return $payload;
    }
}

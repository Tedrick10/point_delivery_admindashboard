<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DeliveryCity;
use App\Models\DeliveryTownship;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeliveryRouteLocationController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->can('order-list')) {
            return redirect()->route('home')->withErrors(__('message.demo_permission_denied'));
        }

        $tab = trim((string) $request->get('tab', 'from_to'));
        if (! in_array($tab, ['from_to', 'city', 'township'], true)) {
            $tab = 'from_to';
        }

        $branches = Branch::query()->orderByRaw(destinationBranchOrderSql())->orderBy('name')->get();
        $cities = DeliveryCity::query()->withCount('townships')->orderBy('sort_order')->orderBy('name')->get();
        $filterCityId = (int) $request->get('city_id', 0);
        if ($filterCityId <= 0) {
            $filterCityId = (int) ($cities->first()?->id ?? 0);
        }

        $townships = DeliveryTownship::query()
            ->with('city')
            ->when($filterCityId > 0, fn ($q) => $q->where('delivery_city_id', $filterCityId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $pageTitle = __('message.delivery_route_locations_title');
        $assets = [];
        $canEdit = auth()->user()->can('order-edit');

        return view('setting.delivery-route-locations', compact(
            'pageTitle',
            'assets',
            'tab',
            'branches',
            'cities',
            'townships',
            'filterCityId',
            'canEdit'
        ));
    }

    public function storeBranch(Request $request)
    {
        $this->assertCanEdit();
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,NULL,id,deleted_at,NULL',
        ]);

        $name = trim($data['name']);
        $branch = Branch::query()->create([
            'name' => $name,
            'city_name' => $name,
            'status' => 1,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.save_form', ['form' => __('message.from').' / '.__('message.to')]),
                'branch' => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'city_name' => $branch->city_name,
                ],
            ]);
        }

        return $this->ok($request, __('message.save_form', ['form' => __('message.from').' / '.__('message.to')]), 'from_to');
    }

    public function updateBranch(Request $request, int $id)
    {
        $this->assertCanEdit();
        $branch = Branch::query()->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:branches,name,'.$branch->id.',id,deleted_at,NULL',
            'status' => 'nullable|in:0,1',
        ]);

        $name = trim($data['name']);
        $branch->fill([
            'name' => $name,
            'city_name' => $name,
            'status' => array_key_exists('status', $data) ? (int) $data['status'] : $branch->status,
        ])->save();

        return $this->ok($request, __('message.update_form', ['form' => __('message.from').' / '.__('message.to')]), 'from_to');
    }

    public function destroyBranch(Request $request, int $id)
    {
        $this->assertCanEdit();
        Branch::query()->findOrFail($id)->delete();

        return $this->ok($request, __('message.delete_form', ['form' => __('message.from').' / '.__('message.to')]), 'from_to');
    }

    public function storeCity(Request $request)
    {
        $this->assertCanEdit();
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:delivery_cities,name',
            'name_mm' => 'nullable|string|max:120',
        ]);

        $city = DeliveryCity::query()->create([
            'name' => trim($data['name']),
            'name_mm' => trim((string) ($data['name_mm'] ?? '')) ?: trim($data['name']),
            'sort_order' => (int) DeliveryCity::query()->max('sort_order') + 1,
            'status' => 1,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.save_form', ['form' => __('message.city')]),
                'city' => $this->cityPayload($city),
            ]);
        }

        return redirect()->route('delivery-route-locations.index', ['tab' => 'city'])
            ->withSuccess(__('message.save_form', ['form' => __('message.city')]));
    }

    public function updateCity(Request $request, int $id)
    {
        $this->assertCanEdit();
        $city = DeliveryCity::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('delivery_cities', 'name')->ignore($city->id)],
            'name_mm' => 'nullable|string|max:120',
            'status' => 'nullable|in:0,1',
        ]);

        $label = trim((string) ($data['name_mm'] ?? '')) ?: trim($data['name']);
        $english = trim($data['name']);
        if ($english === '' || $english === (string) $city->name_mm) {
            $english = $label;
        }

        $city->fill([
            'name' => $english,
            'name_mm' => $label,
            'status' => array_key_exists('status', $data) ? (int) $data['status'] : $city->status,
        ])->save();

        return $this->ok($request, __('message.update_form', ['form' => __('message.city')]), 'city');
    }

    public function destroyCity(Request $request, int $id)
    {
        $this->assertCanEdit();
        $city = DeliveryCity::query()->findOrFail($id);
        $city->townships()->delete();
        $city->delete();

        return $this->ok($request, __('message.delete_form', ['form' => __('message.city')]), 'city');
    }

    public function storeTownship(Request $request)
    {
        $this->assertCanEdit();
        $data = $request->validate([
            'delivery_city_id' => 'required|exists:delivery_cities,id',
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('delivery_townships', 'name')->where('delivery_city_id', $request->delivery_city_id),
            ],
            'name_mm' => 'nullable|string|max:120',
        ]);

        $township = DeliveryTownship::query()->create([
            'delivery_city_id' => (int) $data['delivery_city_id'],
            'name' => trim($data['name']),
            'name_mm' => trim((string) ($data['name_mm'] ?? '')) ?: trim($data['name']),
            'sort_order' => (int) DeliveryTownship::query()->where('delivery_city_id', $data['delivery_city_id'])->max('sort_order') + 1,
            'status' => 1,
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.save_form', ['form' => __('message.township')]),
                'township' => $this->townshipPayload($township),
            ]);
        }

        return redirect()->route('delivery-route-locations.index', [
            'tab' => 'township',
            'city_id' => $township->delivery_city_id,
        ])->withSuccess(__('message.save_form', ['form' => __('message.township')]));
    }

    public function updateTownship(Request $request, int $id)
    {
        $this->assertCanEdit();
        $township = DeliveryTownship::query()->findOrFail($id);
        $data = $request->validate([
            'delivery_city_id' => 'required|exists:delivery_cities,id',
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('delivery_townships', 'name')
                    ->where('delivery_city_id', $request->delivery_city_id)
                    ->ignore($township->id),
            ],
            'name_mm' => 'nullable|string|max:120',
            'status' => 'nullable|in:0,1',
        ]);

        $township->fill([
            'delivery_city_id' => (int) $data['delivery_city_id'],
            'name' => trim($data['name']),
            'name_mm' => trim((string) ($data['name_mm'] ?? '')) ?: trim($data['name']),
            'status' => array_key_exists('status', $data) ? (int) $data['status'] : $township->status,
        ])->save();

        return redirect()->route('delivery-route-locations.index', [
            'tab' => 'township',
            'city_id' => $township->delivery_city_id,
        ])->withSuccess(__('message.update_form', ['form' => __('message.township')]));
    }

    public function destroyTownship(Request $request, int $id)
    {
        $this->assertCanEdit();
        $township = DeliveryTownship::query()->findOrFail($id);
        $cityId = $township->delivery_city_id;
        $township->delete();

        return redirect()->route('delivery-route-locations.index', [
            'tab' => 'township',
            'city_id' => $cityId,
        ])->withSuccess(__('message.delete_form', ['form' => __('message.township')]));
    }

    public function townships(Request $request)
    {
        $city = $this->findCityByRequest($request);
        $townships = $city
            ? $city->townships()->active()->get()
            : collect();

        return response()->json([
            'city' => $city ? $this->cityPayload($city) : null,
            'townships' => $townships->map(fn (DeliveryTownship $row) => $this->townshipPayload($row))->values(),
        ]);
    }

    protected function findCityByRequest(Request $request): ?DeliveryCity
    {
        $id = (int) $request->get('city_id', 0);
        if ($id > 0) {
            return DeliveryCity::query()->active()->find($id);
        }

        $name = trim((string) $request->get('city', ''));
        if ($name === '') {
            return null;
        }

        return DeliveryCity::query()
            ->active()
            ->get()
            ->first(fn (DeliveryCity $city) => $city->matchesName($name));
    }

    protected function cityPayload(DeliveryCity $city): array
    {
        return [
            'id' => $city->id,
            'name' => $city->name,
            'name_mm' => $city->name_mm,
            'label' => $city->displayName(),
        ];
    }

    protected function townshipPayload(DeliveryTownship $township): array
    {
        return [
            'id' => $township->id,
            'delivery_city_id' => $township->delivery_city_id,
            'name' => $township->name,
            'name_mm' => $township->name_mm,
            'label' => $township->displayName(),
        ];
    }

    protected function assertCanEdit(): void
    {
        if (! auth()->user()->can('order-edit')) {
            abort(403, __('message.demo_permission_denied'));
        }
    }

    protected function ok(Request $request, string $message, string $tab)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('delivery-route-locations.index', ['tab' => $tab])->withSuccess($message);
    }
}

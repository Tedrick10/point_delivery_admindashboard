<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\DeliveryRouteLocationController as BaseDeliveryRouteLocationController;
use App\Models\Branch;
use App\Models\DeliveryCity;
use App\Models\DeliveryTownship;
use Illuminate\Http\Request;

/**
 * From / To / City CRUD — Super Admin panel only.
 */
class DeliveryRouteController extends BaseDeliveryRouteLocationController
{
    public function index(Request $request)
    {
        return redirect()->route('super-admin.screens.show', [
            'screen' => 'delivery-route',
            'tab' => $request->get('tab', 'from_to'),
            'city_id' => $request->get('city_id'),
        ]);
    }

    protected function ok(Request $request, string $message, string $tab)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message]);
        }

        $params = ['screen' => 'delivery-route', 'tab' => $tab];
        if ($tab === 'township') {
            $cityId = (int) $request->input('delivery_city_id', $request->get('city_id', 0));
            if ($cityId > 0) {
                $params['city_id'] = $cityId;
            }
        }

        return redirect()
            ->route('super-admin.screens.show', $params)
            ->withSuccess($message);
    }

    /**
     * Shared payload for the Super Admin screen embed.
     *
     * @return array{tab:string,branches:\Illuminate\Support\Collection,cities:\Illuminate\Support\Collection,townships:\Illuminate\Support\Collection,filterCityId:int,canEdit:bool,drRoutes:array<string,string>}
     */
    public static function screenPayload(Request $request): array
    {
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

        return [
            'tab' => $tab,
            'branches' => $branches,
            'cities' => $cities,
            'townships' => $townships,
            'filterCityId' => $filterCityId,
            'canEdit' => true,
            'drRoutes' => [
                'index' => 'super-admin.screens.show',
                'branches.store' => 'super-admin.delivery-route.branches.store',
                'branches.update' => 'super-admin.delivery-route.branches.update',
                'branches.destroy' => 'super-admin.delivery-route.branches.destroy',
                'cities.store' => 'super-admin.delivery-route.cities.store',
                'cities.update' => 'super-admin.delivery-route.cities.update',
                'cities.destroy' => 'super-admin.delivery-route.cities.destroy',
                'townships.store' => 'super-admin.delivery-route.townships.store',
                'townships.update' => 'super-admin.delivery-route.townships.update',
                'townships.destroy' => 'super-admin.delivery-route.townships.destroy',
            ],
        ];
    }
}

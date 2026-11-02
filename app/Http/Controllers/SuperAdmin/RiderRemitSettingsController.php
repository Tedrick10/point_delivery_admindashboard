<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RiderRemitService;
use Illuminate\Http\Request;

class RiderRemitSettingsController extends Controller
{
    public function saveDefaultFuel(Request $request, RiderRemitService $service)
    {
        $data = $request->validate([
            'fuel_amount' => 'required|numeric|min:0',
            'fuel_min_ways' => 'required|integer|min:1|max:1000',
            'fuel_start_date' => 'nullable|date',
        ]);

        $oldDefault = $service->defaultFuelAmount();
        $amount = $service->setDefaultFuelAmount((float) $data['fuel_amount']);
        $service->setFuelMinWays((int) $data['fuel_min_ways']);
        $startDate = $service->setFuelStartDate(
            array_key_exists('fuel_start_date', $data) ? (string) ($data['fuel_start_date'] ?? '') : null
        );

        $day = now('Asia/Yangon')->toDateString();
        $service->applyDefaultFuelToOpenRemits($day, null, $oldDefault, $amount);
        // Recompute all open remits so days before fuel_start_date become 0.
        $service->resyncOpenRemitFuel((int) ($request->user()?->id ?? 0) ?: null);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_fuel_default_saved'),
                'default_fuel' => $amount,
                'fuel_min_ways' => $service->fuelMinWays(),
                'fuel_start_date' => $startDate,
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'rider-remit')
            ->withSuccess(__('message.sa_fuel_default_saved'));
    }

    public function updateRiderFuel(Request $request, $id, RiderRemitService $service)
    {
        $rider = User::query()
            ->where('user_type', 'delivery_man')
            ->where('status', 1)
            ->findOrFail($id);

        $data = $request->validate([
            'fuel_amount' => 'required|numeric|min:0|max:10000000',
            'fuel_min_ways' => 'required|integer|min:1|max:1000',
        ]);

        $service->setRiderFuelMinWays((int) $rider->id, (int) $data['fuel_min_ways']);
        $amount = $service->setRiderFuelAmount((int) $rider->id, (float) $data['fuel_amount']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_rider_fuel_saved'),
                'rider_id' => (int) $rider->id,
                'fuel_amount' => $amount,
                'fuel_min_ways' => $service->riderFuelMinWays((int) $rider->id),
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'rider-remit')
            ->withSuccess(__('message.sa_rider_fuel_saved'));
    }
}

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
        ]);

        $oldDefault = $service->defaultFuelAmount();
        $amount = $service->setDefaultFuelAmount((float) $data['fuel_amount']);

        $day = now('Asia/Yangon')->toDateString();
        $service->applyDefaultFuelToOpenRemits($day, null, $oldDefault, $amount);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_fuel_default_saved'),
                'default_fuel' => $amount,
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
        ]);

        $amount = $service->setRiderFuelAmount((int) $rider->id, (float) $data['fuel_amount']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('message.sa_rider_fuel_saved'),
                'rider_id' => (int) $rider->id,
                'fuel_amount' => $amount,
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'rider-remit')
            ->withSuccess(__('message.sa_rider_fuel_saved'));
    }
}

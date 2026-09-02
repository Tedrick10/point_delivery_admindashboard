<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\RiderRemitService;
use Illuminate\Http\Request;

class RiderRemitSettingsController extends Controller
{
    public function saveDefaultFuel(Request $request, RiderRemitService $service)
    {
        $data = $request->validate([
            'fuel_amount' => 'required|numeric|min:0',
        ]);

        $amount = $service->setDefaultFuelAmount((float) $data['fuel_amount']);

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
}

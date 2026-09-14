<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\KyoShinService;
use Illuminate\Http\Request;

class KyoShinSettingsController extends Controller
{
    public function saveTotal(Request $request, KyoShinService $service)
    {
        $data = $request->validate([
            'scope_key' => 'required|string|in:mdy,ygn_nls,ygn_m2m',
            'total_amount' => 'required|numeric|min:0|max:100000000000',
        ]);

        $service->setTotal($data['scope_key'], (float) $data['total_amount'], $request->user());

        if ($request->expectsJson() || $request->ajax()) {
            $summary = $service->summary($data['scope_key']);

            return response()->json([
                'message' => __('message.kyo_shin_total_saved'),
                'scope_key' => $data['scope_key'],
                'total_amount' => $summary['total'],
                'remain' => $summary['remain'],
            ]);
        }

        return redirect()
            ->route('super-admin.screens.show', 'kyo-shin')
            ->withSuccess(__('message.kyo_shin_total_saved'));
    }
}

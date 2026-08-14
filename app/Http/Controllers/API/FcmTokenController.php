<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    /**
     * Save device FCM token for the authenticated user.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            return json_custom_response([
                'status' => false,
                'message' => __('message.demo_permission_denied'),
            ], 403);
        }

        $data = $request->validate([
            'fcm_token' => 'required|string|max:512',
        ]);

        $user->forceFill([
            'fcm_token' => trim($data['fcm_token']),
        ])->save();

        return json_custom_response([
            'status' => true,
            'message' => 'FCM token updated',
        ]);
    }
}

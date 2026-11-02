<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return view('super-admin.profile.show', [
            'user' => $user,
            'profileImage' => getSingleMedia($user, 'profile_image', null),
            'saPageTitleDefault' => __('message.my_profile'),
            'saPageSubDefault' => __('message.sa_super_admin'),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ], [
            'password.confirmed' => __('message.password_does_not_match'),
        ]);

        $user->name = trim((string) $data['name']);

        $passwordChanged = false;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $passwordChanged = true;
        }

        $user->save();

        if ($request->hasFile('profile_image')) {
            uploadMediaFile($user, $request->file('profile_image'), 'profile_image');
        }

        $message = __('message.profile').' '.__('message.updated');
        if ($passwordChanged) {
            $message .= ' · '.__('message.sa_profile_password_synced');
        }

        return redirect()
            ->route('super-admin.profile.show')
            ->withSuccess($message);
    }
}

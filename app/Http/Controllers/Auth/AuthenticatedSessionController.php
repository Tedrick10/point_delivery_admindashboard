<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\LoginHistoryTrait;

class AuthenticatedSessionController extends Controller
{
    use LoginHistoryTrait;
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return redirect()->route('frontend-section');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || empty($user)) {

            if (isset($request->signinModal) && $request->signinModal === 'signinModal') {
                return redirect()->route('frontend-section')->withErrors(__('message.invalid_email'));
            }
            return redirect()->route('admin-login')->withErrors(__('message.invalid_email'));
        }

        $user_email = $user->email;
        $otp_verification_status = config('constant.MAIL_SETTING.EMAIL_OTP_VERIFICATION');

        if ($user && $otp_verification_status == 'enable') {
            // Send Mail
            sentOTP_mail($user, $otp_verification_status);
            // Store in session
            session(['otp_email' => $user_email]);

            return redirect()->route('verify-otp');
        }

        $request->authenticate();
        $request->session()->regenerate();
        $user = Auth::user();
        if ($user->user_type != 'client') {
            $this->saveLoginHistory($user);
        }

        if (isset($request->admin_login) && $request->admin_login === "admin_login") {
            if ($user->hasRole('admin')) {
                return redirect()->route('home');
            } elseif ($user->hasRole('delivery_man')) {
                Auth::logout();
                return redirect()->route('admin-login')->withErrors(__('message.delivery_man_not_login'));
            } elseif ($user->hasRole('client')) {
                Auth::logout();
                return redirect()->route('admin-login')->withErrors(__('message.client_not_login'));
            }
        }

        if (isset($request->signinModal) && $request->signinModal === "signinModal") {
            if ($user->hasRole('admin')) {
                Auth::logout();
                return redirect()->route('frontend-section')->with('user_type', 'admin');
            } elseif ($user->hasRole('delivery_man')) {
                Auth::logout();
                return redirect()->route('frontend-section')->with('user_type', 'delivery_man');
            } elseif ($user->hasRole('client')) {
                return redirect()->route('home')->with('user_type', 'client');
            }
        }

        return redirect(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    function getCountryDetailsByCode($code)
    {
        $countries = country();
        foreach ($countries as $country) {

          if (is_array($country) && isset($country['countryCode'])) {
            if (strtoupper($country['countryCode']) === strtoupper($code)) {
                return $country;
            }
          }
        }

        return null;
    }
}

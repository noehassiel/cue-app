<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Native\Mobile\Facades\Biometrics;
use Native\Mobile\Facades\PushNotifications;
use Native\Mobile\Facades\SecureStorage;

class MobileController extends Controller
{
    /**
     * App startup: check for stored token and redirect accordingly.
     */
    public function startup(): RedirectResponse
    {
        $token = SecureStorage::get('sanctum_token');

        if ($token) {
            session(['api_token' => $token]);

            return redirect()->route('mobile.dashboard');
        }

        return redirect()->route('mobile.login');
    }

    public function login(): View
    {
        return view('mobile.auth.login', [
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }

    public function register(): View
    {
        return view('mobile.auth.register', [
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }

    /**
     * Store the Sanctum token returned by the production API.
     */
    public function storeToken(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = $request->input('token');

        SecureStorage::set('sanctum_token', $token);
        session(['api_token' => $token]);

        return redirect()->route('mobile.pushEnroll');
    }

    /**
     * Trigger biometric prompt and redirect to dashboard on success.
     */
    public function biometricLogin(): View
    {
        $biometricId = Biometrics::prompt()->id('login')->remember()->getId();

        return view('mobile.auth.biometric', [
            'biometricId' => $biometricId,
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }

    /**
     * Request push notification permission and enroll the device.
     * Called once after successful login.
     */
    public function pushEnroll(): RedirectResponse
    {
        PushNotifications::enroll()->id('cue-push')->remember();

        return redirect()->route('mobile.dashboard');
    }

    public function logout(): RedirectResponse
    {
        SecureStorage::delete('sanctum_token');
        session()->forget('api_token');

        return redirect()->route('mobile.login');
    }

    public function dashboard(): View
    {
        return view('mobile.dashboard', [
            'apiToken' => session('api_token', ''),
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }

    public function ledger(): View
    {
        return view('mobile.ledger', [
            'apiToken' => session('api_token', ''),
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }

    public function funds(): View
    {
        return view('mobile.funds', [
            'apiToken' => session('api_token', ''),
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }

    public function debts(): View
    {
        return view('mobile.debts', [
            'apiToken' => session('api_token', ''),
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }

    public function settings(): View
    {
        return view('mobile.settings', [
            'apiToken' => session('api_token', ''),
            'apiBaseUrl' => config('app.api_base_url'),
        ]);
    }
}

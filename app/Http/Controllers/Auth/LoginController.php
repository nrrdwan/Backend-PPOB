<?php

namespace App\Http\Controllers\Auth;

use Backpack\CRUD\app\Http\Controllers\Auth\LoginController as BackpackLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends BackpackLoginController
{

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $credentials = $request->only('email', 'password');

        // 🔹 PAKAI GUARD WEB SECARA EKSPILISIT
        if (Auth::guard('backpack')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::guard('backpack')->user();

            if (!$user->is_active) {
                Auth::guard('backpack')->logout();
                return back()->withErrors(['email' => 'Akun Anda belum aktif.']);
            }

            if ($user->role !== 'admin') {
                Log::warning('Login attempt by non-Admin user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                Auth::guard('backpack')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => "Akses ditolak. Role '{$user->role}' tidak diizinkan."
                ]);
            }

            Log::info('✅ Admin login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return redirect()->intended(backpack_url('dashboard'));
        }

        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        // Additional check after authentication
        if ($user->role !== 'Admin') {
            $this->guard()->logout();
            return redirect()->route('backpack.auth.login')
                ->with('error', 'Akses Ditolak! Hanya Admin yang dapat mengakses panel admin.');
        }

        // Log successful admin login
        Log::info('Admin login successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip()
        ]);

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('backpack');
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return string|null
     */
    public function broker()
    {
        return config('backpack.base.passwords', config('auth.defaults.passwords'));
    }

    /**
     * Where to redirect users after login.
     *
     * @return string
     */
    protected function redirectTo()
    {
        return backpack_url('dashboard');
    }
}

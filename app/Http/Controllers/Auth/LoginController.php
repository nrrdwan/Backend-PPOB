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

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // Attempt to authenticate user
        if ($this->attemptLogin($request)) {
            $user = $this->guard()->user();
            
            // Check if user role is Admin
            if ($user->role !== 'Admin') {
                // Log the unauthorized attempt
                Log::warning('Login attempt by non-Admin user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                
                // Logout the user immediately
                $this->guard()->logout();
                
                // Regenerate session to prevent fixation
                $request->session()->regenerate();
                
                // Redirect to access denied page with role info
                return redirect()->route('access.denied', ['role' => $user->role]);
            }
            
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful, we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
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
        $guardName = backpack_guard_name();
        if (!$guardName || is_array($guardName)) {
            $guardName = 'web';
        }
        return Auth::guard($guardName);
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

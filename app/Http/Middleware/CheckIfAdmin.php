<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class CheckIfAdmin
{
    /**
     * Checked that the logged in user is an administrator.
     *
     * --------------
     * VERY IMPORTANT
     * --------------
     * Only users with 'Admin' role can access admin panel.
     * This ensures maximum security for the PPOB admin system.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return bool
     */
    private function checkIfUserIsAdmin($user)
    {
        // Only allow users with 'Admin' role
        return $user && $user->role === 'admin' && $user->is_active;
    }

    /**
     * Answer to unauthorized access request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => 'Only Admin role can access this admin panel.'
            ], 403);
        } else {
            // Add flash message for unauthorized access
            session()->flash('error', 'Akses Ditolak! Hanya role Admin yang dapat mengakses panel admin.');
            return redirect()->guest(route('backpack.auth.login'));
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $guard = config('backpack.base.guard', 'web'); // gunakan web dulu
        $user = auth($guard)->user();

        Log::info('🟢 CheckIfAdmin middleware triggered', [
            'guard' => $guard,
            'user_id' => $user->id ?? null,
            'email' => $user->email ?? null,
            'role' => $user->role ?? '(null)',
            'is_active' => $user->is_active ?? '(null)',
        ]);

        if (! $user) {
            return $this->respondToUnauthorizedRequest($request);
        }

        if (! $this->checkIfUserIsAdmin($user)) {
            Log::warning('🚫 Access denied by CheckIfAdmin', [
                'user_id' => $user->id,
                'role' => $user->role,
                'active' => $user->is_active,
            ]);

            auth($guard)->logout();
            session()->flash('error', "Akses Ditolak! Hanya admin aktif yang dapat mengakses panel admin.");
            return redirect()->guest(backpack_url('login'));
        }

        return $next($request);
    }
}

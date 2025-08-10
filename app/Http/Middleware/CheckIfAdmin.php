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
        return $user && $user->role === 'Admin' && $user->is_active;
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
            return redirect()->guest(backpack_url('login'));
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
        // Check if user is not authenticated
        if (backpack_auth()->guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        $user = backpack_user();
        
        // Check if authenticated user is not Administrator
        if (! $this->checkIfUserIsAdmin($user)) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized admin access attempt', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null,
                'role' => $user->role ?? null,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            // If user is authenticated but not admin, show specific message
            if ($user) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => 'Access Denied',
                        'message' => "Your role '{$user->role}' is not authorized to access admin panel. Only Administrator role is allowed."
                    ], 403);
                } else {
                    session()->flash('error', "Akses Ditolak! Role Anda '{$user->role}' tidak memiliki akses ke panel admin. Hanya role Administrator yang diizinkan.");
                    
                    // Logout user and redirect to login
                    backpack_auth()->logout();
                    return redirect()->guest(backpack_url('login'));
                }
            }
            
            return $this->respondToUnauthorizedRequest($request);
        }

        return $next($request);
    }
}

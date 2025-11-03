<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeviceSessionController extends Controller
{
    /**
     * Get all active device sessions for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $currentTokenId = (string)$request->user()->currentAccessToken()->id;

            Log::info('Fetching device sessions', [
                'user_id' => $user->id,
                'current_token_id' => $currentTokenId
            ]);

            $sessions = DeviceSession::where('user_id', $user->id)
                ->orderBy('last_active_at', 'desc')
                ->get()
                ->map(function ($session) use ($currentTokenId) {
                    return [
                        'id' => $session->id,
                        'device_name' => $session->device_name,
                        'device_type' => $session->device_type,
                        'os_version' => $session->os_version,
                        'browser' => $session->browser,
                        'ip_address' => $session->ip_address,
                        'location' => $session->location ?? 'Unknown Location',
                        'last_active' => $session->last_active_at?->toIso8601String(),
                        'created_at' => $session->created_at->toIso8601String(),
                        'is_current_device' => $session->token_id === (string)$currentTokenId,
                    ];
                });

            Log::info('Device sessions fetched', [
                'user_id' => $user->id,
                'total_sessions' => $sessions->count(),
                'sessions_data' => $sessions->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device sessions retrieved successfully',
                'data' => [
                    'devices' => $sessions,
                    'total' => $sessions->count(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get device sessions error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch device sessions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Terminate a specific device session
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $currentTokenId = (string)$request->user()->currentAccessToken()->id;

            $session = DeviceSession::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device session not found'
                ], 404);
            }

            if ($session->token_id === (string)$currentTokenId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot terminate current session. Please use logout instead.'
                ], 400);
            }

            $user->tokens()->where('id', $session->token_id)->delete();

            $session->delete();

            Log::info('Device session terminated', [
                'user_id' => $user->id,
                'session_id' => $id,
                'device_name' => $session->device_name,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device session terminated successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Terminate device session error', [
                'error' => $e->getMessage(),
                'session_id' => $id,
                'user_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate device session',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Terminate all other device sessions (logout from all devices except current)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyAll(Request $request)
    {
        try {
            $user = $request->user();
            $currentTokenId = (string)$request->user()->currentAccessToken()->id;

            
            $tokenIds = DeviceSession::where('user_id', $user->id)
                ->where('token_id', '!=', $currentTokenId)
                ->pluck('token_id')
                ->toArray();

            $deletedCount = count($tokenIds);

            if ($deletedCount === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No other device sessions to terminate',
                    'data' => ['terminated_count' => 0]
                ], 200);
            }

            Log::info('Terminating all other sessions', [
                'user_id' => $user->id,
                'token_ids' => $tokenIds,
                'count' => $deletedCount
            ]);

            $user->tokens()->whereIn('id', $tokenIds)->delete();

            DeviceSession::where('user_id', $user->id)
                ->where('token_id', '!=', $currentTokenId)
                ->delete();

            Log::info('All other device sessions terminated', [
                'user_id' => $user->id,
                'terminated_count' => $deletedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully terminated {$deletedCount} device session(s)",
                'data' => [
                    'terminated_count' => $deletedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Terminate all sessions error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate device sessions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
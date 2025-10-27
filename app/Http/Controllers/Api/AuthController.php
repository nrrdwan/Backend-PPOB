<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'full_name' => 'nullable|string|max:255',
                'pin' => 'required|digits:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buat user baru dengan role default 'User Biasa'
            $user = User::create([
                'name' => $request->name,
                'full_name' => $request->full_name ?? $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
                'kyc_status' => 'unverified',
                'pin' => Hash::make($request->pin),
            ]);

            $token = $user->createToken('ppob-mobile-app')->plainTextToken;
            

            Log::info('User registered via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'kyc_status' => $user->kyc_status,
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Login user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
                'device_name' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cek kredensial
            if (!Auth::attempt($request->only('email', 'password'))) {
                Log::warning('Failed login attempt via API', [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }

            $user = Auth::user();

            // Cek apakah user aktif
            if (!$user->is_active) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact admin.'
                ], 403);
            }

            // Update last login
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->update(['last_login_at' => now()]);


            // Buat token dengan device name
            $deviceName = $request->device_name ?? 'mobile-app';
            $token = $user->createToken($deviceName)->plainTextToken;

            // Log successful login
            Log::info('User logged in via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'device' => $deviceName,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'kyc_status' => $user->kyc_status,
                        'is_active' => $user->is_active,
                        'last_login_at' => $user->last_login_at
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout user (revoke current token)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            // Log logout
            Log::info('User logged out via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            // Revoke current access token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout from all devices (revoke all tokens)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request)
    {
        try {
            $user = $request->user();

            // Log logout all
            Log::info('User logged out from all devices via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            // Revoke all access tokens
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout all error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get current authenticated user profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role,
                        'kyc_status' => $user->kyc_status,
                        'is_active' => $user->is_active,
                        'last_login_at' => $user->last_login_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Profile fetch error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Set or update user PIN
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'pin' => 'required|digits:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = $request->user();
            $user->pin = Hash::make($request->pin);
            $user->save();

            Log::info('User PIN set/updated', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil disimpan'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Set PIN error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan PIN. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send OTP to email for password reset
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate 4 digit OTP
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            // Set expiration time to 1 minute from now
            $expiresAt = Carbon::now()->addMinute();

            // Delete any existing OTP for this email
            PasswordOtp::where('email', $request->email)->delete();

            // Create new OTP record
            PasswordOtp::create([
                'email' => $request->email,
                'otp' => $otp,
                'expires_at' => $expiresAt,
            ]);

            // Send OTP via email using professional template
            Mail::send('emails.otp-reset-password', [
                'otp' => $otp,
                'email' => $request->email
            ], function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('🔐 PPOB - Kode OTP Reset Password');
            });

            // Log successful OTP generation
            Log::info('OTP generated for password reset', [
                'email' => $request->email,
                'otp' => $otp, // For debugging purposes - remove in production
                'expires_at' => $expiresAt,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke email Anda',
                'data' => [
                    'email' => $request->email,
                    'expires_in_seconds' => 60,
                    // Remove this line in production for security:
                    'otp_for_testing' => config('app.debug') ? $otp : null
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Forgot password error', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reset password using OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|string|size:4',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find valid OTP record
            $otpRecord = PasswordOtp::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$otpRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP tidak valid atau sudah kadaluarsa'
                ], 422);
            }

            // Find user
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete used OTP
            $otpRecord->delete();

            // Revoke all existing tokens for security
            $user->tokens()->delete();

            // Log successful password reset
            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Reset password error', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal reset password. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Change authenticated user's password.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        try {
            $user = $request->user();

            // Validasi input
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verifikasi password lama
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password lama salah'
                ], 400);
            }

            // Pastikan password baru tidak sama dengan password lama
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password baru tidak boleh sama dengan password lama'
                ], 400);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Revoke semua token (paksa login ulang)
            $user->tokens()->delete();

            Log::info('User password changed via API', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah. Silakan login ulang.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Change password error', [
                'error'   => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'ip'      => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah password. Silakan coba lagi.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function updateFcmToken(Request $request)
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string'
            ]);

            $user = $request->user();
            $user->fcm_token = $request->fcm_token;
            $user->save();

            \Log::info('✅ FCM token updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'fcm_token' => $request->fcm_token,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token berhasil diperbarui'
            ], 200);
        } catch (\Exception $e) {
            \Log::error('❌ Gagal update FCM token', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui token',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function saveFcmToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $request->validate([
                'fcm_token' => 'required|string',
            ]);

            $user->fcm_token = $request->fcm_token;
            $user->save();

            \Log::info('✅ FCM token saved via saveFcmToken()', [
                'user_id' => $user->id,
                'email' => $user->email,
                'fcm_token' => $user->fcm_token,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token berhasil disimpan',
                'data' => ['user_id' => $user->id]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('❌ Gagal save FCM token', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan token',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->name = $validated['name'];
        $user->email     = $validated['email'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => ['user' => $user]
        ], 200);
    }

    public function verifyPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user->pin || !Hash::check($request->pin, $user->pin)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN salah'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'PIN valid'
        ], 200);
    }

    /**
     * Delete authenticated user's account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();

            // Log the deletion attempt
            Log::warning('User account deletion initiated via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            // Delete all associated tokens
            $user->tokens()->delete();

            // Delete the user record
            $user->delete();

            Log::info('User account deleted successfully via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Akun berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Delete account error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus akun. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
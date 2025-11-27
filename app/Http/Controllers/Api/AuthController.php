<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordOtp;
use App\Models\ReferralTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Register a new user with optional referral code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'full_name' => 'nullable|string|max:255',
                'pin' => 'required|digits:6|confirmed',
                'referral_code' => [
                    'nullable',
                    'string',
                    'size:6',
                    function ($attribute, $value, $fail) {
                        if (!empty($value)) {
                            $exists = \App\Models\User::where('referral_code', $value)
                                ->where('is_active', true)
                                ->exists();
                            
                            if (!$exists) {
                                $fail('Kode referral tidak valid atau tidak aktif.');
                            }
                        }
                    },
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Start transaction untuk memastikan atomicity
            DB::beginTransaction();

            try {
                // Create user
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
                    'referred_by' => $request->filled('referral_code') ? $request->referral_code : null,
                ]);

                // Process referral if code provided
                if ($request->filled('referral_code')) {
                    $this->processReferral($user, $request->referral_code);
                }

                $token = $user->createToken('ppob-mobile-app')->plainTextToken;

                DB::commit();

                Log::info('User registered via API', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'referral_code' => $user->referral_code,
                    'referred_by' => $user->referred_by,
                    'has_referrer' => $request->filled('referral_code'),
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
                            'referral_code' => $user->referral_code,
                            'created_at' => $user->created_at
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer'
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Registration error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Process referral commission
     */
    private function processReferral(User $newUser, string $referralCode)
    {
        try {
            // Find referrer
            $referrer = User::where('referral_code', $referralCode)->first();
            
            if (!$referrer) {
                Log::warning('Referral code not found', ['code' => $referralCode]);
                return;
            }

            // Increment referral count
            $referrer->increment('referral_count');
            $currentCount = $referrer->referral_count;

            // Calculate commission
            $commission = ReferralTransaction::calculateCommission($currentCount);

            // Add commission to referrer's balance
            $referrer->increment('balance', $commission);
            $referrer->increment('referral_earnings', $commission);

            // Create referral transaction record
            ReferralTransaction::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $newUser->id,
                'referral_code' => $referralCode,
                'referral_number' => $currentCount,
                'commission_amount' => $commission,
                'status' => 'paid',
            ]);

            Log::info('Referral commission processed', [
                'referrer_id' => $referrer->id,
                'referrer_name' => $referrer->name,
                'referred_id' => $newUser->id,
                'referred_name' => $newUser->name,
                'referral_number' => $currentCount,
                'commission' => $commission,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing referral', [
                'error' => $e->getMessage(),
                'referral_code' => $referralCode,
                'new_user_id' => $newUser->id,
            ]);
        }
    }

    /**
     * Verify referral code
     */
    public function verifyReferralCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'referral_code' => 'required|string|size:6',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid referral code format',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('referral_code', $request->referral_code)
                ->where('is_active', true)
                ->first();

            if ($user) {
                return response()->json([
                    'success' => true,
                    'message' => 'Referral code is valid',
                    'data' => [
                        'referrer_name' => $user->name,
                        'referral_code' => $user->referral_code,
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Referral code not found or inactive',
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Verify referral code error', [
                'error' => $e->getMessage(),
                'code' => $request->referral_code ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify referral code',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get referral statistics for current user
     */
    public function getReferralStats(Request $request)
    {
        try {
            $user = $request->user();

            $referrals = User::where('referred_by', $user->referral_code)
                ->select('id', 'name', 'email', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            $transactions = ReferralTransaction::where('referrer_id', $user->id)
                ->with('referred:id,name,email')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'referred_user' => [
                            'name' => $transaction->referred->name,
                            'email' => $transaction->referred->email,
                        ],
                        'referral_number' => $transaction->referral_number,
                        'commission_amount' => $transaction->commission_amount,
                        'formatted_commission' => $transaction->formatted_commission,
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at,
                    ];
                });

            // Calculate next commission
            $nextReferralNumber = $user->referral_count + 1;
            $nextCommission = ReferralTransaction::calculateCommission($nextReferralNumber);

            return response()->json([
                'success' => true,
                'message' => 'Referral statistics retrieved successfully',
                'data' => [
                    'referral_code' => $user->referral_code,
                    'total_referrals' => $user->referral_count,
                    'total_earnings' => $user->referral_earnings,
                    'formatted_earnings' => $user->formatted_referral_earnings,
                    'next_commission' => $nextCommission,
                    'formatted_next_commission' => 'Rp ' . number_format($nextCommission, 0, ',', '.'),
                    'referrals' => $referrals,
                    'transactions' => $transactions,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get referral stats error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve referral statistics',
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
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
                'device_name' => 'nullable|string|max:255',
                'os_version' => 'nullable|string|max:100',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

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

            if (!$user->is_active) {
                Auth::logout();
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact admin.'
                ], 403);
            }

            /** @var \App\Models\User $user */
            $user->update(['last_login_at' => now()]);

            $deviceName = $request->device_name ?? 'mobile-app';
            $tokenModel = $user->createToken($deviceName);
            $token = $tokenModel->plainTextToken;
            $tokenId = $tokenModel->accessToken->id;

            $userAgent = $request->userAgent() ?? 'Unknown';
            
            try {
                $deviceSession = \App\Models\DeviceSession::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'token_id' => (string)$tokenId,
                    ],
                    [
                        'device_name' => $deviceName,
                        'device_type' => \App\Models\DeviceSession::parseDeviceType($userAgent),
                        'os_version' => $request->os_version ?? 'Unknown',
                        'user_agent' => $userAgent,
                        'browser' => \App\Models\DeviceSession::parseBrowser($userAgent),
                        'ip_address' => $request->ip(),
                        'location' => $this->getLocationFromIP($request->ip()),
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'last_active_at' => now(),
                        'is_current' => true,
                    ]
                );

                Log::info('✅ Device session created/updated', [
                    'user_id' => $user->id,
                    'token_id' => $tokenId,
                    'device_session_id' => $deviceSession->id,
                    'device_name' => $deviceName,
                    'user_agent' => $userAgent,
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to create device session', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $user->id,
                    'token_id' => $tokenId,
                ]);
            }

            Log::info('User logged in via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'device' => $deviceName,
                'token_id' => $tokenId,
                'ip' => $request->ip(),
                'user_agent' => $userAgent
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
                        'last_login_at' => $user->last_login_at,
                        'referral_code' => $user->referral_code,
                        'balance' => $user->balance,
                        'formatted_balance' => $user->formatted_balance,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

    private function getLocationFromIP($ip)
    {
        if ($ip === '127.0.0.1' || 
            str_starts_with($ip, '192.168.') || 
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.16.')) {
            return 'Local Network';
        }

        try {
            $response = \Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,message,country,city,regionName'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'success') {
                    $location = [];
                    if (!empty($data['city'])) $location[] = $data['city'];
                    if (!empty($data['regionName'])) $location[] = $data['regionName'];
                    if (!empty($data['country'])) $location[] = $data['country'];
                    
                    return implode(', ', $location) ?: 'Unknown Location';
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get location from IP', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }
        
        return 'Unknown Location';
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
            $tokenId = $request->user()->currentAccessToken()->id;

            Log::info('User logged out via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'token_id' => $tokenId,
                'ip' => $request->ip()
            ]);

            \App\Models\DeviceSession::where('user_id', $user->id)
                ->where('token_id', (string)$tokenId)
                ->delete();

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

            Log::info('User logged out from all devices via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

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
                        'profile_picture' => $user->profile_picture,
                        'profile_picture_url' => $user->profile_picture,
                        'role' => $user->role,
                        'kyc_status' => $user->kyc_status,
                        'is_active' => $user->is_active,
                        'last_login_at' => $user->last_login_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'referral_code' => $user->referral_code,
                        'referred_by' => $user->referred_by,
                        'referral_count' => $user->referral_count,
                        'referral_earnings' => $user->referral_earnings,
                        'formatted_referral_earnings' => $user->formatted_referral_earnings,
                        'balance' => $user->balance,
                        'formatted_balance' => $user->formatted_balance,
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

    public function changePin(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'current_pin' => 'required|digits:6',
                'new_pin'     => 'required|digits:6|confirmed',
            ]);

            if (empty($user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda belum memiliki PIN. Silakan buat PIN terlebih dahulu.',
                ], 400);
            }
            if (!\Illuminate\Support\Facades\Hash::check($request->current_pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN lama yang Anda masukkan salah.',
                ], 401);
            }

            if (\Illuminate\Support\Facades\Hash::check($request->new_pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN baru tidak boleh sama dengan PIN lama.',
                ], 422);
            }

            $user->pin = \Illuminate\Support\Facades\Hash::make($request->new_pin);
            $user->save();

            \Log::info('PIN berhasil diubah', [
                'user_id' => $user->id,
                'email' => $user->email,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil diubah.',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Input tidak valid.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Gagal mengubah PIN', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah PIN. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function forgotPin(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(5);
            $ttlSeconds = 300;

            \App\Models\PasswordOtp::updateOrCreate(
                ['email' => $request->email],
                [
                    'otp' => $otp,
                    'expires_at' => $expiresAt,
                ]
            );

            $user = \App\Models\User::where('email', $request->email)->first();
            $name = $user?->full_name ?? $user?->name ?? 'Pengguna';

            \Mail::send('emails.otp-reset-pin', [
                'otp' => $otp,
                'email' => $request->email,
                'name' => $name,
                'ttlSeconds' => $ttlSeconds,
                'supportEmail' => config('mail.from.address'),
                'supportUrl' => config('app.url') . '/help-center',
            ], function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('🔐 PPOB - Kode OTP Reset PIN');
            });

            \Log::info('OTP dikirim untuk reset PIN', [
                'email' => $request->email,
                'otp' => $otp,
                'expires_at' => $expiresAt,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke email Anda.',
                'expires_in' => $ttlSeconds,
                'otp_debug' => config('app.debug') ? $otp : null,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Gagal mengirim OTP reset PIN', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function resetPin(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp'   => 'required|string|size:4',
                'pin'   => 'required|digits:6|confirmed',
            ]);

            $otpRecord = \App\Models\PasswordOtp::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('expires_at', '>', now())
                ->first();

            if (!$otpRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.',
                ], 422);
            }

            $user = \App\Models\User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun tidak ditemukan.',
                ], 404);
            }

            $user->pin = \Illuminate\Support\Facades\Hash::make($request->pin);
            $user->save();

            $otpRecord->delete();

            \Log::info('PIN berhasil direset', [
                'email' => $request->email,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil direset. Silakan login menggunakan PIN baru Anda.',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Gagal reset PIN', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? 'unknown',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mereset PIN. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

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

            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $expiresAt = Carbon::now()->addMinute();

            PasswordOtp::where('email', $request->email)->delete();

            PasswordOtp::create([
                'email' => $request->email,
                'otp' => $otp,
                'expires_at' => $expiresAt,
            ]);

            Mail::send('emails.otp-reset-password', [
                'otp' => $otp,
                'email' => $request->email
            ], function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('🔐 PPOB - Kode OTP Reset Password');
            });

            Log::info('OTP generated for password reset', [
                'email' => $request->email,
                'otp' => $otp,
                'expires_at' => $expiresAt,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kode OTP telah dikirim ke email Anda',
                'data' => [
                    'email' => $request->email,
                    'expires_in_seconds' => 60,
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

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            $otpRecord->delete();

            $user->tokens()->delete();

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

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password lama salah'
                ], 400);
            }

            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password baru tidak boleh sama dengan password lama'
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

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
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|nullable|string|max:20',
                'full_name' => 'sometimes|nullable|string|max:255',
                'profile_picture' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            if (isset($validated['phone'])) {
                $user->phone = $validated['phone'];
            }
            if (isset($validated['full_name'])) {
                $user->full_name = $validated['full_name'];
            }

            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture) {
                    $oldPath = str_replace(asset('storage/'), '', $user->profile_picture);
                    \Storage::disk('public')->delete($oldPath);
                }

                $file = $request->file('profile_picture');
                $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('profile_pictures', $filename, 'public');
                $user->profile_picture = asset('storage/' . $path);

                Log::info('Profile picture uploaded', [
                    'user_id' => $user->id,
                    'filename' => $filename,
                    'path' => $path
                ]);
            }

            $user->save();

            Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($validated)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'profile_picture' => $user->profile_picture,
                        'profile_picture_url' => $user->profile_picture,
                        'role' => $user->role,
                        'kyc_status' => $user->kyc_status,
                        'referral_code' => $user->referral_code,
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update profile error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Upload profile picture only
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePicture(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            Log::info('Profile picture upload attempt', [
                'user_id' => $user->id,
                'file_original_name' => $request->file('profile_picture')->getClientOriginalName(),
                'file_size' => $request->file('profile_picture')->getSize()
            ]);

            if ($user->profile_picture) {
                $oldPath = str_replace(asset('storage/'), '', $user->profile_picture);
                if (\Storage::disk('public')->exists($oldPath)) {
                    \Storage::disk('public')->delete($oldPath);
                    Log::info('Old profile picture deleted', [
                        'user_id' => $user->id,
                        'old_path' => $oldPath
                    ]);
                }
            }

            $file = $request->file('profile_picture');
            $filename = 'profile_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_pictures', $filename, 'public');
            $url = asset('storage/' . $path);

            $user->profile_picture = $url;
            $user->save();

            Log::info('Profile picture uploaded successfully', [
                'user_id' => $user->id,
                'filename' => $filename,
                'url' => $url,
                'path' => $path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui',
                'data' => [
                    'profile_picture_url' => $url,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'profile_picture' => $url,
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Profile picture validation failed', [
                'user_id' => $request->user()->id ?? 'unknown',
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File tidak valid. Pastikan format JPG/PNG dan ukuran max 2MB',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Upload profile picture error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload foto profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
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

            Log::warning('User account deletion initiated via API', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip()
            ]);

            $user->tokens()->delete();

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
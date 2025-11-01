<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceSession extends Model
{
    protected $fillable = [
        'user_id',
        'token_id',
        'device_name',
        'device_type',
        'os_version',
        'user_agent',
        'browser',
        'ip_address',
        'location',
        'latitude',
        'longitude',
        'last_active_at',
        'is_current',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'is_current' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse User Agent to get browser info
     */
    public static function parseBrowser(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        if (str_contains($userAgent, 'Opera')) return 'Opera';
        return 'Unknown Browser';
    }

    /**
     * Parse User Agent to get device type
     */
    public static function parseDeviceType(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile')) return 'mobile';
        if (str_contains($userAgent, 'Tablet')) return 'tablet';
        return 'desktop';
    }
}
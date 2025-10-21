<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use CrudTrait;
    protected $fillable = [
        'transaction_id',
        'user_id',
        'from_user_id',
        'to_user_id',
        'product_id',
        'phone_number',
        'customer_id',
        'amount',
        'admin_fee',
        'total_amount',
        'status',
        'type',
        'notes',
        'provider_response',
        'processed_at',
        'metadata'
    ];

    protected $casts = [
        'provider_response' => 'array',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'metadata' => 'array',
        'total_amount' => 'decimal:2'
    ];

    // Boot method untuk auto generate transaction_id
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->transaction_id) {
                $transaction->transaction_id = static::generateTransactionId();
            }
        });
    }

    // Static method untuk generate transaction ID
    public static function generateTransactionId()
    {
        $prefix = 'TRX';
        $date = now()->format('Ymd');
        $random = strtoupper(\Illuminate\Support\Str::random(6));
        
        return $prefix . $date . $random;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    // Scope untuk status
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Status checking methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isSuccess()
    {
        return $this->status === 'success';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    // Status update methods
    public function markAsProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsSuccess($providerResponse = null)
    {
        $this->update([
            'status' => 'success',
            'processed_at' => now(),
            'provider_response' => $providerResponse
        ]);
    }

    public function markAsFailed($providerResponse = null)
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'provider_response' => $providerResponse
        ]);
    }

    // Accessor methods
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 0, ',', '.');
    }
}

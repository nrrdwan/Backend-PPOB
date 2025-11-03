<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCommission extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = [
        'product_id',
        'seller_commission',
        'seller_commission_type',
        'reseller_commission',
        'reseller_commission_type',
        'b2b_commission',
        'b2b_commission_type',
        'is_active',
        'min_commission',
        'max_commission',
    ];

    protected $casts = [
        'seller_commission' => 'decimal:2',
        'reseller_commission' => 'decimal:2',
        'b2b_commission' => 'decimal:2',
        'min_commission' => 'decimal:2',
        'max_commission' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Hitung komisi berdasarkan user type dan amount
     */
    public function calculateCommission(string $userType, float $amount): float
    {
        if (!$this->is_active) {
            return 0;
        }

        $commission = 0;
        $commissionType = '';

        switch (strtolower($userType)) {
            case 'seller':
                $commission = $this->seller_commission;
                $commissionType = $this->seller_commission_type;
                break;
            case 'reseller':
                $commission = $this->reseller_commission;
                $commissionType = $this->reseller_commission_type;
                break;
            case 'b2b':
                $commission = $this->b2b_commission;
                $commissionType = $this->b2b_commission_type;
                break;
            default:
                return 0;
        }

        if ($commissionType === 'percent') {
            $calculatedCommission = ($amount * $commission) / 100;
        } else {
            $calculatedCommission = $commission;
        }

        if ($this->min_commission && $calculatedCommission < $this->min_commission) {
            $calculatedCommission = $this->min_commission;
        }

        if ($this->max_commission && $calculatedCommission > $this->max_commission) {
            $calculatedCommission = $this->max_commission;
        }

        return round($calculatedCommission, 2);
    }

    /**
     * Get commission info untuk user type tertentu
     */
    public function getCommissionInfo(string $userType): array
    {
        switch (strtolower($userType)) {
            case 'seller':
                return [
                    'commission' => $this->seller_commission,
                    'type' => $this->seller_commission_type,
                    'display' => $this->seller_commission_type === 'percent' 
                        ? $this->seller_commission . '%' 
                        : 'Rp ' . number_format($this->seller_commission, 0, ',', '.')
                ];
            case 'reseller':
                return [
                    'commission' => $this->reseller_commission,
                    'type' => $this->reseller_commission_type,
                    'display' => $this->reseller_commission_type === 'percent' 
                        ? $this->reseller_commission . '%' 
                        : 'Rp ' . number_format($this->reseller_commission, 0, ',', '.')
                ];
            case 'b2b':
                return [
                    'commission' => $this->b2b_commission,
                    'type' => $this->b2b_commission_type,
                    'display' => $this->b2b_commission_type === 'percent' 
                        ? $this->b2b_commission . '%' 
                        : 'Rp ' . number_format($this->b2b_commission, 0, ',', '.')
                ];
            default:
                return ['commission' => 0, 'type' => 'percent', 'display' => '0%'];
        }
    }
}


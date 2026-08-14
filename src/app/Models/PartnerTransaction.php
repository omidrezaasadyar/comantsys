<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'status',
        'payment_method',
        'txn_date',
        'purpose',
        'account_holder',
        'account_number',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'txn_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $txn): void {
            if (blank($txn->created_by) && auth()->check()) {
                $txn->created_by = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function types(): array
    {
        return [
            'contribution' => 'آورده',
            'withdrawal'   => 'برداشت',
        ];
    }

    public static function typeColors(): array
    {
        return [
            'contribution' => 'success',
            'withdrawal'   => 'danger',
        ];
    }

    public static function statuses(): array
    {
        return [
            'paid'    => 'پرداخت‌شده',
            'pending' => 'در انتظار',
        ];
    }

    public static function statusColors(): array
    {
        return [
            'paid'    => 'success',
            'pending' => 'warning',
        ];
    }

    public static function paymentMethods(): array
    {
        return [
            'bank' => 'واریز به حساب',
            'cash' => 'نقدی',
        ];
    }

    public static function currencyLabels(): array
    {
        return [
            'IRR' => 'ریال',
            'EUR' => 'یورو',
            'GBP' => 'پوند',
            'USD' => 'دلار',
        ];
    }
}

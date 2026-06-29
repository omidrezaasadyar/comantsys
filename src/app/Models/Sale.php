<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'item_name',
        'supplier_id',
        'customer_name',
        'currency',
        'exchange_rate_ice',
        'exchange_rate_free',
        'sale_date',
        'quantity',
        'purchase_unit_price',
        'sale_unit_price',
        'total_purchase',
        'extra_costs_total',
        'total_cost',
        'revenue',
        'profit',
        'notes',
    ];

    protected $casts = [
        'sale_date'           => 'date',
        'quantity'            => 'decimal:2',
        'purchase_unit_price' => 'decimal:2',
        'sale_unit_price'     => 'decimal:2',
        'total_purchase'      => 'decimal:2',
        'extra_costs_total'   => 'decimal:2',
        'total_cost'          => 'decimal:2',
        'revenue'             => 'decimal:2',
        'profit'              => 'decimal:2',
    ];

    // رابطه‌ها
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(SaleCost::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SaleAttachment::class);
    }

    /**
     * محاسبهٔ مقادیر مالی از روی ورودی‌های دستی.
     * این متد منبعِ یگانهٔ حقیقت برای محاسبات است؛ هم در فرم (live)
     * و هم هنگام ذخیره (saving) فراخوانی می‌شود تا داده‌ها همیشه سازگار بمانند.
     *
     * @param  array  $costs  آرایه‌ای از هزینه‌های جانبی به شکل [['amount' => x], ...]
     * @return array{total_purchase: float, extra_costs_total: float, total_cost: float, revenue: float, profit: float}
     */
    public static function calculateFinancials(
        float $quantity,
        float $purchaseUnitPrice,
        float $saleUnitPrice,
        array $costs = []
    ): array {
        $totalPurchase   = $quantity * $purchaseUnitPrice;
        $revenue         = $quantity * $saleUnitPrice;
        $extraCostsTotal = collect($costs)->sum(fn ($c) => (float) ($c['amount'] ?? 0));
        $totalCost       = $totalPurchase + $extraCostsTotal;
        $profit          = $revenue - $totalCost;

        return [
            'total_purchase'    => $totalPurchase,
            'extra_costs_total' => $extraCostsTotal,
            'total_cost'        => $totalCost,
            'revenue'           => $revenue,
            'profit'            => $profit,
        ];
    }

    /**
     * هنگام ذخیرهٔ رکورد، مقادیر محاسبه‌شده را همیشه از روی ورودی‌های فعلی
     * بازمحاسبه می‌کند. این تضمین می‌کند حتی اگر فرم دور زده شود
     * (مثلاً ویرایش مستقیم)، داده‌های مالی معتبر بمانند.
     */
    protected static function booted(): void
    {
        /**
         * مرحلهٔ ۱ — قبل از ذخیره: مقادیری که به هزینه‌های جانبی وابسته نیستند.
         * این‌ها فقط از روی تعداد و قیمت‌ها محاسبه می‌شوند.
         */
        static::saving(function (Sale $sale) {
            $sale->total_purchase = (float) $sale->quantity * (float) $sale->purchase_unit_price;
            $sale->revenue        = (float) $sale->quantity * (float) $sale->sale_unit_price;
        });

        /**
         * مرحلهٔ ۲ — بعد از ذخیره: حالا که Repeater هزینه‌ها را در جدول
         * sale_costs نوشته، جمعشان را از دیتابیس واقعی می‌خوانیم و
         * هزینهٔ کل و سود را نهایی می‌کنیم. saveQuietly از حلقهٔ بی‌نهایت
         * جلوگیری می‌کند (دوباره saving/saved را صدا نمی‌زند).
         */
        static::saved(function (Sale $sale) {
            $extraCostsTotal = (float) $sale->costs()->sum('amount');
            $totalCost       = (float) $sale->total_purchase + $extraCostsTotal;
            $profit          = (float) $sale->revenue - $totalCost;

            // فقط اگر مقادیر تغییر کرده‌اند، بی‌سروصدا به‌روزرسانی کن
            if (
                (float) $sale->extra_costs_total !== $extraCostsTotal ||
                (float) $sale->total_cost !== $totalCost ||
                (float) $sale->profit !== $profit
            ) {
                $sale->extra_costs_total = $extraCostsTotal;
                $sale->total_cost        = $totalCost;
                $sale->profit            = $profit;
                $sale->saveQuietly();
            }
        });
    }

    // ویژگی موقت برای انتقال هزینه‌ها از فرم به منطق محاسبه (در دیتابیس ذخیره نمی‌شود)
    public ?array $costs_input = null;
}

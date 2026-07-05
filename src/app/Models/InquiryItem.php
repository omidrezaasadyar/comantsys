<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryItem extends Model
{
    protected $fillable = [
        'inquiry_id',
        'description',
        'quantity',
        'unit',
        'unit_other',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    /**
     * واحدهای پیش‌فرض + گزینهٔ «سایر». منبع یگانه برای Select در Repeater.
     *
     * @return array<string, string>
     */
    public static function units(): array
    {
        return [
            'piece'        => __('inquiries.unit.piece'),
            'meter'        => __('inquiries.unit.meter'),
            'kilogram'     => __('inquiries.unit.kilogram'),
            'liter'        => __('inquiries.unit.liter'),
            'square_meter' => __('inquiries.unit.square_meter'),
            'other'        => __('inquiries.unit.other'),
        ];
    }

    /**
     * برچسب نمایشیِ واحد: اگر «سایر» انتخاب شده باشد متنِ آزاد، وگرنه برچسبِ پیش‌فرض.
     */
    public function getUnitLabelAttribute(): ?string
    {
        if ($this->unit === 'other') {
            return $this->unit_other;
        }

        return static::units()[$this->unit] ?? $this->unit;
    }
}

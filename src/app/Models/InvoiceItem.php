<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_code',
        'description',
        'quantity',
        'unit_price',
        'net_sales',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'net_sales'  => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * محاسبهٔ جمع فروش ردیف پیش از ذخیره.
     * net_sales = quantity × unit_price
     */
    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->net_sales = (float) $item->quantity * (float) $item->unit_price;
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{

    protected $fillable = [
        'type',
        'locale',
        'invoice_number',
        'company_id',
        'customer_id',
        'expert_name',
        'inquiry_number',
        'inquiry_date',
        'invoice_date',
        'currency',
        'vat_rate',
        'subtotal',
        'vat_amount',
        'grand_total',
        'template',
        'notes',
    ];

    protected $casts = [
        'inquiry_date' => 'date',
        'invoice_date' => 'date',
        'vat_rate'     => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'grand_total'  => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * منبع یگانهٔ حقیقت محاسبات فاکتور.
     * subtotal از مجموع net_sales اقلام؛ ارزش افزوده فقط برای ریال.
     */
    public function calculateTotals(): void
    {
        $isIRR = $this->currency === 'IRR';
        $rate = (float) $this->vat_rate;

        $subtotal = 0;
        $vatAmount = 0;

        foreach ($this->items as $item) {
            $net = (float) $item->net_sales;
            $subtotal += $net;
            // مالیات هر ردیف جداگانه گرد می‌شود (سازگار با نمایش فاکتور)
            $vatAmount += $isIRR ? round($net * $rate / 100) : 0;
        }

        $this->subtotal    = $subtotal;
        $this->vat_amount  = $vatAmount;
        $this->grand_total = $subtotal + $vatAmount;
    }

    protected static function booted(): void
    {
        // تولید شمارهٔ فاکتور هنگام ایجاد (اتمیک، فقط اگر خالی باشد)
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number) && $invoice->company_id) {
                $company = Company::find($invoice->company_id);
                $date = $invoice->invoice_date ?? now();

                if ($company) {
                    $invoice->invoice_number = $company->generateNumber(
                        $invoice->type,
                        $date instanceof \DateTimeInterface ? $date : new \DateTime($date)
                    );
                }
            }
        });
    }
}
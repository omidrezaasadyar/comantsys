<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        // پیگیری ارسال / تأیید / بازنگری
        'send_status',
        'approval_status',
        'is_revised',
        'sent_at',
        'revised_at',
        'recipient_person',
    ];

    protected $casts = [
        'inquiry_date' => 'date',
        'invoice_date' => 'date',
        'vat_rate'     => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'vat_amount'   => 'decimal:2',
        'grand_total'  => 'decimal:2',
        'sent_at'      => 'date',
        'revised_at'   => 'date',
        'is_revised'   => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
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

    public function currencyLabel(): string
    {
        return match ($this->currency) {
            'EUR' => 'یورو',
            'USD' => 'دلار',
            'GBP' => 'پوند',
            'IRR' => 'ریال',
            default => $this->currency,
        };
    }

    /**
     * برچسب فارسی وضعیت ارسال.
     *
     * ستون در دیتابیس string ساده است (نه enum بومی)، پس این نگاشت تنها منبعِ
     * حقیقت برچسب‌هاست — همان الگوی Inquiry::statuses(). برخلاف Inquiry که
     * برچسب‌هایش از lang/fa/inquiries.php می‌آید، منبع Invoice هنوز i18n نشده و
     * رشته‌هایش مثل currencyLabel() این‌جا فارسی نوشته می‌شوند.
     *
     * @return array<string, string>
     */
    public static function sendStatuses(): array
    {
        return [
            'not_sent' => 'ارسال‌نشده',
            'sent'     => 'ارسال‌شده',
        ];
    }

    /**
     * رنگ نشان (badge) هر وضعیت ارسال.
     *
     * @return array<string, string>
     */
    public static function sendStatusColors(): array
    {
        return [
            'not_sent' => 'gray',
            'sent'     => 'success',
        ];
    }

    /**
     * برچسب فارسی وضعیت تأیید.
     *
     * @return array<string, string>
     */
    public static function approvalStatuses(): array
    {
        return [
            'pending'  => 'در انتظار',
            'approved' => 'تأییدشده',
            'rejected' => 'ردشده',
        ];
    }

    /**
     * رنگ نشان (badge) هر وضعیت تأیید.
     *
     * @return array<string, string>
     */
    public static function approvalStatusColors(): array
    {
        return [
            'pending'  => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];
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
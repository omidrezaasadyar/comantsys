<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquiry extends Model
{
    protected $fillable = [
        'customer_id',
        'inquiry_number',
        'inquiry_date',
        'response_date',
        'status',
        'description',
    ];

    protected $casts = [
        'inquiry_date'  => 'date',
        'response_date' => 'date',
    ];

    // رابطه‌ها
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(InquiryAttachment::class);
    }

    /**
     * منبع یگانهٔ حقیقت برای وضعیت‌ها (مقدار ⇐ برچسب).
     * در فرم، جدول و اینفولیست استفاده می‌شود.
     *
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'received'  => __('inquiries.status.received'),
            'reviewing' => __('inquiries.status.reviewing'),
            'approved'  => __('inquiries.status.approved'),
            'sent'      => __('inquiries.status.sent'),
            'delivered' => __('inquiries.status.delivered'),
            'cancelled' => __('inquiries.status.cancelled'),
        ];
    }
    public function sourcingResults(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\SourcingResult::class);
    }
    /**
     * رنگ نشان (badge) هر وضعیت — برای هماهنگی بصری بین جدول و صفحهٔ نمایش.
     *
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            'received'  => 'gray',
            'reviewing' => 'warning',
            'approved'  => 'info',
            'sent'      => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
        ];
    }
}

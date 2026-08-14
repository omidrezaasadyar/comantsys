<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PortalRequest extends Model
{
    /**
     * ستون‌های قابل پرکردن انبوه.
     * request_number عمداً اینجا نیست — مدل خودش آن را می‌سازد (booted).
     */
    protected $fillable = [
        'user_id',
        'requester_name',
        'company',
        'email',
        'phone',
        'related_person',
        'subject',
        'description',
        'request_date',
        'terms_accepted',
        'validation_status',
        'request_status',
        'admin_response',
    ];

    protected $casts = [
        'request_date'   => 'date',
        'terms_accepted' => 'boolean',
    ];

    // ── رابطه‌ها ──

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PortalRequestAttachment::class);
    }

    // ── نقشهٔ وضعیت‌ها ──

    /**
     * منبع یگانهٔ حقیقت برای وضعیت اعتبارسنجی (مقدار ⇐ برچسب).
     * در فرم، جدول و اینفولیست استفاده می‌شود.
     *
     * @return array<string, string>
     */
    public static function validationStatuses(): array
    {
        return [
            'pending'  => __('portal_requests.validation_status.pending'),
            'verified' => __('portal_requests.validation_status.verified'),
            'rejected' => __('portal_requests.validation_status.rejected'),
        ];
    }

    /**
     * رنگ نشان (badge) هر وضعیت اعتبارسنجی.
     *
     * @return array<string, string>
     */
    public static function validationStatusColors(): array
    {
        return [
            'pending'  => 'warning',
            'verified' => 'success',
            'rejected' => 'danger',
        ];
    }

    /**
     * منبع یگانهٔ حقیقت برای وضعیت رسیدگی (مقدار ⇐ برچسب).
     * فعلاً مجموعهٔ کوچکی است؛ بعداً تکمیل می‌شود.
     *
     * @return array<string, string>
     */
    public static function requestStatuses(): array
    {
        return [
            'received'     => __('portal_requests.request_status.received'),
            'under_review' => __('portal_requests.request_status.under_review'),
            'queued'       => __('portal_requests.request_status.queued'),
        ];
    }

    /**
     * رنگ نشان (badge) هر وضعیت رسیدگی.
     *
     * @return array<string, string>
     */
    public static function requestStatusColors(): array
    {
        return [
            'received'     => 'gray',
            'under_review' => 'warning',
            'queued'       => 'info',
        ];
    }

    /**
     * ساخت شمارهٔ درخواست — الگوی دو-قلابی، مثل Sale.
     *
     * شمارهٔ نهایی به id (کلید خودافزا) وابسته است و id فقط بعد از INSERT
     * وجود دارد؛ اما ستون request_number در دیتابیس NOT NULL و UNIQUE است.
     * پس دو مرحله لازم است.
     */
    protected static function booted(): void
    {
        /**
         * مرحلهٔ ۱ — قبل از درج: یک مقدار موقتِ یکتا می‌گذاریم تا محدودیت
         * NOT NULL برقرار بماند. UUID است تا دو درج هم‌زمان به خطای UNIQUE
         * برخورد نکنند.
         */
        static::creating(function (PortalRequest $request) {
            if (blank($request->request_number)) {
                $request->request_number = 'REQ-TMP-' . Str::uuid();
            }
        });

        /**
         * مرحلهٔ ۲ — بعد از درج: حالا id موجود است، شمارهٔ نهایی را می‌سازیم.
         * سال دو رقمی از request_date گرفته می‌شود (نه now()) و شماره = id + 455.
         * saveQuietly از حلقهٔ بی‌نهایت جلوگیری می‌کند.
         */
        static::created(function (PortalRequest $request) {
            if (blank($request->request_number) || Str::startsWith($request->request_number, 'REQ-TMP-')) {
                $request->request_number =
                    'REQ-EIS-' . $request->request_date->format('y') . '-' . ($request->id + 455);

                $request->saveQuietly();
            }
        });
    }
}

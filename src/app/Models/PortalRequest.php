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

    /**
     * گفت‌وگوی درخواست — از قدیمی به جدید، یعنی ترتیب خواندن یک رشته‌پیام.
     * مرتب‌سازی روی خودِ رابطه است تا هر جای پروژه ترتیب یکسانی بگیرد.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(PortalRequestMessage::class)
            ->orderBy('created_at');
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
     * ترتیب آرایه = ترتیب طبیعیِ چرخهٔ کار، و همین ترتیب در فهرست‌های
     * انتخابی نمایش داده می‌شود؛ پس تصادفی نیست.
     *
     * @return array<string, string>
     */
    public static function requestStatuses(): array
    {
        return [
            'received'       => __('portal_requests.request_status.received'),
            'under_review'   => __('portal_requests.request_status.under_review'),
            'needs_revision' => __('portal_requests.request_status.needs_revision'),
            'queued'         => __('portal_requests.request_status.queued'),
            'rejected'       => __('portal_requests.request_status.rejected'),
            'completed'      => __('portal_requests.request_status.completed'),
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
            'received'       => 'gray',
            'under_review'   => 'warning',
            'needs_revision' => 'warning',
            'queued'         => 'info',
            'rejected'       => 'danger',
            'completed'      => 'success',
        ];
    }

    // ── وضعیت نمایشی (halo) ──

    /**
     * وضعیت نمایشیِ درخواست برای جعبهٔ بالای صفحهٔ مشاهده.
     *
     * دو ستون وضعیت داریم (اعتبارسنجی و رسیدگی) ولی کاربر باید یک پیام واحد
     * ببیند؛ این متد آن دو را با یک زنجیرهٔ اولویت به یک کلید تبدیل می‌کند و
     * تنها منبع حقیقت برای آن جعبه است.
     *
     * ترتیب اولویت (بالاتر برنده است) — عمداً همین ترتیب:
     *   ۱. rejected  — رد شدنِ اعتبارسنجی حرف آخر است؛ حتی اگر رسیدگی جلو رفته
     *                  باشد، کاربر باید «رد شد» را ببیند نه چیز دیگر.
     *   ۲. revision  — درخواستِ بازنگری کاری است که کاربر باید انجام دهد، پس بر
     *                  «تأیید شده» مقدم است.
     *   ۳. verified  — تأیید شده و کاری برای کاربر نمانده.
     *   ۴. pending   — حالت پیش‌فرض.
     */
    public function viewState(): string
    {
        return match (true) {
            $this->validation_status === 'rejected' => 'rejected',
            $this->request_status === 'needs_revision' => 'revision',
            $this->validation_status === 'verified' => 'verified',
            default => 'pending',
        };
    }

    /**
     * عنوان هر وضعیت نمایشی (کلید ⇐ برچسب).
     *
     * @return array<string, string>
     */
    public static function viewStates(): array
    {
        return [
            'rejected' => __('portal_requests.view_state.rejected.title'),
            'revision' => __('portal_requests.view_state.revision.title'),
            'verified' => __('portal_requests.view_state.verified.title'),
            'pending' => __('portal_requests.view_state.pending.title'),
        ];
    }

    /**
     * توضیح یک‌خطی زیر عنوان — همان چیزی که به کاربر می‌گوید حالا چه کند.
     *
     * @return array<string, string>
     */
    public static function viewStateDescriptions(): array
    {
        return [
            'rejected' => __('portal_requests.view_state.rejected.description'),
            'revision' => __('portal_requests.view_state.revision.description'),
            'verified' => __('portal_requests.view_state.verified.description'),
            'pending' => __('portal_requests.view_state.pending.description'),
        ];
    }

    /**
     * رنگ هر وضعیت نمایشی — همان واژگان رنگی بقیهٔ پروژه.
     *
     * @return array<string, string>
     */
    public static function viewStateColors(): array
    {
        return [
            'rejected' => 'danger',
            'revision' => 'warning',
            'verified' => 'success',
            // قرمز، نه خنثی: تا وقتی اعتبارسنجی تأیید نشده، درخواست هنوز
            // «کارِ باز» است و باید در نگاه اول توجه را جلب کند.
            'pending' => 'danger',
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

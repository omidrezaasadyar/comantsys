<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * پیگیری ارسال / تأیید / بازنگری فاکتور — فاز ۱ (فقط ستون‌ها و مدل).
 *
 * وضعیت‌ها مثل بقیهٔ پروژه به‌صورت string ساده ذخیره می‌شوند، نه enum بومیِ
 * دیتابیس: دقیقاً همان الگویی که `inquiries.status` / `direction` / `calendar`
 * دارند (string + نگاشت برچسب و رنگ در مدل). این کار افزودن یک وضعیت جدید را
 * به یک تغییر کد تبدیل می‌کند، نه یک ALTER TYPE.
 *
 * `after(...)` عمداً نیامده: روی PostgreSQL بی‌اثر است و ستون‌ها در هر حال به
 * انتهای جدول اضافه می‌شوند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // وضعیت ارسال — not_sent / sent
            $table->string('send_status')->default('not_sent');

            // وضعیت تأیید مشتری — pending / approved / rejected
            $table->string('approval_status')->default('pending');

            // آیا فاکتور بازنگری شده است؟
            $table->boolean('is_revised')->default(false);

            // تاریخ ارسال و تاریخ بازنگری — میلادی در DB، شمسی در UI
            $table->date('sent_at')->nullable();
            $table->date('revised_at')->nullable();

            // نام شخصی که فاکتور برای او ارسال شده است
            $table->string('recipient_person')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // هیچ کلید خارجی‌ای روی این ستون‌ها نیست، پس dropForeign لازم نمی‌شود.
            $table->dropColumn([
                'send_status',
                'approval_status',
                'is_revised',
                'sent_at',
                'revised_at',
                'recipient_person',
            ]);
        });
    }
};

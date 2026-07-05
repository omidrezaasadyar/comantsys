<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // هویت
            $table->string('name');                          // نام فارسی
            $table->string('name_en')->nullable();           // نام انگلیسی
            $table->string('national_id')->nullable();       // شناسه ملی
            $table->string('economic_code')->nullable();     // کد اقتصادی
            $table->string('registration_no')->nullable();   // شمارهٔ ثبت

            // تماس و آدرس
            $table->text('address')->nullable();             // آدرس فارسی
            $table->text('address_en')->nullable();          // آدرس انگلیسی
            $table->string('postal_code')->nullable();       // کد پستی
            $table->string('phone')->nullable();             // تلفن ثابت
            $table->string('mobile')->nullable();            // موبایل
            $table->string('messenger_phone')->nullable();   // شمارهٔ پیام‌رسان‌ها
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // فایل‌ها
            $table->string('logo_path')->nullable();
            $table->string('stamp_path')->nullable();

            // تنظیمات منطقه‌ای
            $table->string('locale')->default('fa');         // fa | en  (شمسی/میلادی، RTL/LTR)
            $table->string('default_currency')->default('IRR');
            $table->text('footer_note')->nullable();         // پاورقی PDF

            // شماره‌گذاری
            $table->string('prefix')->default('MAG');        // کد شرکت در شماره
            $table->unsignedSmallInteger('seq_padding')->default(4);   // تعداد رقم SEQ
            $table->unsignedInteger('seq_start')->default(1);          // شمارهٔ شروع هر دوره

            $table->unsignedInteger('invoice_counter')->default(0);    // شمارندهٔ جاری فاکتور
            $table->string('invoice_period')->nullable();              // دورهٔ آخرین فاکتور (مثل 405-2)

            $table->unsignedInteger('proforma_counter')->default(0);   // شمارندهٔ جاری پیش‌فاکتور
            $table->string('proforma_period')->nullable();             // دورهٔ آخرین پیش‌فاکتور

            $table->string('counter_reset')->default('monthly');       // monthly | yearly | never

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

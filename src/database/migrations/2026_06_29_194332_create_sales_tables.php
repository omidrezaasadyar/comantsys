<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->string('item_name');                                  // نام قلم فروش‌رفته
            $table->foreignId('supplier_id')                              // تأمین‌کنندهٔ مرتبط (اختیاری)
                  ->nullable()
                  ->constrained('suppliers')
                  ->nullOnDelete();
            $table->string('customer_name')->nullable();                  // نام مشتری (متن دستی)
            $table->string('currency', 3)->default('IRR');                // IRR / EUR / GBP / USD
            $table->date('sale_date')->nullable();                        // تاریخ فروش

            // ورودی‌های دستی
            $table->decimal('quantity', 15, 2)->default(0);               // تعداد
            $table->decimal('purchase_unit_price', 15, 2)->default(0);    // قیمت خرید واحد
            $table->decimal('sale_unit_price', 15, 2)->default(0);        // قیمت فروش واحد

            // مقادیر محاسبه‌شده (ذخیره می‌شوند برای جمع/فیلتر/مرتب‌سازی)
            $table->decimal('total_purchase', 15, 2)->default(0);         // خرید کل = تعداد × خرید واحد
            $table->decimal('extra_costs_total', 15, 2)->default(0);      // جمع هزینه‌های جانبی
            $table->decimal('total_cost', 15, 2)->default(0);             // هزینهٔ کل = خرید کل + جانبی
            $table->decimal('revenue', 15, 2)->default(0);                // درآمد = تعداد × فروش واحد
            $table->decimal('profit', 15, 2)->default(0);                 // سود = درآمد − هزینهٔ کل

            $table->text('notes')->nullable();                            // یادداشت
            $table->timestamps();
        });

        Schema::create('sale_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                  ->constrained('sales')
                  ->cascadeOnDelete();
            $table->string('title');                                      // عنوان هزینه (حمل، گمرک، ...)
            $table->decimal('amount', 15, 2)->default(0);                 // مبلغ
            $table->timestamps();
        });

        Schema::create('sale_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                  ->constrained('sales')
                  ->cascadeOnDelete();
            $table->string('file');                                       // مسیر فایل آپلودشده
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_attachments');
        Schema::dropIfExists('sale_costs');
        Schema::dropIfExists('sales');
    }
};

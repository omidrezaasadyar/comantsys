<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('type')->default('proforma'); // invoice | proforma
            $table->string('invoice_number')->nullable(); // هنگام ذخیره تولید می‌شود

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->restrictOnDelete(); // نگذار شرکتی که فاکتور دارد حذف شود

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            $table->string('expert_name')->nullable();    // نام کارشناس
            $table->string('inquiry_number')->nullable(); // شماره استعلام
            $table->date('inquiry_date')->nullable();      // تاریخ استعلام
            $table->date('invoice_date');                  // تاریخ فاکتور

            $table->string('currency')->default('IRR');    // IRR | EUR | USD | GBP
            $table->decimal('vat_rate', 5, 2)->default(10); // نرخ ارزش افزوده ٪

            $table->decimal('subtotal', 20, 2)->default(0);    // جمع فروش
            $table->decimal('vat_amount', 20, 2)->default(0);  // جمع مالیات و عوارض
            $table->decimal('grand_total', 20, 2)->default(0); // مبلغ کل

            $table->string('template')->default('magan_fa'); // قالب PDF
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes(); // حذف نرم برای سند مالی
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();

            // مشتری — الزامی؛ استعلام نباید بی‌مشتری بماند، پس حذف مشتری محدود می‌شود
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->restrictOnDelete();

            $table->string('inquiry_number')->unique();   // شمارهٔ مرجعِ خودِ خریدار (ورود دستی)
            $table->date('inquiry_date');                 // تاریخ استعلام
            $table->date('response_date')->nullable();    // تاریخ پاسخ
            $table->string('status')->default('received'); // received / reviewing / approved / sent / delivered / cancelled
            $table->text('description')->nullable();       // توضیحات

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};

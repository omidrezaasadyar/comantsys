<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete(); // با حذف فاکتور، اقلامش هم حذف شوند

            $table->string('item_code')->nullable();  // کد کالا
            $table->string('description');            // شرح کالا و خدمات

            $table->decimal('quantity', 15, 2)->default(1);     // تعداد
            $table->decimal('unit_price', 20, 2)->default(0);   // قیمت واحد
            $table->decimal('net_sales', 20, 2)->default(0);    // جمع فروش ردیف = quantity × unit_price

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};

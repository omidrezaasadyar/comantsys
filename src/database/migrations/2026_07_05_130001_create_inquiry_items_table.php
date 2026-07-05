<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')
                  ->constrained('inquiries')
                  ->cascadeOnDelete();

            $table->string('description');                 // شرح قلم
            $table->decimal('quantity', 15, 2);            // تعداد / مقدار
            $table->string('unit');                        // واحد (از فهرست پیش‌فرض یا «سایر»)
            $table->string('unit_other')->nullable();      // متن آزادِ واحد — فقط وقتی unit = 'other'
            $table->text('notes')->nullable();             // یادداشت

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_items');
    }
};

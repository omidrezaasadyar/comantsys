<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')
                  ->constrained('inquiries')
                  ->cascadeOnDelete();

            $table->string('title')->nullable();   // عنوان اختیاری
            $table->string('file_path');           // مسیر روی دیسک خصوصی (local)
            $table->string('file_type')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_attachments');
    }
};

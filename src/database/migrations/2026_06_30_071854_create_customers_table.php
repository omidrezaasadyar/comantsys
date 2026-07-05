<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('person_type')->default('legal'); // real | legal
            $table->string('name');
            $table->string('national_id')->nullable();   // شناسه ملی / کد ملی
            $table->string('economic_code')->nullable(); // کد اقتصادی
            $table->string('postal_code')->nullable();   // کد پستی
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

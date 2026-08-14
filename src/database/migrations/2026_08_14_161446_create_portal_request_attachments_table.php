<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('portal_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_request_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->string('file_type')->nullable();
            // Who attached the file. Plain string, not a DB enum.
            $table->string('source')->default('customer');  // customer|admin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_request_attachments');
    }
};

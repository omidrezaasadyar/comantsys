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
        Schema::create('portal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            // System-generated reference shown to the requester.
            $table->string('request_number')->unique();

            // Requester details as submitted.
            $table->string('requester_name');
            $table->string('company');
            $table->string('email');
            $table->string('phone');
            $table->string('related_person')->nullable();

            // The request itself.
            $table->string('subject');
            $table->text('description');
            $table->date('request_date');
            $table->boolean('terms_accepted')->default(false);

            // Both status columns are plain strings, NOT DB enums, so the
            // vocabularies stay changeable in app code without a migration.
            $table->string('validation_status')->default('pending');  // pending|verified|rejected
            $table->string('request_status')->default('received');    // received|under_review|queued|…

            $table->text('admin_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_requests');
    }
};

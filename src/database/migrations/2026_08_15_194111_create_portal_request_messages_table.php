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
        Schema::create('portal_request_messages', function (Blueprint $table) {
            $table->id();
            // Messages are children of the request, same as attachments:
            // deleting the request takes its whole thread with it.
            $table->foreignId('portal_request_id')->constrained()->cascadeOnDelete();
            // Which side wrote it. Plain string, NOT a DB enum, so the
            // vocabulary stays changeable in app code without a migration —
            // same choice as portal_request_attachments.source.
            $table->string('sender');  // admin|customer
            $table->text('body');
            // created_at is what orders the thread.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_request_messages');
    }
};

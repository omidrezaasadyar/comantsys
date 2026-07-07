<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A sourcing request: the part to source (standalone — no inquiry coupling).
        Schema::create('sourcing_requests', function (Blueprint $table) {
            $table->id();

            $table->string('part_name');
            $table->string('part_number')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index('status');
        });

        // One agent execution against a request (may be retried → many runs per request).
        Schema::create('sourcing_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_request_id')
                  ->constrained('sourcing_requests')
                  ->cascadeOnDelete();

            $table->string('status')->default('pending');   // pending|running|completed|failed
            $table->text('query')->nullable();

            $table->string('llm_provider')->nullable();
            $table->string('llm_model')->nullable();
            $table->string('search_provider')->nullable();

            $table->jsonb('results')->nullable();       // structured findings for UI
            $table->jsonb('raw_search')->nullable();    // raw search data for quality evaluation

            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);

            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['sourcing_request_id', 'status']);
            $table->index('created_at');
        });

        // Attachments for a request — same conventions as inquiry_attachments
        // (module-specific table, private `local` disk, secure download route).
        Schema::create('sourcing_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_request_id')
                  ->constrained('sourcing_requests')
                  ->cascadeOnDelete();

            $table->string('title')->nullable();   // عنوان اختیاری
            $table->string('file_path');           // مسیر روی دیسک خصوصی (local)
            $table->string('file_type')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop children before the parent they FK into.
        Schema::dropIfExists('sourcing_request_attachments');
        Schema::dropIfExists('sourcing_runs');
        Schema::dropIfExists('sourcing_requests');
    }
};

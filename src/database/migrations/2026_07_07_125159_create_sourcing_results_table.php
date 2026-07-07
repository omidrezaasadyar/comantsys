<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sourcing_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('status')->default('pending');   // pending|running|completed|failed
            $table->text('query');

            $table->string('llm_provider');
            $table->string('llm_model');
            $table->string('search_provider');

            $table->jsonb('results')->nullable();       // structured findings for UI
            $table->jsonb('raw_search')->nullable();    // raw search data for quality evaluation

            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);

            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['inquiry_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::drop('sourcing_results');
    }
};

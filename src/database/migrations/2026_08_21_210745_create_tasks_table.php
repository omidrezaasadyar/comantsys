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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Assignee — the task dies with the user.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Manager who created it — kept as an orphan record if they leave.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->date('due_date');
            $table->boolean('is_done')->default(false);
            $table->string('completion_note')->nullable();
            $table->timestamp('done_at')->nullable();

            $table->timestamps();

            // PostgreSQL does NOT auto-index the referencing side of an FK,
            // unlike MySQL — these are explicit on purpose.
            $table->index('user_id');
            $table->index('created_by');
            $table->index(['user_id', 'due_date']); // per-user daily list ordering
            $table->index('is_done');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

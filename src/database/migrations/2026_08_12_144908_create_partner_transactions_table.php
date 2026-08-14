<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('type');                       // contribution | withdrawal
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3);
            $table->string('status')->default('paid');    // pending | paid
            $table->date('txn_date');
            $table->text('purpose')->nullable();
            $table->string('account_holder')->nullable();
            $table->string('account_number')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'currency', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_transactions');
    }
};

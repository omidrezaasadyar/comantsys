<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // شرکتِ صادرکنندهٔ استعلام — تقویم فرم (شمسی/میلادی) از روی locale همین شرکت تعیین می‌شود
            $table->foreignId('company_id')
                  ->nullable()
                  ->after('customer_id')
                  ->constrained('companies')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};

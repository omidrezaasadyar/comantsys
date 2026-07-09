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
        Schema::table('sourcing_runs', function (Blueprint $table) {
            // Per-attachment OCR text (filename => text) the query builder saw.
            $table->jsonb('ocr_context')->nullable()->after('raw_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sourcing_runs', function (Blueprint $table) {
            $table->dropColumn('ocr_context');
        });
    }
};

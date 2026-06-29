<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // نرخ تبدیل ارز خارجی به ریال در زمان معامله (فقط ثبت، بدون محاسبه)
            $table->decimal('exchange_rate_ice', 20, 2)->nullable()->after('currency');   // نرخ ICE / رسمی
            $table->decimal('exchange_rate_free', 20, 2)->nullable()->after('exchange_rate_ice'); // نرخ آزاد
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate_ice', 'exchange_rate_free']);
        });
    }
};

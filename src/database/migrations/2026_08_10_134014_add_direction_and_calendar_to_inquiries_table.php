<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // جهت استعلام: دریافتی (inbound) یا ارسالی (outbound)
            $table->string('direction')->default('inbound')->after('company_id');

            // تقویمِ فرم — انتخاب دستی: شمسی (jalali) یا میلادی (gregorian)
            $table->string('calendar')->default('jalali')->after('direction');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['direction', 'calendar']);
        });
    }
};

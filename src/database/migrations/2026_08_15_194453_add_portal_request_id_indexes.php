<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Both child tables declare portal_request_id with
     * foreignId()->constrained()->cascadeOnDelete(), which creates the column
     * and the FK but NOT an index. MySQL/InnoDB auto-indexes the referencing
     * side of a foreign key; PostgreSQL does not — so on this stack every
     * "load a request's thread/attachments" lookup, and every cascade during a
     * parent DELETE, was a sequential scan. These two indexes close that gap.
     */
    public function up(): void
    {
        Schema::table('portal_request_messages', function (Blueprint $table) {
            $table->index('portal_request_id');
        });

        Schema::table('portal_request_attachments', function (Blueprint $table) {
            $table->index('portal_request_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * The array form of dropIndex() makes Laravel rebuild the conventional
     * index name ({table}_{column}_index) rather than treating the argument as
     * a literal name — which is what up() created, since ->index() was called
     * without an explicit name.
     */
    public function down(): void
    {
        Schema::table('portal_request_messages', function (Blueprint $table) {
            $table->dropIndex(['portal_request_id']);
        });

        Schema::table('portal_request_attachments', function (Blueprint $table) {
            $table->dropIndex(['portal_request_id']);
        });
    }
};

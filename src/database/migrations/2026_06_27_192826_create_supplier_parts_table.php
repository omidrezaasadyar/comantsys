<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("supplier_parts", function (Blueprint $table) {
            $table->id();
            $table->foreignId("supplier_id")->constrained()->cascadeOnDelete();
            $table->string("part_name");
            $table->string("part_number")->nullable();
            $table->decimal("price", 15, 2)->nullable();
            $table->string("currency")->default("IRR");
            $table->text("notes")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("supplier_parts");
    }
};

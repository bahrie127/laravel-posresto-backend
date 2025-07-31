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
        Schema::table('draft_orders', function (Blueprint $table) {
            $table->integer('tax_percent')->default(0)->after('tax');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_orders', function (Blueprint $table) {
            $table->dropColumn('tax_percent');
        });
    }
};

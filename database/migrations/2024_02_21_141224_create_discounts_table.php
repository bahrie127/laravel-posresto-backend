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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            //description
            $table->text('description')->nullable();
            //type
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            //value
            $table->decimal('value', 15, 2);
            //status
            $table->enum('status', ['active', 'inactive'])->default('active');
            //expired date
            $table->date('expired_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};

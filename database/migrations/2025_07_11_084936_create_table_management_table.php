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
        Schema::create('table_management', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->dateTime('start_time')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->float('x_position');
            $table->float('y_position');
            $table->enum('status', ['available', 'occupied', 'reserved', 'unavailable'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_management');
    }
};

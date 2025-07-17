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
        Schema::create('draft_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable(); // id dari order jika sudah dibuat
            $table->integer('total_item');
            $table->integer('subtotal');
            $table->integer('tax')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('service_charge')->default(0);
            $table->integer('total');
            $table->timestamp('transaction_time');
            $table->integer('table_number');
            $table->string('draft_name');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_orders');
    }
};

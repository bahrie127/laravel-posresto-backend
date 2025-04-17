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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('payment_amount');
            $table->integer('sub_total');
            $table->integer('tax');
            $table->integer('discount');
            $table->integer('service_charge');
            $table->integer('total');
            $table->string('payment_method');
            $table->integer('total_item');
            $table->integer('id_kasir');
            $table->string('nama_kasir');
            $table->string('transaction_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

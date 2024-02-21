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
        // id INTEGER PRIMARY KEY AUTOINCREMENT,
        // id_order INTEGER,
        // id_product INTEGER,
        // quantity INTEGER,
        // price INTEGER
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            //order id from orders table
            $table->foreignId('order_id')->constrained('orders');
            //product id from products table
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity');
            $table->integer('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};

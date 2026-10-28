<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tamu menginap untuk opsi "tagih ke kamar" di kasir. Di produksi klien data ini datang
     * dari sistem hotel; tabel ini menyediakannya untuk instalasi tanpa sistem hotel & demo.
     */
    public function up(): void
    {
        Schema::create('hotel_guests', function (Blueprint $table) {
            $table->id();
            $table->string('billing_number')->unique();
            $table->string('room_number');
            $table->string('first_name');
            $table->string('last_name')->default('');
            $table->date('check_in');
            $table->date('check_out');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_guests');
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Dulu: 20 user faker, 4 kategori & 5 produk acak (tab Makanan/Minuman/Snack di app
     * memakai id 1-3 sehingga isinya acak), dan diskon yang sudah kedaluwarsa.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // URUTAN PENTING: transaksi contoh membutuhkan produk yang sudah ada.
        $this->call([
            KategoriProdukSeeder::class,
            TransaksiContohSeeder::class,
            RiwayatPenjualanSeeder::class,
        ]);
    }
}
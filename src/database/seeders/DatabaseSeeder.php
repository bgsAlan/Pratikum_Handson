
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Kategori dan produk harus dibuat sebelum transaksi.
        $this->call([
            KategoriProdukSeeder::class,
            TransaksiContohSeeder::class,
        ]);
    }
}
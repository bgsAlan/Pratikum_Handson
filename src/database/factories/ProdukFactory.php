```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ProdukFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Bila kategori_id tidak diberikan pemanggil, factory membuat
            // kategori baru sendiri sehingga kunci asing tidak pernah kosong.
            'kategori_id' => Kategori::factory(),
            'sku' => 'SKU-' . $this->faker->unique()->numberBetween(100, 999),
            'nama' => ucwords($this->faker->words(3, true)),

            // Harga dibulatkan ke ratusan agar realistis sebagai harga rak
            // dan agar pengujian pembulatan struk tetap masuk akal.
            'harga' => $this->faker->numberBetween(20, 5_000) * 100,
            'stok' => $this->faker->numberBetween(5, 300),
            'aktif' => true,
        ];
    }

    /* ---------------- State: variasi kasus batas ---------------- */

    /** Produk yang stoknya habis, untuk menguji AB-8. */
    public function habis(): static
    {
        return $this->state(fn () => ['stok' => 0]);
    }

    /** Produk murah berstok besar, untuk menguji diskon grosir AB-2. */
    public function grosir(): static
    {
        return $this->state(fn () => [
            'harga' => $this->faker->numberBetween(20, 60) * 100,
            'stok' => $this->faker->numberBetween(300, 900),
        ]);
    }

    /** Produk yang ditarik dari rak, untuk menguji filter katalog. */
    public function nonaktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
```

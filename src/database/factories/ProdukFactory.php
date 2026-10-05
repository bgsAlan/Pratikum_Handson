
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
            'kategori_id' => Kategori::factory(),
            'sku' => 'SKU-' . $this->faker->unique()->numberBetween(100, 999),
            'nama' => ucwords($this->faker->words(3, true)),
            'harga' => $this->faker->numberBetween(20, 5000) * 100,
            'stok' => $this->faker->numberBetween(5, 300),
            'aktif' => true,
        ];
    }

    public function habis(): static
    {
        return $this->state(fn () => [
            'stok' => 0,
        ]);
    }

    public function grosir(): static
    {
        return $this->state(fn () => [
            'harga' => $this->faker->numberBetween(20, 60) * 100,
            'stok' => $this->faker->numberBetween(300, 900),
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => [
            'aktif' => false,
        ]);
    }
}docker compose exec app php artisan
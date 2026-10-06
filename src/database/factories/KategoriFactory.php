<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class KategoriFactory extends Factory
{
    public function definition(): array
    {
        $nama = $this->faker->unique()->words(2, true);

        return [
            'kode' => Str::slug($nama, '_'),
            'nama' => ucwords($nama),
            'aktif' => true,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
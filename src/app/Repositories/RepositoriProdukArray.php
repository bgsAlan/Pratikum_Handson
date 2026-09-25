<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoriProduk;

/**
 * Sumber data sementara untuk Modul 3.
 * Diganti implementasi Eloquent pada Modul 4.
 */
final class RepositoriProdukArray implements RepositoriProduk
{
    private const KATALOG = [
        'SKU-001' => ['nama' => 'Beras Pandan Wangi 5 kg', 'kategori' => 'kebutuhan_rumah', 'harga' => 72000, 'stok' => 40],
        'SKU-002' => ['nama' => 'Minyak Goreng 1 L', 'kategori' => 'kebutuhan_rumah', 'harga' => 18500, 'stok' => 120],
        'SKU-003' => ['nama' => 'Gula Pasir 1 kg', 'kategori' => 'kebutuhan_rumah', 'harga' => 15500, 'stok' => 85],
        'SKU-004' => ['nama' => 'Kopi Bubuk 200 g', 'kategori' => 'minuman', 'harga' => 24000, 'stok' => 60],
        'SKU-005' => ['nama' => 'Teh Celup 25 sachet', 'kategori' => 'minuman', 'harga' => 9500, 'stok' => 0],
        'SKU-006' => ['nama' => 'Mie Instan Goreng', 'kategori' => 'makanan', 'harga' => 3400, 'stok' => 480],
        'SKU-007' => ['nama' => 'Susu UHT 1 L', 'kategori' => 'minuman', 'harga' => 19000, 'stok' => 36],
        'SKU-008' => ['nama' => 'Sabun Mandi Batang', 'kategori' => 'kebutuhan_rumah', 'harga' => 5200, 'stok' => 150],
        'SKU-009' => ['nama' => 'Buku Tulis 38 lembar', 'kategori' => 'alat_tulis', 'harga' => 4800, 'stok' => 200],
        'SKU-010' => ['nama' => 'Pena Gel Hitam', 'kategori' => 'alat_tulis', 'harga' => 3900, 'stok' => 240],
    ];

    public function semua(): array
    {
        return array_map(
            static fn (string $sku): array => self::baris($sku),
            array_keys(self::KATALOG),
        );
    }

    public function cariSku(string $sku): ?array
    {
        $sku = strtoupper(trim($sku));

        return isset(self::KATALOG[$sku]) ? self::baris($sku) : null;
    }

    /** @return array<string, mixed> */
    private static function baris(string $sku): array
    {
        return ['sku' => $sku, ...self::KATALOG[$sku]];
    }
}
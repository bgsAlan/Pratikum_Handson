<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Produk;

/**
 * Kontrak sumber data produk.
 *
 * Service bergantung pada antarmuka ini, bukan pada
 * implementasi repository tertentu.
 */
interface RepositoriProduk
{
    /** @return array<int, array<string, mixed>> */
    public function semua(): array;

    /** @return array<string, mixed>|null */
    public function cariSku(string $sku): ?array;

    /** Mengambil produk beserta relasi pemasoknya. */
    public function cariPemasok(string $sku): ?Produk;

    public function kunciStok(string $sku): int;

    /**
     * Nilai $selisih negatif mengurangi stok,
     * positif menambah stok.
     */
    public function ubahStok(string $sku, int $selisih): void;

    /**
     * @param array<int, string> $sku
     * @return array<string, array<string, mixed>>
     */
    public function cariBanyakSku(array $sku): array;

    /**
     * Mengunci stok beberapa produk sekaligus.
     *
     * @param array<int, string> $sku
     * @return array<string, int>
     */
    public function kunciBanyakStok(array $sku): array;

    /**
     * Mengubah stok beberapa produk sekaligus.
     *
     * @param array<string, int> $perubahan
     */
    public function ubahBanyakStok(array $perubahan): void;
}
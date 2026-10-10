<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Kontrak sumber data produk.
 *
 * Service hanya bergantung pada antarmuka ini, tidak pada
 * implementasinya. Pada Modul 4 kita cukup membuat class baru
 * RepositoriProdukEloquent lalu menukar satu baris binding di
 * AppServiceProvider. Controller dan service tidak berubah sama sekali.
 */
interface RepositoriProduk
{
    /** @return array<int, array<string, mixed>> */
    public function semua(): array;

    /** @return array<string, mixed>|null */
    public function cariSku(string $sku): ?array;

    /** @return array<string, mixed>|null */
    public function cariPemasok(string $sku): ?array;

    public function kunciStok(string $sku): int;
    /** Nilai $selisih negatif mengurangi stok, positif mengembalikannya. */
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
     * Mengurangi atau menambah stok beberapa produk sekaligus.
     *
     * @param array<string, int> $perubahan
     */
    public function ubahBanyakStok(array $perubahan): void;
}

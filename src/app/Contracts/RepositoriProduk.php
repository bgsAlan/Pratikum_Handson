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
}
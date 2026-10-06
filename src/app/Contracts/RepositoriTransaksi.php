<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Kontrak sumber data transaksi.
 * Bentuknya tidak berubah sejak Modul 3;
 * hanya implementasinya yang berganti dari berkas JSON ke Eloquent.
 */
interface RepositoriTransaksi
{
    /** @return array<int, array<string, mixed>> */
    public function tanggal(string $tanggal): array;

    /** @return array<string, mixed>|null */
    public function cariNomor(string $nomor): ?array;

    /** @param array<string, mixed> $transaksi */
    public function simpan(array $transaksi): void;

    /** @param array<string, mixed> $perubahan */
    public function perbarui(string $nomor, array $perubahan): void;

    /** Nomor urut berikutnya untuk tanggal tertentu. */
    public function urutanBerikutnya(string $tanggal): int;
}

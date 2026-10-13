<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Collection;

/**
 * Kontrak sumber data transaksi.
 */
interface RepositoriTransaksi
{
    public function tanggal(string $tanggal): Collection;

    public function cariNomor(string $nomor): ?Transaksi;

    /** @param array<string, mixed> $transaksi */
    public function simpan(array $transaksi): void;

    /** @param array<string, mixed> $perubahan */
    public function perbarui(string $nomor, array $perubahan): void;

    /** Nomor urut berikutnya untuk tanggal tertentu. */
    public function urutanBerikutnya(string $tanggal): int;
}
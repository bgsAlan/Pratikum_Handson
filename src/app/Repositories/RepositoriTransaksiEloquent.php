<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoriTransaksi;
use App\Models\ItemTransaksi;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Collection;

final class RepositoriTransaksiEloquent implements RepositoriTransaksi
{
    public function tanggal(string $tanggal): Collection
    {
        return Transaksi::query()
            ->with('item')
            ->tanggal($tanggal)
            ->orderBy('nomor')
            ->get();
    }

    public function cariNomor(string $nomor): ?Transaksi
    {
        return Transaksi::query()
            ->with('item')
            ->where('nomor', $nomor)
            ->first();
    }

    public function simpan(array $transaksi): void
    {
        $item = $transaksi['item'];
        unset($transaksi['item']); // 'item' bukan kolom tabel transaksi

        $baris = Transaksi::create($transaksi);

        ItemTransaksi::insert(array_map(
            static fn (array $b): array => [
                'transaksi_id' => $baris->id,
                'produk_id' => $b['_produk_id'],
                'sku' => $b['sku'], // snapshot AB-12
                'nama_produk' => $b['nama'], // snapshot AB-12
                'harga_satuan' => $b['harga_satuan'], // snapshot AB-12
                'kuantitas' => $b['kuantitas'],
                'diskon' => $b['diskon'],
                'total' => $b['total'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $item,
        ));
    }

    public function perbarui(string $nomor, array $perubahan): void
    {
        Transaksi::query()
            ->where('nomor', $nomor)
            ->update($perubahan);
    }

    public function urutanBerikutnya(string $tanggal): int
    {
        return Transaksi::query()
            ->where(
                'nomor',
                'like',
                'POS-' . str_replace('-', '', $tanggal) . '-%'
            )
            ->count() + 1;
    }
}
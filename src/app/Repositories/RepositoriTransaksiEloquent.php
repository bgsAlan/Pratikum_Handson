<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoriTransaksi;
use App\Models\ItemTransaksi;
use App\Models\Produk;
use App\Models\Transaksi;

final class RepositoriTransaksiEloquent implements RepositoriTransaksi
{
    public function tanggal(string $tanggal): array
    {
        return Transaksi::query()
            ->with('item')
            ->tanggal($tanggal)
            ->orderBy('nomor')
            ->get()
            ->map($this->keArray(...))
            ->all();
    }

    public function cariNomor(string $nomor): ?array
    {
        $transaksi = Transaksi::query()
            ->with('item')
            ->where('nomor', $nomor)
            ->first();

        return $transaksi === null ? null : $this->keArray($transaksi);
    }

    public function simpan(array $transaksi): void
    {
        $item = $transaksi['item'];
        unset($transaksi['item']);   // 'item' bukan kolom tabel transaksi

        $baris = Transaksi::create($transaksi);

        ItemTransaksi::insert(array_map(
            static fn (array $b): array => [
                'transaksi_id' => $baris->id,
                'produk_id' => $b['_produk_id'],
                'sku'          => $b['sku'],           // snapshot AB-12
                'nama_produk'  => $b['nama'],          // snapshot AB-12
                'harga_satuan' => $b['harga_satuan'],  // snapshot AB-12
                'kuantitas'    => $b['kuantitas'],
                'diskon'       => $b['diskon'],
                'total'        => $b['total'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            $item,
        ));
    }

    public function perbarui(string $nomor, array $perubahan): void
    {
        Transaksi::query()->where('nomor', $nomor)->update($perubahan);
    }

    public function urutanBerikutnya(string $tanggal): int
    {
        return Transaksi::query()
            ->where('nomor', 'like', 'POS-' . str_replace('-', '', $tanggal) . '-%')
            ->count() + 1;
    }

    /** @return array<string, mixed> */
    private function keArray(Transaksi $transaksi): array
    {
        $item = $transaksi->item
            ->map(static fn (ItemTransaksi $i): array => [
                'sku' => $i->sku,
                'nama' => $i->nama_produk,
                'harga_satuan' => $i->harga_satuan,
                'kuantitas' => $i->kuantitas,
                'diskon' => $i->diskon,
                'total' => $i->total,
                ]
            )
            ->all();

        return [
            'nomor'         => $transaksi->nomor,
            'waktu'         => $transaksi->created_at->toIso8601String(),
            'kasir'         => $transaksi->kasir,
            'member'        => $transaksi->member,
            'metode_bayar'  => $transaksi->metode_bayar->value,
            'metode_label'  => $transaksi->metode_bayar->label(),
            'status'        => $transaksi->status->value,
            'item'          => $item,
            'subtotal'      => $transaksi->subtotal,
            'diskon_grosir' => $transaksi->diskon_grosir,
            'diskon_member' => $transaksi->diskon_member,
            'total_diskon'  => $transaksi->total_diskon,
            'dpp'           => $transaksi->dpp,
            'ppn'           => $transaksi->ppn,
            'total'         => $transaksi->total,
            'pembulatan'    => $transaksi->pembulatan,
            'total_bayar'   => $transaksi->total_bayar,
            'dibayar'       => $transaksi->dibayar,
            'kembalian'     => $transaksi->kembalian,
        ];
    }
}

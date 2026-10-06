<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Uang;
use App\Models\ItemTransaksi;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

final class LayananLaporan
{
    /** @return array<string, mixed> */
    public function harian(string $tanggal): array
    {
        $ringkas = Transaksi::query()
            ->selesai()
            ->tanggal($tanggal)
            ->selectRaw('COUNT(*) AS jumlah')
            ->selectRaw('COALESCE(SUM(total_bayar), 0) AS omzet')
            ->selectRaw('COALESCE(SUM(ppn), 0) AS ppn')
            ->selectRaw('COALESCE(SUM(total_diskon), 0) AS diskon')
            ->first();

        $perMetode = Transaksi::query()
            ->selesai()
            ->tanggal($tanggal)
            ->groupBy('metode_bayar')
            ->pluck(DB::raw('SUM(total_bayar)'), 'metode_bayar')
            ->map(static fn ($nilai): int => (int) $nilai)
            ->all();

        $jumlah = (int) $ringkas->jumlah;
        $omzet = (int) $ringkas->omzet;

        return [
            'tanggal' => $tanggal,
            'jumlah_transaksi' => $jumlah,
            'omzet' => $omzet,
            'omzet_format' => (new Uang($omzet))->format(),
            'total_diskon' => (int) $ringkas->diskon,
            'total_ppn' => (int) $ringkas->ppn,
            'rata_rata_struk' => $jumlah > 0
                ? intdiv($omzet, $jumlah)
                : 0,
            'per_metode_bayar' => $perMetode,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function terlaris(string $tanggal, int $batas = 5): array
    {
        return ItemTransaksi::query()
            ->join(
                'transaksi',
                'transaksi.id',
                '=',
                'item_transaksi.transaksi_id'
            )
            ->where('transaksi.status', 'selesai')
            ->whereDate('transaksi.created_at', $tanggal)
            ->groupBy(
                'item_transaksi.sku',
                'item_transaksi.nama_produk'
            )
            ->orderByDesc('kuantitas')
            ->limit($batas)
            ->get([
                'item_transaksi.sku',
                'item_transaksi.nama_produk AS nama',
                DB::raw(
                    'SUM(item_transaksi.kuantitas) AS kuantitas'
                ),
                DB::raw(
                    'SUM(item_transaksi.total) AS pendapatan'
                ),
            ])
            ->map(static fn ($baris): array => [
                'sku' => $baris->sku,
                'nama' => $baris->nama,
                'kuantitas' => (int) $baris->kuantitas,
                'pendapatan' => (int) $baris->pendapatan,
            ])
            ->all();
    }
}
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoriTransaksi;
use App\Domain\Uang;

final class LayananLaporan
{
    public function __construct(
        private readonly RepositoriTransaksi $transaksi,
    ) {}

    public function harian(string $tanggal): array
    {
        $selesai = $this->transaksiSelesai($tanggal);

        $omzet = array_sum(array_column($selesai, 'total_bayar'));
        $ppn = array_sum(array_column($selesai, 'ppn'));
        $diskon = array_sum(array_column($selesai, 'total_diskon'));
        $jumlah = count($selesai);

        $perMetode = [];
        foreach ($selesai as $t) {
            $perMetode[$t['metode_bayar']] = ($perMetode[$t['metode_bayar']] ?? 0) + $t['total_bayar'];
        }

        return [
            'tanggal' => $tanggal,
            'jumlah_transaksi' => $jumlah,
            'omzet' => $omzet,
            'omzet_format' => (new Uang($omzet))->format(),
            'total_diskon' => $diskon,
            'total_ppn' => $ppn,
            'rata_rata_struk' => $jumlah > 0 ? intdiv($omzet, $jumlah) : 0,
            'per_metode_bayar' => $perMetode,
        ];
    }

    public function terlaris(string $tanggal, int $batas = 5): array
    {
        $rekap = [];
        foreach ($this->transaksiSelesai($tanggal) as $transaksi) {
            foreach ($transaksi['item'] as $baris) {
                $sku = $baris['sku'];
                $rekap[$sku] ??= [
                    'sku' => $sku,
                    'nama' => $baris['nama'],
                    'kuantitas' => 0,
                    'pendapatan' => 0,
                ];

                $rekap[$sku]['kuantitas'] += $baris['kuantitas'];
                $rekap[$sku]['pendapatan'] += $baris['total'];
            }
        }

        usort($rekap, static fn (array $a, array $b): int => $b['kuantitas'] <=> $a['kuantitas']);

        return array_slice($rekap, 0, $batas);
    }

    private function transaksiSelesai(string $tanggal): array
    {
        return array_values(array_filter(
            $this->transaksi->semua(),
            static fn (array $t): bool => $t['status'] === 'selesai'
                && str_starts_with($t['waktu'], $tanggal),
        ));
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Produk;
use App\Services\LayananKasir;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class RiwayatPenjualanSeeder extends Seeder
{
    private const HARI = 7;
    private const STRUK_PER_HARI = 40;

    public function run(LayananKasir $kasir): void
    {
        $sku = Produk::query()
            ->kategoriKode('minuman')
            ->where('stok', '>=', 300)
            ->orderBy('sku')
            ->pluck('sku')
            ->all();

        $jumlahSku = count($sku);
        $hariIni = CarbonImmutable::today();

        try {
            for ($h = self::HARI; $h >= 1; $h--) {
                for ($i = 0; $i < self::STRUK_PER_HARI; $i++) {

                    Carbon::setTestNow(
                        $hariIni
                            ->subDays($h)
                            ->setTime(8, 0)
                            ->addMinutes($i * 15)
                    );

                    $struk = $kasir->proses([
                        'item' => [
                            [
                                'sku' => $sku[$i % $jumlahSku],
                                'kuantitas' => 1,
                            ],
                            [
                                'sku' => $sku[($i + 1) % $jumlahSku],
                                'kuantitas' => 1,
                            ],
                        ],
                        'member' => $i % 3 === 0,
                        'metode_bayar' => $i % 2 === 0
                            ? 'qris'
                            : 'Kartu Debit',
                    ], 'Bambang Saputra');

                    if ($i % 10 === 9) {
                        $kasir->batalkan(
                            $struk['nomor'],
                            'Salah input kuantitas',
                            'Bagas Prakoso'
                        );
                    }
                }
            }
        } finally {
            Carbon::setTestNow();
        }
    }
}
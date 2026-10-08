<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Pemasok;
use App\Models\Produk;
use Illuminate\Database\Seeder;

final class PemasokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    private const PEMASOK = [
        ['PMS-01', 'CV Sumber Pangan Solo',      'Surakarta'],
        ['PMS-02', 'PT Distribusi Minuman Jaya', 'Semarang'],
        ['PMS-03', 'UD Alat Tulis Makmur',       'Klaten'],
    ];
    /** sku => [[kode pemasok, harga beli, utama?], ...] */
    private const PASOKAN = [
        'SKU-001' => [['PMS-01', 64800, true]],
        'SKU-002' => [['PMS-01', 16300, true]],
        'SKU-003' => [['PMS-01', 13900, true]],
        'SKU-004' => [['PMS-02', 20400, true], ['PMS-01', 21100, false]],
        'SKU-005' => [['PMS-02', 7900, true]],
        'SKU-006' => [['PMS-01', 2950, true]],
        'SKU-007' => [['PMS-02', 16500, true], ['PMS-01', 17200, false]],
        'SKU-008' => [['PMS-01', 4300, true]],
        'SKU-009' => [['PMS-03', 3700, true]],
        'SKU-010' => [['PMS-03', 2900, true]],
    ];

    public function run(): void
    {
        //
        $id = [];

        foreach (self::PEMASOK as [$kode, $nama, $kota]) {
            $id[$kode] = Pemasok::updateOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'kota' => $kota, 'aktif' => true],
            )->id;
        }

        foreach (self::PASOKAN as $sku => $daftar) {
            $data = [];

            foreach ($daftar as [$kode, $hargaBeli, $utama]) {
                $data[$id[$kode]] = ['harga_beli' => $hargaBeli, 'utama' => $utama];
            }

            Produk::query()->where('sku', $sku)->firstOrFail()->pemasok()->sync($data);
        }
    }
}

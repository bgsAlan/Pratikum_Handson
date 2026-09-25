<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoriProduk;
use App\Contracts\RepositoriTransaksi;
use App\Domain\MetodeBayar;
use App\Domain\Uang;
use App\Exceptions\PembayaranKurang;
use App\Exceptions\ProdukTidakDitemukan;
use App\Exceptions\StokTidakCukup;
use App\Exceptions\TransaksiSudahDibatalkan;
use App\Exceptions\TransaksiTidakDitemukan;

final class LayananKasir
{
    public function __construct(
        private readonly RepositoriProduk $produk,
        private readonly RepositoriTransaksi $transaksi,
    ) {}

    public function hitung(array $item, bool $member = false): array
    {
        $minimalGrosir = (int) config('pos.grosir.minimal_kuantitas');
        $persenGrosir  = (float) config('pos.grosir.persen');

        $baris = [];
        $subtotal = Uang::nol();
        $diskonItem = Uang::nol();

        foreach ($item as $masukan) {
            $produk = $this->produk->cariSku($masukan['sku']);
            if ($produk === null) {
                throw new ProdukTidakDitemukan($masukan['sku']);
            }

            $kuantitas = (int) $masukan['kuantitas'];
            if ($kuantitas > $produk['stok']) {
                throw new StokTidakCukup($produk['sku'], $kuantitas, $produk['stok']);
            }

            $hargaSatuan = new Uang($produk['harga']);
            $totalBaris  = $hargaSatuan->kali($kuantitas);

            $diskonBaris = $kuantitas >= $minimalGrosir
                ? $totalBaris->persen($persenGrosir)
                : Uang::nol();

            $subtotal   = $subtotal->tambah($totalBaris);
            $diskonItem = $diskonItem->tambah($diskonBaris);

            $baris[] = [
                'sku'          => $produk['sku'],
                'nama'         => $produk['nama'],
                'harga_satuan' => $hargaSatuan->rupiah,
                'kuantitas'    => $kuantitas,
                'diskon'       => $diskonBaris->rupiah,
                'total'        => $totalBaris->kurang($diskonBaris)->rupiah,
                'total_format' => $totalBaris->kurang($diskonBaris)->format(),
            ];
        }

        $diskonMember = $member
            ? $subtotal->kurang($diskonItem)->persen((float) config('pos.member.persen'))
            : Uang::nol();

        $totalDiskon = $diskonItem->tambah($diskonMember);
        $dpp         = $subtotal->kurang($totalDiskon);
        $ppn         = $dpp->persen((float) config('pos.ppn_persen'));
        $total       = $dpp->tambah($ppn);
        $totalBayar  = $total->bulatkanKeAtas((int) config('pos.pembulatan'));

        return [
            'item'               => $baris,
            'subtotal'           => $subtotal->rupiah,
            'diskon_grosir'      => $diskonItem->rupiah,
            'diskon_member'      => $diskonMember->rupiah,
            'total_diskon'       => $totalDiskon->rupiah,
            'dpp'                => $dpp->rupiah,
            'ppn'                => $ppn->rupiah,
            'total'              => $total->rupiah,
            'pembulatan'         => $totalBayar->kurang($total)->rupiah,
            'total_bayar'        => $totalBayar->rupiah,
            'total_bayar_format' => $totalBayar->format(),
        ];
    }

    public function proses(array $data, string $kasir): array
    {
        $metode = MetodeBayar::from($data['metode_bayar']);
        $member = (bool) ($data['member'] ?? false);

        $rincian    = $this->hitung($data['item'], $member);
        $totalBayar = new Uang($rincian['total_bayar']);

        $dibayar = match ($metode->butuhKembalian()) {
            true  => new Uang((int) ($data['dibayar'] ?? 0)),
            false => $totalBayar,
        };

        if ($dibayar->kurangDari($totalBayar)) {
            throw new PembayaranKurang($totalBayar->kurang($dibayar));
        }

        $transaksi = array_merge([
            'nomor'        => $this->nomorBaru(),
            'waktu'        => now()->toIso8601String(),
            'kasir'        => $kasir,
            'member'       => $member,
            'metode_bayar' => $metode->value,
            'metode_label' => $metode->label(),
            'status'       => 'selesai',
        ], $rincian, [
            'dibayar'   => $dibayar->rupiah,
            'kembalian' => $dibayar->kurang($totalBayar)->rupiah,
        ]);

        $this->transaksi->simpan($transaksi);

        return $transaksi;
    }

    public function batalkan(string $nomor, string $alasan, string $olehKasir): array
    {
        $transaksi = $this->transaksi->cariNomor($nomor);
        if ($transaksi === null) {
            throw new TransaksiTidakDitemukan($nomor);
        }

        if ($transaksi['status'] === 'batal') {
            throw new TransaksiSudahDibatalkan($nomor);
        }

        $perubahan = [
            'status'          => 'batal',
            'alasan_batal'    => $alasan,
            'dibatalkan_oleh' => $olehKasir,
            'dibatalkan_pada' => now()->toIso8601String(),
        ];

        $this->transaksi->perbarui($nomor, $perubahan);

        return array_merge($transaksi, $perubahan);
    }

    public function transaksiTanggal(string $tanggal): array
    {
        return array_values(array_filter(
            $this->transaksi->semua(),
            static fn (array $t): bool => str_starts_with($t['waktu'], $tanggal),
        ));
    }

    public function cari(string $nomor): array
    {
        $transaksi = $this->transaksi->cariNomor($nomor);
        if ($transaksi === null) {
            throw new TransaksiTidakDitemukan($nomor);
        }

        return $transaksi;
    }

    private function nomorBaru(): string
    {
        $tanggal = now()->format('Ymd');
        $urut = count(array_filter(
            $this->transaksi->semua(),
            static fn (array $t): bool => str_starts_with($t['nomor'], "POS-{$tanggal}"),
        )) + 1;

        return sprintf('POS-%s-%04d', $tanggal, $urut);
    }
}
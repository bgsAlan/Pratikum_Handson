<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\StatusTransaksi;
use Illuminate\Support\Facades\DB;
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
        $persenGrosir = (float) config('pos.grosir.persen');

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
            $totalBaris = $hargaSatuan->kali($kuantitas);

            $diskonBaris = $kuantitas >= $minimalGrosir
                ? $totalBaris->persen($persenGrosir)
                : Uang::nol();

            $subtotal = $subtotal->tambah($totalBaris);
            $diskonItem = $diskonItem->tambah($diskonBaris);

            $baris[] = [
                'sku' => $produk['sku'],
                'nama' => $produk['nama'],
                'harga_satuan' => $hargaSatuan->rupiah,
                'kuantitas' => $kuantitas,
                'diskon' => $diskonBaris->rupiah,
                'total' => $totalBaris->kurang($diskonBaris)->rupiah,
                'total_format' => $totalBaris->kurang($diskonBaris)->format(),
            ];
        }

        $diskonMember = $member
            ? $subtotal->kurang($diskonItem)->persen((float) config('pos.member.persen'))
            : Uang::nol();

        $totalDiskon = $diskonItem->tambah($diskonMember);
        $dpp = $subtotal->kurang($totalDiskon);
        $ppn = $dpp->persen((float) config('pos.ppn_persen'));
        $total = $dpp->tambah($ppn);
        $totalBayar = $total->bulatkanKeAtas((int) config('pos.pembulatan'));

        return [
            'item' => $baris,
            'subtotal' => $subtotal->rupiah,
            'diskon_grosir' => $diskonItem->rupiah,
            'diskon_member' => $diskonMember->rupiah,
            'total_diskon' => $totalDiskon->rupiah,
            'dpp' => $dpp->rupiah,
            'ppn' => $ppn->rupiah,
            'total' => $total->rupiah,
            'pembulatan' => $totalBayar->kurang($total)->rupiah,
            'total_bayar' => $totalBayar->rupiah,
            'total_bayar_format' => $totalBayar->format(),
        ];
    }

    public function proses(array $data, string $kasir): array
    {
        // Semua atau tidak sama sekali: gagal di langkah mana pun, semua dibatalkan.
        return DB::transaction(function () use ($data, $kasir): array {

            // 1. Kunci baris produk, lalu cek ulang stok saat TERKUNCI (AB-8)
            foreach ($data['item'] as $baris) {
                $tersedia = $this->produk->kunciStok($baris['sku']);

                if ($baris['kuantitas'] > $tersedia) {
                    throw new StokTidakCukup($baris['sku'], (int) $baris['kuantitas'], $tersedia);
                }
            }

            // 2. Hitung uang (AB-1 s.d. AB-6), sama seperti sebelumnya
            $metode = MetodeBayar::from($data['metode_bayar']);
            $member = (bool) ($data['member'] ?? false);

            $rincian = $this->hitung($data['item'], $member);
            $totalBayar = new Uang($rincian['total_bayar']);

            $dibayar = match ($metode->butuhKembalian()) {
                true => new Uang((int) ($data['dibayar'] ?? 0)),
                false => $totalBayar,
            };

            if ($dibayar->kurangDari($totalBayar)) {
                throw new PembayaranKurang($totalBayar->kurang($dibayar));
            }

            // 3. Susun struk lalu simpan
            $transaksi = array_merge([
                'nomor' => $this->nomorBaru(),
                'kasir' => $kasir,
                'member' => $member,
                'metode_bayar' => $metode->value,
                'status' => StatusTransaksi::Selesai->value,
            ], $rincian, [
                'dibayar' => $dibayar->rupiah,
                'kembalian' => $dibayar->kurang($totalBayar)->rupiah,
            ]);

            $this->transaksi->simpan($transaksi);

            // 4. Kurangi stok (AB-11)
            foreach ($data['item'] as $baris) {
                $this->produk->ubahStok($baris['sku'], -1 * (int) $baris['kuantitas']);
            }

            return $this->transaksi->cariNomor($transaksi['nomor']);
        });
    }

    public function batalkan(string $nomor, string $alasan, string $olehKasir): array
    {
        return DB::transaction(function () use ($nomor, $alasan, $olehKasir): array {
            $transaksi = $this->transaksi->cariNomor($nomor);

            if ($transaksi === null) {
                throw new TransaksiTidakDitemukan($nomor);          // 404
            }

            if ($transaksi['status'] === StatusTransaksi::Batal->value) {
                throw new TransaksiSudahDibatalkan($nomor);         // 409, AB-10
            }

            $this->transaksi->perbarui($nomor, [
                'status' => StatusTransaksi::Batal->value,
                'alasan_batal' => $alasan,
                'dibatalkan_oleh' => $olehKasir,
                'dibatalkan_pada' => now(),
            ]);

            // AB-11: barang kembali ke rak
            foreach ($transaksi['item'] as $baris) {
                $this->produk->ubahStok($baris['sku'], (int) $baris['kuantitas']);
            }

            return $this->transaksi->cariNomor($nomor);
        });
    }

    public function transaksiTanggal(string $tanggal): array
    {
        return $this->transaksi->tanggal($tanggal);
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
        $tanggal = now()->toDateString();

        return sprintf(
            'POS-%s-%04d',
            now()->format('Ymd'),
            $this->transaksi->urutanBerikutnya($tanggal),
        );
    }
}

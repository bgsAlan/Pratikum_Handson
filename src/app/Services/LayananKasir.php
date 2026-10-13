<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoriProduk;
use App\Contracts\RepositoriTransaksi;
use App\Domain\MetodeBayar;
use App\Domain\StatusTransaksi;
use App\Domain\Uang;
use App\Exceptions\PembayaranKurang;
use App\Exceptions\StokTidakCukup;
use App\Exceptions\TransaksiSudahDibatalkan;
use App\Exceptions\TransaksiTidakDitemukan;
use App\Exceptions\ProdukTidakDitemukan;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Layanan utama untuk proses kasir.
 *
 * Aturan bisnis AB-1 sampai AB-11 tetap dipertahankan.
 */
final class LayananKasir
{
    public function __construct(
        private readonly RepositoriProduk $produk,
        private readonly RepositoriTransaksi $transaksi,
    ) {}

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function hitung(array $item, bool $member = false): array
    {
        $sku = array_column($item, 'sku');
        $produkTersedia = $this->produk->cariBanyakSku($sku);

        $minimalGrosir = (int) config('pos.grosir.minimal_kuantitas');
        $persenGrosir = (float) config('pos.grosir.persen');

        $baris = [];
        $subtotal = Uang::nol();
        $diskonItem = Uang::nol();

        foreach ($item as $masukan) {
            $kode = strtoupper(trim($masukan['sku']));
            $produk = $produkTersedia[$kode] ?? null;

            if ($produk === null) {
                throw new ProdukTidakDitemukan($masukan['sku']);
            }

            $kuantitas = (int) $masukan['kuantitas'];

            if ($kuantitas > $produk['stok']) {
                throw new StokTidakCukup(
                    $produk['sku'],
                    $kuantitas,
                    $produk['stok'],
                );
            }

            $hargaSatuan = new Uang($produk['harga']);
            $totalBaris = $hargaSatuan->kali($kuantitas);

            $diskonBaris = $kuantitas >= $minimalGrosir
                ? $totalBaris->persen($persenGrosir)
                : Uang::nol();

            $subtotal = $subtotal->tambah($totalBaris);
            $diskonItem = $diskonItem->tambah($diskonBaris);

            $baris[] = [
                '_produk_id' => $produk['_produk_id'],
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
            ? $subtotal->kurang($diskonItem)->persen(
                (float) config('pos.member.persen')
            )
            : Uang::nol();

        $totalDiskon = $diskonItem->tambah($diskonMember);
        $dpp = $subtotal->kurang($totalDiskon);
        $ppn = $dpp->persen((float) config('pos.ppn_persen'));
        $total = $dpp->tambah($ppn);
        $totalBayar = $total->bulatkanKeAtas(
            (int) config('pos.pembulatan')
        );

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
        ];
    }

    public function proses(array $data, string $kasir): Transaksi
    {
        return DB::transaction(function () use ($data, $kasir): Transaksi {
            $metode = MetodeBayar::from($data['metode_bayar']);
            $member = (bool) ($data['member'] ?? false);

            $rincian = $this->hitung($data['item'], $member);

            $jumlahPerSku = [];

            foreach ($data['item'] as $baris) {
                $sku = strtoupper(trim($baris['sku']));
                $jumlahPerSku[$sku] = ($jumlahPerSku[$sku] ?? 0)
                    + (int) $baris['kuantitas'];
            }

            $stokTerkunci = $this->produk->kunciBanyakStok(
                array_keys($jumlahPerSku)
            );

            foreach ($jumlahPerSku as $sku => $kuantitas) {
                if (!array_key_exists($sku, $stokTerkunci)) {
                    throw new ProdukTidakDitemukan($sku);
                }

                $tersedia = $stokTerkunci[$sku];

                if ($kuantitas > $tersedia) {
                    throw new StokTidakCukup($sku, $kuantitas, $tersedia);
                }
            }

            $totalBayar = new Uang($rincian['total_bayar']);

            $dibayar = $metode->butuhKembalian()
                ? new Uang((int) ($data['dibayar'] ?? 0))
                : $totalBayar;

            if ($dibayar->kurangDari($totalBayar)) {
                throw new PembayaranKurang(
                    $totalBayar->kurang($dibayar),
                );
            }

            $dataTransaksi = array_merge(
                [
                    'nomor' => $this->nomorBaru(),
                    'kasir' => $kasir,
                    'member' => $member,
                    'metode_bayar' => $metode->value,
                    'status' => StatusTransaksi::Selesai->value,
                ],
                $rincian,
                [
                    'dibayar' => $dibayar->rupiah,
                    'kembalian' => $dibayar->kurang($totalBayar)->rupiah,
                ],
            );

            $this->transaksi->simpan($dataTransaksi);

            $perubahanStok = [];

            foreach ($jumlahPerSku as $sku => $kuantitas) {
                $perubahanStok[$sku] = -$kuantitas;
            }

            $this->produk->ubahBanyakStok($perubahanStok);

            $hasil = $this->transaksi->cariNomor(
                $dataTransaksi['nomor']
            );

            if ($hasil === null) {
                throw new TransaksiTidakDitemukan($dataTransaksi['nomor']);
            }

            return $hasil;
        });
    }

    public function batalkan(
        string $nomor,
        string $alasan,
        string $olehKasir,
    ): Transaksi {
        return DB::transaction(function () use (
            $nomor,
            $alasan,
            $olehKasir,
        ): Transaksi {
            $transaksi = $this->transaksi->cariNomor($nomor);

            if ($transaksi === null) {
                throw new TransaksiTidakDitemukan($nomor);
            }

            if ($transaksi->status === StatusTransaksi::Batal) {
                throw new TransaksiSudahDibatalkan($nomor);
            }

            $this->transaksi->perbarui($nomor, [
                'status' => StatusTransaksi::Batal->value,
                'alasan_batal' => $alasan,
                'dibatalkan_oleh' => $olehKasir,
                'dibatalkan_pada' => now(),
            ]);

            $perubahanStok = [];

            foreach ($transaksi->item as $baris) {
                $sku = strtoupper(trim($baris->sku));

                $perubahanStok[$sku] = ($perubahanStok[$sku] ?? 0)
                    + (int) $baris->kuantitas;
            }

            $this->produk->ubahBanyakStok($perubahanStok);

            $hasil = $this->transaksi->cariNomor($nomor);

            if ($hasil === null) {
                throw new TransaksiTidakDitemukan($nomor);
            }

            return $hasil;
        });
    }

    public function transaksiTanggal(string $tanggal): Collection
    {
        return $this->transaksi->tanggal($tanggal);
    }

    public function cari(string $nomor): Transaksi
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
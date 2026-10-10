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
                throw new \App\Exceptions\ProdukTidakDitemukan($masukan['sku']);
            }

            $kuantitas = (int) $masukan['kuantitas'];

            if ($kuantitas > $produk['stok']) {
                throw new StokTidakCukup($produk['sku'], $kuantitas, $produk['stok']);
            }

            $hargaSatuan = new Uang($produk['harga']);
            $totalBaris = $hargaSatuan->kali($kuantitas);

            $diskonBaris = $kuantitas >= $minimalGrosir ? $totalBaris->persen($persenGrosir) : Uang::nol();

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

        $diskonMember = $member ? $subtotal->kurang($diskonItem)->persen((float) config('pos.member.persen')) : Uang::nol();

        $totalDiskon = $diskonItem->tambah($diskonMember);
        $dpp = $subtotal->kurang($totalDiskon);
        $ppn = $dpp->persen((float) config('pos.ppn_persen'));
        $total = $dpp->tambah($ppn);

        $totalBayar = $total->bulatkanKeAtas((int) config('pos.pembulatan'));

        return ['item' => $baris, 'subtotal' => $subtotal->rupiah, 'diskon_grosir' => $diskonItem->rupiah, 'diskon_member' => $diskonMember->rupiah, 'total_diskon' => $totalDiskon->rupiah, 'dpp' => $dpp->rupiah, 'ppn' => $ppn->rupiah, 'total' => $total->rupiah, 'pembulatan' => $totalBayar->kurang($total)->rupiah, 'total_bayar' => $totalBayar->rupiah,];
    }

    /**
     * Memproses transaksi kasir.
     *
     * Urutan validasi Langkah 17:
     * 1. Hitung katalog terlebih dahulu.
     * 2. Kunci stok dan periksa ulang stok.
     * 3. Validasi pembayaran.
     * 4. Simpan transaksi.
     * 5. Kurangi stok.
     */
    public function proses(array $data, string $kasir): array
    {
        return DB::transaction(function () use ($data, $kasir): array {
            // 1. Validasi katalog dan stok awal terlebih dahulu.
            //
            // hitung() menggunakan cariSku(). Jika SKU tidak ditemukan,
            // proses langsung menghasilkan ProdukTidakDitemukan.
            $metode = MetodeBayar::from($data['metode_bayar']);
            $member = (bool) ($data['member'] ?? false);

            $rincian = $this->hitung(
                $data['item'],
                $member
            );

            // 2. Kunci stok dan lakukan pemeriksaan ulang.
            //
            // Pemeriksaan kedua dilakukan setelah baris produk dikunci
            // sehingga stok yang digunakan untuk menentukan transaksi
            // merupakan stok dalam kondisi terkunci (AB-8).

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
                    throw new \App\Exceptions\ProdukTidakDitemukan($sku);
                }

                $tersedia = $stokTerkunci[$sku];

                if ($kuantitas > $tersedia) {
                    throw new StokTidakCukup(
                        $sku,
                        $kuantitas,
                        $tersedia
                    );
                }
            }

            // 3. Validasi pembayaran (AB-7 dan AB-9).
            $totalBayar = new Uang(
                $rincian['total_bayar']
            );

            $dibayar = $metode->butuhKembalian()
                ? new Uang((int) ($data['dibayar'] ?? 0))
                : $totalBayar;

            if ($dibayar->kurangDari($totalBayar)) {
                throw new PembayaranKurang(
                    $totalBayar->kurang($dibayar),
                );
            }

            // 4. Susun dan simpan struk.
            $transaksi = array_merge(
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
                    'kembalian' => $dibayar
                        ->kurang($totalBayar)
                        ->rupiah,
                ],
            );

            $this->transaksi->simpan($transaksi);

            // 5. Kurangi stok setelah transaksi berhasil disimpan (AB-11).
            $perubahanStok = [];
            foreach ($jumlahPerSku as $sku => $kuantitas) {
                $perubahanStok[$sku] = -$kuantitas;
            }
            $this->produk->ubahBanyakStok($perubahanStok);

            return $this->transaksi->cariNomor(
                $transaksi['nomor']
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function batalkan(
        string $nomor,
        string $alasan,
        string $olehKasir,
    ): array {
        return DB::transaction(function () use (
            $nomor,
            $alasan,
            $olehKasir,
        ): array {
            $transaksi = $this->transaksi->cariNomor($nomor);

            if ($transaksi === null) {
                throw new TransaksiTidakDitemukan($nomor);
            }

            if ($transaksi['status'] === StatusTransaksi::Batal->value) {
                throw new TransaksiSudahDibatalkan($nomor);
            }

            $this->transaksi->perbarui($nomor, [
                'status' => StatusTransaksi::Batal->value,
                'alasan_batal' => $alasan,
                'dibatalkan_oleh' => $olehKasir,
                'dibatalkan_pada' => now(),
            ]);

            // AB-11: stok barang dikembalikan.
            $perubahanStok = [];
            foreach ($transaksi['item'] as $baris) {
                $sku = strtoupper(trim($baris['sku']));
                $perubahanStok[$sku] = ($perubahanStok[$sku] ?? 0)
                    + (int) $baris['kuantitas'];
            }
            $this->produk->ubahBanyakStok($perubahanStok);
            return $this->transaksi->cariNomor($nomor);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function transaksiTanggal(string $tanggal): array
    {
        return $this->transaksi->tanggal($tanggal);
    }

    /**
     * @return array<string, mixed>
     */
    public function cari(string $nomor): array
    {
        $transaksi = $this->transaksi->cariNomor($nomor);

        if ($transaksi === null) {
            throw new TransaksiTidakDitemukan($nomor);
        }

        return $transaksi;
    }

    /**
     * Nomor struk POS-YYYYMMDD-0001.
     */
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



<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoriProduk;
use App\Domain\Kategori;
use App\Domain\Uang;
use App\Exceptions\ProdukTidakDitemukan;
use App\Models\Pemasok;
use App\Models\Produk;

final class LayananKatalog
{
    public function __construct(
        private readonly RepositoriProduk $repositori,
    ) {}

    public function daftar(
        ?string $kategori = null,
        ?string $cari = null,
    ): array {
        $produk = $this->repositori->semua();

        if ($kategori !== null) {
            $produk = array_filter(
                $produk,
                static fn (array $p): bool => $p['kategori'] === $kategori,
            );
        }

        if ($cari !== null) {
            $kunci = mb_strtolower($cari);

            $produk = array_filter(
                $produk,
                static fn (array $p): bool => str_contains(
                    mb_strtolower($p['nama']),
                    $kunci,
                ),
            );
        }

        return array_values(
            array_map($this->format(...), $produk),
        );
    }

    public function ambil(string $sku): array
    {
        $produk = $this->repositori->cariSku($sku);

        if ($produk === null) {
            throw new ProdukTidakDitemukan($sku);
        }

        return $this->format($produk);
    }

    private function format(array $produk): array
    {
        $kategori = Kategori::from($produk['kategori']);
        $harga = new Uang($produk['harga']);

        return [
            'sku' => $produk['sku'],
            'nama' => $produk['nama'],
            'kategori' => $kategori->value,
            'kategori_label' => $kategori->label(),
            'harga' => $harga->rupiah,
            'harga_format' => $harga->format(),
            'stok' => $produk['stok'],
            'tersedia' => $produk['stok'] > 0,
        ];
    }

    /**
     * Produk beserta pemasok dan marginnya (AB-13).
     *
     * Perhitungan margin tetap dilakukan di service.
     * Hasilnya dititipkan sebagai atribut sementara pada model,
     * kemudian ditampilkan oleh Resource.
     */
    public function denganPemasok(string $sku): Produk
    {
        $produk = $this->repositori->cariPemasok($sku);

        if ($produk === null) {
            throw new ProdukTidakDitemukan($sku);
        }

        $produk->pemasok->each(
            function (Pemasok $pemasok) use ($produk): void {
                $margin = $produk->harga
                    - (int) $pemasok->pasokan->harga_beli;

                $pemasok->setAttribute('margin', $margin);

                $pemasok->setAttribute(
                    'margin_persen',
                    round($margin * 100 / $produk->harga, 1),
                );
            },
        );

        return $produk;
    }

    /**
     * Mengambil data pemasok dalam bentuk array.
     *
     * Method ini dipertahankan untuk pemanggil lama.
     *
     * @return array<string, mixed>
     */
    public function pemasok(string $sku): array
    {
        $produk = $this->repositori->cariPemasok($sku);

        if ($produk === null) {
            throw new ProdukTidakDitemukan($sku);
        }

        $pemasok = $produk->pemasok->map(
            static function (Pemasok $p): array {
                return [
                    'kode' => $p->kode,
                    'nama' => $p->nama,
                    'kota' => $p->kota,
                    'harga_beli' => (int) $p->pasokan->harga_beli,
                    'utama' => (bool) $p->pasokan->utama,
                ];
            },
        )->all();

        foreach ($pemasok as &$p) {
            $margin = $produk->harga - $p['harga_beli'];

            $p['margin'] = $margin;
            $p['margin_persen'] = round(
                $margin * 100 / $produk->harga,
                1,
            );
        }

        unset($p);

        return [
            'sku' => $produk->sku,
            'nama' => $produk->nama,
            'harga' => $produk->harga,
            'pemasok' => $pemasok,
        ];
    }
}
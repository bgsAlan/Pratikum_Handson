<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\RepositoriProduk;
use App\Domain\Kategori;
use App\Domain\Uang;
use App\Exceptions\ProdukTidakDitemukan;

final class LayananKatalog
{
    public function __construct(
        private readonly RepositoriProduk $repositori,
    ) {}

    public function daftar(?string $kategori = null, ?string $cari = null): array
    {
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
                static fn (array $p): bool => str_contains(mb_strtolower($p['nama']), $kunci),
            );
        }

        return array_values(array_map($this->format(...), $produk));
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
}

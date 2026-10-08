<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoriProduk;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pengganti RepositoriProdukArray dari Modul 3.
 *
 * Bentuk nilai kembaliannya sengaja dijaga persis sama: array asosiatif
 * berisi sku, nama, kategori, harga, dan stok. Karena itulah LayananKatalog,
 * LayananKasir, dan seluruh controller tidak berubah satu baris pun.
 */
final class RepositoriProdukEloquent implements RepositoriProduk
{
    public function semua(): array
    {
        return $this->dasar()
            ->orderBy('produk.nama')
            ->get()
            ->map($this->keArray(...))
            ->all();
    }
    public function cariSku(string $sku): ?array
    {
        $produk = $this->dasar(aktifSaja: false)
            ->where('produk.sku', strtoupper(trim($sku)))
            ->first();
        return $produk === null ? null : $this->keArray($produk);
    }
    public function kunciStok(string $sku): int
    {
        // lockForUpdate() menahan baris ini sampai transaksi basis data
        // selesai, sehingga dua kasir yang menjual barang terakhir pada
        // saat bersamaan tidak dapat membaca stok yang sama.
        return (int) Produk::query()
            ->where('sku', strtoupper(trim($sku)))
            ->lockForUpdate()
            ->value('stok');
    }
    public function ubahStok(string $sku, int $selisih): void
    {
        // increment() menghasilkan satu perintah UPDATE atomik:
        // UPDATE produk SET stok = stok + ? WHERE sku = ?
        // Ini berbeda dari membaca stok ke PHP lalu menuliskannya kembali,
        // yang membuka celah balapan (race condition).
        // Praktik Pemrograman Back End · Hands-On Modul 4 · Studi Kasus Point of Sales
        // D3 Teknik Informatika – Sekolah Vokasi UNS | Halaman 23 dari 47
        Produk::query()
            ->where('sku', strtoupper(trim($sku)))
            ->increment('stok', $selisih);
    }
    /**
     * Kueri dasar: produk digabung dengan kategori lewat join eksplisit.
     * Relasi Eloquent (belongsTo/hasMany) baru diperkenalkan pada Modul 5.
     */
    private function dasar(bool $aktifSaja = true): Builder
    {
        return Produk::query()
            ->with('kategori:id,kode')
            ->when($aktifSaja, fn(Builder $q) => $q->aktif());
    }
    /** @return array<string, mixed> */
    private function keArray(Produk $produk): array
    {
        return [
            'sku' => $produk->sku,
            'nama' => $produk->nama,
            'kategori' => $produk->kategori->kode,
            'harga' => $produk->harga,
            'stok' => $produk->stok,
        ];
    }
}

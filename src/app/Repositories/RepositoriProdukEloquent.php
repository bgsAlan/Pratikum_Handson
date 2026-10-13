<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoriProduk;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pengganti RepositoriProdukArray dari Modul 3.
 *
 * Bentuk nilai kembaliannya dijaga untuk method-method
 * katalog dan kasir yang masih menggunakan array.
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

    private function normal(string $sku): string
    {
        return strtoupper(trim($sku));
    }

    public function cariSku(string $sku): ?array
    {
        $produk = $this->dasar(aktifSaja: false)
            ->where('produk.sku', $this->normal($sku))
            ->first();

        return $produk === null ? null : $this->keArray($produk);
    }

    /**
     * Mengambil banyak produk dalam satu query utama.
     *
     * @param array<int, string> $sku
     * @return array<string, array<string, mixed>>
     */
    public function cariBanyakSku(array $sku): array
    {
        $sku = array_values(array_unique(array_map(
            $this->normal(...),
            $sku
        )));

        if ($sku === []) {
            return [];
        }

        return $this->dasar(aktifSaja: false)
            ->whereIn('produk.sku', $sku)
            ->get()
            ->mapWithKeys(fn (Produk $produk) => [
                $produk->sku => [
                    ...$this->keArray($produk),
                    '_produk_id' => $produk->id,
                ],
            ])
            ->all();
    }

    /**
     * Mengunci stok produk dalam urutan SKU yang konsisten.
     *
     * @param array<int, string> $sku
     * @return array<string, int>
     */
    public function kunciBanyakStok(array $sku): array
    {
        $sku = array_values(array_unique(array_map(
            $this->normal(...),
            $sku
        )));

        sort($sku);

        if ($sku === []) {
            return [];
        }

        return Produk::query()
            ->whereIn('sku', $sku)
            ->orderBy('sku')
            ->lockForUpdate()
            ->get(['sku', 'stok'])
            ->mapWithKeys(fn (Produk $produk) => [
                $produk->sku => (int) $produk->stok,
            ])
            ->all();
    }

    /**
     * Memperbarui stok beberapa produk menggunakan satu UPDATE CASE.
     *
     * @param array<string, int> $perubahan
     */
    public function ubahBanyakStok(array $perubahan): void
    {
        $normal = [];

        foreach ($perubahan as $sku => $selisih) {
            $skuNormal = $this->normal((string) $sku);

            $normal[$skuNormal] = ($normal[$skuNormal] ?? 0)
                + (int) $selisih;
        }

        $normal = array_filter(
            $normal,
            static fn (int $selisih): bool => $selisih !== 0
        );

        if ($normal === []) {
            return;
        }

        $sku = array_keys($normal);
        sort($sku);

        $case = [];
        $bindings = [];

        foreach ($sku as $kode) {
            $case[] = 'WHEN ? THEN stok + ?';
            $bindings[] = $kode;
            $bindings[] = $normal[$kode];
        }

        $placeholder = implode(
            ', ',
            array_fill(0, count($sku), '?')
        );

        $bindings = array_merge($bindings, $sku);

        $tabel = (new Produk())->getTable();

        $sql = sprintf(
            'UPDATE `%s` SET `stok` = CASE `sku` %s ELSE `stok` END WHERE `sku` IN (%s)',
            str_replace('`', '``', $tabel),
            implode(' ', $case),
            $placeholder,
        );

        DB::update($sql, $bindings);
    }

    public function kunciStok(string $sku): int
    {
        return (int) Produk::query()
            ->where('sku', $this->normal($sku))
            ->lockForUpdate()
            ->value('stok');
    }

    public function ubahStok(string $sku, int $selisih): void
    {
        Produk::query()
            ->where('sku', $this->normal($sku))
            ->increment('stok', $selisih);
    }

    /**
     * Kueri dasar produk beserta kategori.
     */
    private function dasar(bool $aktifSaja = true): Builder
    {
        return Produk::query()
            ->with('kategori:id,kode')
            ->when(
                $aktifSaja,
                fn (Builder $q) => $q->aktif()
            );
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * Mengambil produk beserta pemasok dan data pasokannya.
     */
    public function cariPemasok(string $sku): ?Produk
    {
        return Produk::query()
            ->with(['pemasok.pasokan'])
            ->where('sku', $this->normal($sku))
            ->first();
    }
}
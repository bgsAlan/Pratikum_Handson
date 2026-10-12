<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Produk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ProdukQuery
{
    // Daftar putih pengurutan: jangan pernah kirim ?urut= mentah ke orderBy()
    private const URUT = ['nama', 'harga', 'stok'];

    // Daftar putih sparse fieldset
    private const KOLOM = [
        'sku', 'nama', 'kategori', 'kategori_label',
        'harga', 'harga_format', 'stok', 'tersedia',
    ];

    public function terapkan(Request $request): Builder
    {
        return Produk::query()
            ->with('kategori:id,kode,nama')
            ->aktif()

            ->when($request->filled('cari'), function (Builder $q) use ($request): void {
                $kata = $request->string('cari')->trim()->toString();
                $q->where('produk.nama', 'like', '%' . $kata . '%');
            })
            ->when($request->filled('kategori'), fn (Builder $q): Builder =>
                $q->kategoriKode($request->string('kategori')->toString()))
            ->when($request->filled('harga_min'), fn (Builder $q): Builder =>
                $q->where('produk.harga', '>=', $request->integer('harga_min')))
            ->when($request->filled('harga_max'), fn (Builder $q): Builder =>
                $q->where('produk.harga', '<=', $request->integer('harga_max')))
            ->when($request->boolean('tersedia'), fn (Builder $q): Builder =>
                $q->tersedia())

            ->orderBy($this->kolomUrut($request), $this->arahUrut($request))
            // Pengurutan kedua yang unik, supaya baris tidak bertukar antar halaman
            ->orderBy('produk.sku');
    }

    /** @return array<int, string>|null */
    public function kolomDiminta(Request $request): ?array
    {
        if (! $request->filled('kolom')) {
            return null;
        }

        $diminta = array_map('trim', explode(',', $request->string('kolom')->toString()));
        $sah = array_values(array_intersect($diminta, self::KOLOM));

        // Semua tidak dikenal -> diabaikan (kirim semua kunci), bukan data kosong
        return $sah === [] ? null : $sah;
    }

    private function kolomUrut(Request $request): string
    {
        $sort = $request->string('urut', 'nama')->toString();
        return in_array($sort, self::URUT, true) ? 'produk.' . $sort : 'produk.nama';
    }

    private function arahUrut(Request $request): string
    {
        $arah = strtolower($request->string('arah', 'asc')->toString());
        return in_array($arah, ['asc', 'desc'], true) ? $arah : 'asc';
    }
}
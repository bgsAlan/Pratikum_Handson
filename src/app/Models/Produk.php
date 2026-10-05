<?php

namespace App\Models;

use App\Domain\Uang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produk';

    protected $fillable = [
        'kategori_id',
        'sku',
        'nama',
        'harga',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'stok' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('produk.aktif', true);
    }
    public function scopeTersedia($query)
    {
        return $query->where('produk.stok', '>', 0);
    }
    public function scopeKategoriKode($query, string $kode)
    {
        // Subquery: relasi Eloquent baru dibahas pada Modul 5.
        return $query->whereIn(
            'kategori_id',
            Kategori::query()->where('kode', $kode)->select('id'),
        );
    }
    /** Dipakai Uang dari Modul 2 agar format rupiah hanya ditulis satu kali. */
    public function hargaFormat(): string
    {
        return (new Uang($this->harga))->format();
    }
}

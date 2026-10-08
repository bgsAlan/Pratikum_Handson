<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Domain\Kategori as EnumKategori;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class Kategori extends Model
{
    use HasFactory;

    protected $table = 'kategori';
    protected $fillable = ['kode', 'nama', 'aktif'];


    protected function casts(): array
    {
        return [
            'aktif' => 'boolean'
        ];
    }
    public function enum(): EnumKategori
    {
        return EnumKategori::from($this->kode);
    }
    public function scopeAktif($query)
    {
        return $query->where('kategori.aktif', true);
    }
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    public function itemTerjual(): HasManyThrough
    {
        return $this->hasManyThrough(
            ItemTransaksi::class,
            Produk::class,
            'kategori_id',   // FK di tabel perantara (produk)
            'produk_id',     // FK di tabel tujuan (item_transaksi)
        )->withTrashedParents();
    }
}

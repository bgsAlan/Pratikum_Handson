<?php

namespace App\Models;

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

}

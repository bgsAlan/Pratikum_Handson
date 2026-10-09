<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
final class Pemasok extends Model
{
    //
    protected $table = 'pemasok';

    protected $fillable = ['kode', 'nama', 'kota', 'telepon', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function produk(): BelongsToMany
    {
        return $this->belongsToMany(Produk::class, 'pemasok_produk')
            ->as('pasokan')
            ->withPivot(['harga_beli', 'utama'])
            ->withTimestamps();
    }
    
}

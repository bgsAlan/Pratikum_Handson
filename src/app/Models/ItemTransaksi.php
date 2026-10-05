<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ItemTransaksi extends Model
{
    use HasFactory;

    protected $table = 'item_transaksi';

    protected $fillable = [
        'transaksi_id', 'produk_id', 'sku', 'nama_produk',
        'harga_satuan', 'kuantitas', 'diskon', 'total',
    ];

    protected function casts(): array
    {
        return [
            'harga_satuan' => 'integer',
            'kuantitas'    => 'integer',
            'diskon'       => 'integer',
            'total'        => 'integer',
        ];
    }
}
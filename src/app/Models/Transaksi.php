<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\MetodeBayar;
use App\Domain\StatusTransaksi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi'; // tanpa ini Laravel mencari "transaksis"

    protected $fillable = [
        'nomor',
        'kasir',
        'member',
        'metode_bayar',
        'status',
        'subtotal',
        'diskon_grosir',
        'diskon_member',
        'total_diskon',
        'dpp',
        'ppn',
        'total',
        'pembulatan',
        'total_bayar',
        'dibayar',
        'kembalian',
        'alasan_batal',
        'dibatalkan_oleh',
        'dibatalkan_pada',
    ];

    protected function casts(): array
    {
        return [
            'member'          => 'boolean',
            'metode_bayar'    => MetodeBayar::class,
            'status'          => StatusTransaksi::class,
            'dibatalkan_pada' => 'datetime',
        ];
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', StatusTransaksi::Selesai);
    }

    public function scopeTanggal($query, string $tanggal)
    {
        return $query->whereDate('created_at', $tanggal);
    }
    public function item(): HasMany
    {
        return $this->hasMany(ItemTransaksi::class, 'transaksi_id');
    }
}

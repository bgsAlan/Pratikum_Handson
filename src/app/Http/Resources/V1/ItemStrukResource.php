<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\ItemTransaksi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ItemTransaksi
 */
final class ItemStrukResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sku' => $this->sku,

            // Kolomnya bernama nama_produk, kuncinya tetap "nama".
            // Inilah gunanya Resource: nama kolom boleh berubah tanpa
            // satu pun klien ikut berubah.
            'nama' => $this->nama_produk,
            'harga_satuan' => $this->harga_satuan,
            'kuantitas' => $this->kuantitas,
            'diskon' => $this->diskon,
            'total' => $this->total,
        ];
    }
}
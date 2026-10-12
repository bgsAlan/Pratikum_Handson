<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Domain\Uang;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Produk
 *
 * Rumah baru bagi pekerjaan yang sejak Modul 3 dilakukan keArray().
 * Bedanya: berkas ini HANYA mengurus bentuk tampilan, tidak tahu
 * apa-apa tentang penyimpanan maupun aturan bisnis.
 */
final class ProdukResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sku' => $this->sku,
            'nama' => $this->nama,

            // Bentuk kontrak lama dipertahankan persis: klien yang sudah
            // jalan tidak boleh rusak hanya karena kita merapikan kode.
            'kategori' => $this->whenLoaded(
                'kategori',
                fn () => $this->kategori->kode
            ),
            'kategori_label' => $this->whenLoaded(
                'kategori',
                fn () => $this->kategori->nama
            ),

            'harga' => $this->harga,
            'harga_format' => (new Uang($this->harga))->format(),
            'stok' => $this->stok,
            'tersedia' => $this->stok > 0,

            // Muncul hanya bila kueri memakai withCount('itemTerjual').
            'terjual_count' => $this->whenCounted('itemTerjual'),

            // Muncul hanya untuk supervisor, lewat with('pemasok') (AB-13).
            'pemasok' => PemasokResource::collection(
                $this->whenLoaded('pemasok')
            ),
        ];
    }
}
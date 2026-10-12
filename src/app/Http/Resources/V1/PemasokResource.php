<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Pemasok;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pemasok
 */
final class PemasokResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kota' => $this->kota,
            'harga_beli' => (int) $this->pasokan->harga_beli,
            'utama' => (bool) $this->pasokan->utama,

            // Margin TIDAK dihitung di sini. Ia aturan bisnis (AB-13),
            // dihitung LayananKatalog dan dititipkan sebagai atribut
            // sementara pada model. whenHas() memunculkannya hanya bila
            // titipan itu memang ada, sehingga Resource yang sama dapat
            // dipakai di tempat lain tanpa membocorkan margin.
            'margin' => $this->whenHas('margin'),
            'margin_persen' => $this->whenHas('margin_persen'),
        ];
    }
}
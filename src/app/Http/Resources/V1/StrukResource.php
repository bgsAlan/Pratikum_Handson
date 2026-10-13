<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaksi
 *
 * Salinan langsung dari keArray() pada RepositoriTransaksiEloquent,
 * dipindahkan tanpa mengubah satu pun nama kunci. Perpindahan tempat
 * yang tidak mengubah keluaran adalah refactor; yang mengubah keluaran
 * adalah perubahan kontrak, dan itu pekerjaan yang berbeda.
 */
final class StrukResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nomor' => $this->nomor,
            'waktu' => $this->created_at->toIso8601String(),
            'kasir' => $this->kasir,
            'member' => $this->member,
            'metode_bayar' => $this->metode_bayar->value,
            'metode_label' => $this->metode_bayar->label(),
            'status' => $this->status->value,
            'item' => ItemStrukResource::collection(
                $this->whenLoaded('item')
            ),
            'subtotal' => $this->subtotal,
            'diskon_grosir' => $this->diskon_grosir,
            'diskon_member' => $this->diskon_member,
            'total_diskon' => $this->total_diskon,
            'dpp' => $this->dpp,
            'ppn' => $this->ppn,
            'total' => $this->total,
            'pembulatan' => $this->pembulatan,
            'total_bayar' => $this->total_bayar,
            'dibayar' => $this->dibayar,
            'kembalian' => $this->kembalian,

            // Tetap dikirim sebagai null pada struk yang belum dibatalkan.
            // whenNotNull() TIDAK dipakai di sini: kontrak Modul 5
            // mendaftarkan ketiganya sebagai required.
            'alasan_batal' => $this->alasan_batal,
            'dibatalkan_oleh' => $this->dibatalkan_oleh,
            'dibatalkan_pada' => $this->dibatalkan_pada?->toIso8601String(),
        ];
    }
}
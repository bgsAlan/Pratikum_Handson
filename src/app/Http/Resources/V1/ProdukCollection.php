<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

final class ProdukCollection extends ResourceCollection
{
    /** Setiap anggota koleksi dibentuk oleh Resource ini. */
    public $collects = ProdukResource::class;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['data' => $this->collection];
    }

    /**
     * Merapikan bagian pagination bawaan Laravel agar tidak ada dua kunci "links".
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'links' => $default['links'], // first, last, prev, next
            'meta'  => [
                'halaman'       => $paginated['current_page'],
                'per_halaman'   => $paginated['per_page'],
                'total_halaman' => $paginated['last_page'],
                'total'         => $paginated['total'],
            ],
        ];
    }

    public function withResponse(Request $request, $response): void
    {
        $response->header('Cache-Control', 'private, max-age=30');
    }

    public static function daripada(AbstractPaginator $paginator): self
    {
        return new self($paginator);
    }
}
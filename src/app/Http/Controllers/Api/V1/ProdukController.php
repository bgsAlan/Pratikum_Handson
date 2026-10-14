<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ProdukTidakDitemukan;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ProdukCollection;
use App\Http\Resources\V1\ProdukResource;
use App\Queries\ProdukQuery;
use App\Services\LayananKatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProdukController extends Controller
{
    public function __construct(
        private readonly LayananKatalog $katalog,
        private readonly ProdukQuery $kueri,          // BARU
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Batas atas WAJIB: 1..100, bawaan 15
        $perHalaman = max(1, min($request->integer('per_halaman', 15), 100));

        $produk = $this->kueri->terapkan($request)->paginate($perHalaman);

        // withQueryString(): links.next membawa filter
        $koleksi = new ProdukCollection($produk->withQueryString());

        return $koleksi
            ->additional(['kolom' => $this->kueri->kolomDiminta($request)])
            ->response();
    }

    // show() dan pemasok() TETAP, punya Hana
    public function show(string $sku): JsonResponse
    {
        try {
            $produk = $this->katalog->ambil($sku);

            return response()->json([
                'data' => $produk,
            ], 200);
        } catch (ProdukTidakDitemukan $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }
    }

    public function pemasok(string $sku): JsonResponse
    {
        try {
            $produk = $this->katalog->denganPemasok($sku);

            return response()->json([
                'data' => new ProdukResource($produk),
            ]);
        } catch (ProdukTidakDitemukan $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }
    }
}
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LayananKatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProdukController extends Controller
{
    public function __construct(
        private readonly LayananKatalog $katalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $kategori = $request->string('kategori')->trim()->toString();
        $cari     = $request->string('cari')->trim()->toString();

        $produk = $this->katalog->daftar(
            kategori: $kategori !== '' ? $kategori : null,
            cari: $cari !== '' ? $cari : null,
        );

        return response()->json([
            'data' => $produk,
            'meta' => [
                'jumlah'   => count($produk),
                'kategori' => $kategori !== '' ? $kategori : 'semua',
            ],
        ]);
    }

    public function show(string $sku): JsonResponse
    {
        return response()->json([
            'data' => $this->katalog->ambil($sku),
        ]);
    }
}
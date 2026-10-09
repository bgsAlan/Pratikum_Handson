<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LayananLaporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LaporanController extends Controller
{
    public function __construct(
        private readonly LayananLaporan $laporan,
    ) {}

    public function harian(Request $request): JsonResponse
    {
        $tanggal = $this->tanggal($request);

        return response()->json([
            'data' => $this->laporan->harian($tanggal),
        ]);
    }

    public function terlaris(Request $request): JsonResponse
    {
        $tanggal = $this->tanggal($request);
        $batas =  max(1, min((int) ($request->query('batas') ?? 5), 20));

        return response()->json([
            'data' => $this->laporan->terlaris($tanggal, $batas),
            'meta' => ['tanggal' => $tanggal, 'batas' => $batas],
        ]);
    }

    private function tanggal(Request $request): string
    {
        $tanggal = $request->string('tanggal')->trim()->toString();

        return $tanggal !== '' ? $tanggal : now()->toDateString();
    }
    public function kategori(Request $request): JsonResponse
    {
        $tanggal = $this->tanggal($request);
        $data    = $this->laporan->perKategori($tanggal);

        return response()->json([
            'data' => $data,
            'meta' => [
                'tanggal'          => $tanggal,
                'total_pendapatan' => array_sum(array_column($data, 'pendapatan')),
            ],
        ]);
    }
}

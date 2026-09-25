<?php
declare(strict_types=1);
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
        $batas   = (int) ($request->query('batas') ?? 5);

        return response()->json([
            'data' => $this->laporan->terlaris($tanggal, max(1, min($batas, 20))),
            'meta' => ['tanggal' => $tanggal, 'batas' => $batas],
        ]);
    }

    private function tanggal(Request $request): string
    {
        $tanggal = $request->string('tanggal')->trim()->toString();

        return $tanggal !== '' ? $tanggal : now()->toDateString();
    }
}

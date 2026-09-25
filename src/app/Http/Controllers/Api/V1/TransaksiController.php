<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\MetodeBayar;
use App\Http\Controllers\Controller;
use App\Services\LayananKasir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TransaksiController extends Controller
{
    public function __construct(
        private readonly LayananKasir $kasir,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tanggal = $request->string('tanggal')->trim()->toString() ?: now()->toDateString();
        $transaksi = $this->kasir->transaksiTanggal($tanggal);

        return response()->json([
            'data' => $transaksi,
            'meta' => ['tanggal' => $tanggal, 'jumlah' => count($transaksi)],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item'             => ['required', 'array', 'min:1'],
            'item.*.sku'       => ['required', 'string'],
            'item.*.kuantitas' => ['required', 'integer', 'min:1'],
            'member'           => ['sometimes', 'boolean'],
            'metode_bayar'     => ['required', 'string', 'in:' . implode(',', array_column(MetodeBayar::cases(), 'value'))],
            'dibayar'          => ['required_if:metode_bayar,tunai', 'integer', 'min:0'],
        ]);

        $kasir = $request->attributes->get('kasir');
        $transaksi = $this->kasir->proses($data, $kasir['nama']);

        return response()
            ->json(['data' => $transaksi], 201)
            ->header('Location', route('api.v1.pos.transaksi.show', $transaksi['nomor']));
    }

    public function show(string $nomor): JsonResponse
    {
        return response()->json([
            'data' => $this->kasir->cari($nomor),
        ]);
    }

    public function batal(Request $request, string $nomor): JsonResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:5', 'max:200'],
        ]);

        $kasir = $request->attributes->get('kasir');

        return response()->json([
            'data' => $this->kasir->batalkan($nomor, $data['alasan'], $kasir['nama']),
        ]);
    }
}
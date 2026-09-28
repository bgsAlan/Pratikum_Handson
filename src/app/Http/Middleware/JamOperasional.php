<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak transaksi di luar jam buka toko.
 * Contoh aturan bisnis lintas rute yang tepat diletakkan di middleware:
 * ia tidak menghitung apa pun, hanya menentukan boleh lewat atau tidak.
 */
final class JamOperasional
{
    public function handle(Request $request, Closure $next): Response
    {
        $buka = (string) config('pos.jam.buka');
        $tutup = (string) config('pos.jam.tutup');

        $sekarang = now();
        $jamBuka = now()->setTimeFromTimeString($buka);
        $jamTutup = now()->setTimeFromTimeString($tutup);

        if ($sekarang->lt($jamBuka) || $sekarang->gt($jamTutup)) {
            return response()->json([
                'kesalahan' => 'di_luar_jam_operasional',
                'pesan' => "Transaksi hanya dilayani pukul {$buka} sampai {$tutup}.",
                'waktu_server' => $sekarang->toIso8601String(),
            ], 403);
        }

        return $next($request);
    }
}

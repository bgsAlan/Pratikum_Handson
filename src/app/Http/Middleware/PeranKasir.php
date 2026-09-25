<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware berparameter.
 *
 * Dipakai pada rute sebagai: ->middleware('peran:supervisor')
 * Beberapa peran dipisah koma: ->middleware('peran:supervisor,manajer')
 *
 * Middleware ini WAJIB berjalan setelah KunciApiKasir, karena
 * identitas kasir dibaca dari request attributes.
 */
final class PeranKasir
{
    public function handle(Request $request, Closure $next, string ...$peranDiizinkan): Response
    {
        $kasir = $request->attributes->get('kasir');

        if ($kasir === null || ! in_array($kasir['peran'], $peranDiizinkan, true)) {
            return response()->json([
                'kesalahan' => 'peran_tidak_berwenang',
                'pesan' => 'Aksi ini hanya boleh dilakukan oleh: '.implode(', ', $peranDiizinkan).'.',
                'peran_anda' => $kasir['peran'] ?? null,
            ], 403);
        }

        return $next($request);
    }
}

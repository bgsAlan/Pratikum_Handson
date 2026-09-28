<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware global untuk seluruh rute pada grup api.
 * Memberi setiap request satu identitas unik dan mencatat durasinya.
 */
final class CatatRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // ---------- SEBELUM controller dijalankan ----------
        $mulai = microtime(true);
        $idRequest = (string) Str::uuid();

        // Nilai ini dapat dibaca middleware lain maupun controller.
        $request->attributes->set('id_request', $idRequest);

        $response = $next($request);

        // ---------- SESUDAH controller, sebelum response dikirim ----------
        $durasiMs = round((microtime(true) - $mulai) * 1000, 2);

        logger()->info('POS.HTTP', [
            'id' => $idRequest,
            'method' => $request->method(),
            'uri' => $request->path(),
            'status' => $response->getStatusCode(),
            'durasi_ms' => $durasiMs,
            'kasir' => $request->attributes->get('kasir')['nama'] ?? '-',
            'ip' => $request->ip(),
        ]);

        $response->headers->set('X-Request-Id', $idRequest);
        $response->headers->set('X-Response-Time', $durasiMs.'ms');

        return $response;
    }
}

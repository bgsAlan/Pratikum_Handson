<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang masuk seluruh endpoint POS.
 *
 * Memeriksa header X-API-Key, lalu menyisipkan identitas kasir
 * ke dalam request attributes agar dapat dibaca middleware
 * berikutnya dan controller.
 *
 * CATATAN: ini bukan autentikasi sungguhan, hanya alat bantu
 * Modul 3. Laravel Sanctum menggantikannya pada Modul 9.
 */
final class KunciApiKasir
{
    public function handle(Request $request, Closure $next): Response
    {
        $kunci = (string) $request->header('X-API-Key', '');

        if ($kunci === '') {
            return $this->tolak('kunci_api_hilang', 'Header X-API-Key wajib disertakan.');
        }

        $identitas = $this->cariKasir($kunci);

        if ($identitas === null) {
            return $this->tolak('kunci_api_tidak_dikenal', 'Kunci API tidak dikenal.');
        }

        $request->attributes->set('kasir', $identitas);

        return $next($request);
    }

    /**
     * @return array<string, string>|null
     */
    private function cariKasir(string $kunci): ?array
    {
        foreach ((array) config('pos.kasir') as $kunciSah => $identitas) {
            // hash_equals membandingkan dalam waktu tetap sehingga
            // penyerang tidak dapat menebak kunci dari selisih waktu respons.
            if (hash_equals((string) $kunciSah, $kunci)) {
                return $identitas;
            }
        }

        return null;
    }

    private function tolak(string $kode, string $pesan): Response
    {
        return response()->json([
            'kesalahan' => $kode,
            'pesan' => $pesan,
        ], 401);
    }
}

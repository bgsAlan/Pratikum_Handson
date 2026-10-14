<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SaringKolom
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $isi = $response->getData(true);

        // Daftar kolom sah dititipkan controller lewat additional()
        $kolom = $isi['kolom'] ?? null;
        unset($isi['kolom']);

        if (is_array($kolom) && isset($isi['data'])) {
            $isi['data'] = array_is_list($isi['data'])
                ? array_map(fn (array $b): array => $this->saring($b, $kolom), $isi['data'])
                : $this->saring($isi['data'], $kolom);
        }

        return $response->setData($isi);
    }

    /**
     * @param  array<string, mixed>  $baris
     * @param  array<int, string>    $kolom
     * @return array<string, mixed>
     */
    private function saring(array $baris, array $kolom): array
    {
        return array_intersect_key($baris, array_flip($kolom));
    }
}
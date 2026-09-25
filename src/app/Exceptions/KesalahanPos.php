<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Support\Str;
use RunTimeException;

/**
 * Induk seluruh kesalahan domain POS.
 *
 * Class ini sengaja TIDAK menyebut response() maupun JsonResponse.
 * Ia hanya menyatakan "kesalahan apa" dan "setara status HTTP berapa".
 * Penerjemahan menjadi response JSON dilakukan satu kali saja
 * di bootstrap/app.php, sehingga tidak ada try-catch yang berulang
 * di dalam controller.
 */
abstract class KesalahanPos extends RunTimeException
{
    abstract public function kodeHTTP(): int;

    /** ProdukTidakDitemukan -> "produk_tidak_ditemukan" */
    public function kodeKesalahan(): string
    {
        return Str::snake(class_basename($this));
    }

    /** @return array<string, mixed> konteks tambahan untuk badan response */
    public function konteks(): array
    {
        return [];
    }
}

<?php

use App\Exceptions\KesalahanPos;
use App\Http\Middleware\CatatRequest;
use App\Http\Middleware\JamOperasional;
use App\Http\Middleware\KunciApiKasir;
use App\Http\Middleware\PeranKasir;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware global: berjalan pada SEMUA rute berkas api.php
        $middleware->api(append: [
            CatatRequest::class,
        ]);

        // Middleware beralias: dipasang per rute atau per grup rute
        $middleware->alias([
            'kasir' => KunciApiKasir::class,
            'peran' => PeranKasir::class,
            'jam.buka' => JamOperasional::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Satu tempat penerjemahan kesalahan domain menjadi response JSON.
        // Karena ini ada, tidak satu pun controller memerlukan try-catch.
        $exceptions->render(function (KesalahanPos $e, Request $request) {
            if (! $request->expectsJson()) {
                return null; // biarkan Laravel menanganinya seperti biasa
            }

            return response()->json(array_merge([
                'kesalahan' => $e->kodeKesalahan(),
                'pesan' => $e->getMessage(),
            ], $e->konteks()), $e->kodeHttp());
        });
    })->create();

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
| Dokumentasi API. Didaftarkan di luar produksi saja: kontrak lengkap
| berikut seluruh kode kesalahan adalah peta yang berguna bagi
| penyerang (dibahas pada Modul 10, OWASP API9: Improper Inventory
| Management).
*/
if (! app()->isProduction()) {
    Route::prefix('docs')->name('docs.')->group(function () {

        // Berkas kontrak disajikan apa adanya dari folder docs/.
        Route::get('/openapi.yaml', function () {
            return response()->file(base_path('docs/openapi.yaml'), [
                'Content-Type'  => 'application/yaml; charset=UTF-8',
                'Cache-Control' => 'no-store',    // perubahan langsung terlihat
            ]);
        })->name('spesifikasi');

        // Swagger UI: halaman statis yang membaca kontrak di atas.
        Route::view('/swagger', 'dokumentasi.swagger')->name('swagger');
    });
}
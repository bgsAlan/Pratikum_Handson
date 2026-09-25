<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\LaporanController;
use App\Http\Controllers\Api\V1\ProdukController;
use App\Http\Controllers\Api\V1\TransaksiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Awalan "api" ditambahkan otomatis oleh withRouting() di bootstrap/app.php,
| sehingga /produk di bawah prefix v1/pos menjadi /api/v1/pos/produk.
|
*/

// Rute publik: dipakai monitoring untuk memastikan layanan hidup.
Route::get('/ping', fn () => response()->json([
    'status' => 'ok',
    'toko' => config('pos.nama_toko'),
    'waktu' => now()->toIso8601String(),
]))->name('api.ping');

Route::prefix('v1/pos')
    ->name('api.v1.pos.')
    ->middleware('kasir') // berlaku untuk SELURUH rute di dalam grup
    ->group(function () {

        /* ---------------- Katalog produk ---------------- */
        Route::get('/produk', [ProdukController::class, 'index'])
            ->name('produk.index');

        // where() membatasi bentuk parameter. URI /produk/abc tidak akan
        // pernah sampai ke controller, melainkan langsung 404 dari router.
        Route::get('/produk/{sku}', [ProdukController::class, 'show'])
            ->where('sku', 'SKU-[0-9]{3}')
            ->name('produk.show');

        /* ---------------- Transaksi ---------------- */
        Route::get('/transaksi', [TransaksiController::class, 'index'])
            ->name('transaksi.index');

        Route::post('/transaksi', [TransaksiController::class, 'store'])
            ->middleware('jam.buka') // tambahan khusus rute ini
            ->name('transaksi.store');

        Route::get('/transaksi/{nomor}', [TransaksiController::class, 'show'])
            ->where('nomor', 'POS-[0-9]{8}-[0-9]{4}')
            ->name('transaksi.show');

        Route::post('/transaksi/{nomor}/batal', [TransaksiController::class, 'batal'])
            ->middleware('peran:supervisor') // hanya supervisor
            ->where('nomor', 'POS-[0-9]{8}-[0-9]{4}')
            ->name('transaksi.batal');

        /* ---------------- Laporan ---------------- */
        // Grup bersarang: prefix dan nama saling ditumpuk menjadi
        // /api/v1/pos/laporan/harian dengan nama api.v1.pos.laporan.harian
        Route::prefix('laporan')
            ->name('laporan.')
            ->group(function () {
                Route::get('/harian', [LaporanController::class, 'harian'])
                    ->name('harian');

                Route::get('/terlaris', [LaporanController::class, 'terlaris'])
                    ->name('terlaris');
            });
    });

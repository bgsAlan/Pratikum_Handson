<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\RepositoriProduk;
use App\Contracts\RepositoriTransaksi;
use App\Repositories\RepositoriProdukArray;
use App\Repositories\RepositoriTransaksiBerkas;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
        | Di sinilah antarmuka dihubungkan ke implementasinya.
        | Ketika sebuah class meminta RepositoriProduk pada constructor-nya,
        | service container menyerahkan RepositoriProdukArray.
        |
        | Modul 4 nanti cukup mengganti sisi kanan menjadi
        | RepositoriProdukEloquent::class. Controller dan service
        | tidak perlu disentuh sama sekali.
        */
        $this->app->bind(RepositoriProduk::class, RepositoriProdukArray::class);

        // singleton: satu instance dipakai ulang selama satu request,
        // sehingga berkas JSON tidak dibuka berkali-kali.
        $this->app->singleton(RepositoriTransaksi::class, RepositoriTransaksiBerkas::class);
    }

    public function boot(): void
    {
        //
    }
}

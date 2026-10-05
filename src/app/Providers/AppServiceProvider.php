<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\RepositoriProduk;
use App\Contracts\RepositoriTransaksi;
use App\Repositories\RepositoriProdukEloquent;
use App\Repositories\RepositoriTransaksiEloquent;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            RepositoriProduk::class,
            RepositoriProdukEloquent::class
        );

        $this->app->bind(
            RepositoriTransaksi::class,
            RepositoriTransaksiEloquent::class
        );
    }

    public function boot(): void
    {
        //
    }
}
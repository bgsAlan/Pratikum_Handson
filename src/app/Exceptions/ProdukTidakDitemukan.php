<?php

declare(strict_types=1);
namespace App\Exceptions;
final class ProdukTidakDitemukan extends KesalahanPos{
    public function __construct(private readonly string $sku)
    {
        parent::__construct("Produk dengan SKU {$sku} tidak ditemukan.");
    }
    public function kodeHttp() : int {
        return 404;
    }
    public function konteks() : array {
        return ['sku' => $this->sku];
    }
}
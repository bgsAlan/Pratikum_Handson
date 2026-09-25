<?php

declare(strict_types=1);
namespace App\Exceptions;

final class StokTidakCukup extends KesalahanPos{
    public function __construct(
        private readonly string $sku,
        private readonly int $diminta,
        private readonly int $tersedia,
    ){
        parent::__construct(
            "Stok {$sku} tidak mencukupi. Diminta {$diminta}, tersedia {$tersedia}."
        );

    }
    public function kodeHttp():int {
        return 422;
    }
    public function konteks() : array {
        return [
            'sku' => $this->sku,
            'diminta' => $this->diminta,
            'tersedia' => $this->tersedia,
        ];
    }
}
<?php

declare(strict_types=1);
namespace App\Exceptions;

use app\Domain\Uang;
final class PembayaranKurang extends KesalahanPos{
    public function __construct(private readonly Uang $kurang){
        parent::__construct("Pembayaran Kurang sebesar {$kurang->format()}.");
    }
    public function kodeHttp() :int {
        return 422;
    }
    public function konteks():array{
        return ['kurang' => $this->kurang->rupiah];
    }
}
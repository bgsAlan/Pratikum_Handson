<?php

declare(strict_types=1);

namespace App\Exceptions;

final class TransaksiTidakDitemukan extends KesalahanPos
{
    public function __construct(private readonly string $nomor)
    {
        parent::__construct("Transaksi {$nomor} tidak ditemukan.");
    }

    public function kodeHttp(): int
    {
        return 404;
    }

    public function konteks(): array
    {
        return ['nomor' => $this->nomor];
    }
}

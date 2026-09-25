<?php

declare(strict_types=1);

namespace App\Exceptions;

final class TransaksiSudahDibatalkan extends KesalahanPos
{
    public function __construct(private readonly string $nomor)
    {
        parent::__construct("Transaksi {$nomor} sudah pernaj dibatalkan.");
    }

    /** 409 Conflict: permintaan sah, tetapi bentrok dengan keadaan sekarang. */
    public function kodeHttp(): int
    {
        return 409;
    }

    public function konteks(): array
    {
        return ['nomor' => $this->nomor];
    }
}

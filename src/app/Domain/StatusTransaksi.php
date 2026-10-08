<?php
namespace App\Domain;

enum StatusTransaksi: string
{
    case Selesai = 'selesai';
    case Batal   = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Selesai => 'Selesai',
            self::Batal   => 'Dibatalkan',
        };
    }
}
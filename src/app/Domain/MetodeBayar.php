<?php
declare(strict_types=1);
namespace App\Domain;

enum MetodeBayar: string{
    case Tunai = 'tunai';
    case Qris = 'qris';
    case KartuDebit = 'Kartu Debit';
    
    public function butuhKembalian(): bool {
        return $this === self::Tunai;
    }
    public function label() : string {
        return match ($this) {
            self::Tunai =>'Tunai' ,
            self::Qris => 'Qris' ,
            self::KartuDebit => 'Kartu Debit',
        };
        
    }
}



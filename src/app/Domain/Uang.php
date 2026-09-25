<?php

declare(strict_types=1);

namespace App\Domain;

use InvalidArgumentException;

/**
 * Value object rupiah. Seluruh nilai uang pada sistem POS
 * disimpan sebagai bilangan bulat rupiah, bukan float.
 * Float tidak boleh dipakai untuk uang karena 0.1 + 0.2 !== 0.3.
 */
final readonly class Uang
{
    public function __construct(public int $rupiah)
    {
        if ($rupiah < 0) {
            throw new InvalidArgumentException('Nilai uang tidak boleh negatif.');
        }
    }

    public static function nol(): self
    {
        return new self(0);
    }

    public function tambah(self $lain): self
    {
        return new self($this->rupiah + $lain->rupiah);
    }

    public function kurang(self $lain): self
    {
        return new self($this->rupiah - $lain->rupiah);
    }

    public function kali(int $faktor): self
    {
        return new self($this->rupiah * $faktor);
    }

    /** Persentase dibulatkan ke rupiah terdekat (AB-3, AB-5). */
    public function persen(float $persen): self
    {
        return new self((int) round($this->rupiah * $persen / 100));
    }

    /** Pembulatan struk ke atas, misal ke kelipatan Rp 100 (AB-6). */
    public function bulatkanKeAtas(int $kelipatan): self
    {
        return new self((int) (ceil($this->rupiah / $kelipatan) * $kelipatan));
    }

    public function kurangDari(self $lain): bool
    {
        return $this->rupiah < $lain->rupiah;
    }

    public function format(): string
    {
        return 'Rp ' . number_format($this->rupiah, 0, ',', '.');
    }
}
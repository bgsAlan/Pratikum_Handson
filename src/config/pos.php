<?php

declare(strict_types=1);
/*
|--------------------------------------------------------------------------
| Konfigurasi Point of Sales
|--------------------------------------------------------------------------
| Seluruh angka bisnis diletakkan di sini, bukan ditulis langsung
| (hard-coded) di dalam service. Ketika pemilik toko menaikkan diskon
| member dari 3% ke 5%, tidak ada satu pun baris kode yang berubah.
*/
return [
    'nama_toko' => env('POS_NAMA_TOKO', 'Barokah Mart Solo'),
    // AB-5
    'ppn_persen' => (float) env('POS_PPN_PERSEN', 11),
    // AB-6
    'pembulatan' => (int) env('POS_PEMBULATAN', 100),
    // AB-3
    'member' => [
        'persen' => (float) env('POS_DISKON_MEMBER', 3),
    ],
    // AB-2
    'grosir' => [
        'minimal_kuantitas' => (int) env('POS_GROSIR_MINIMAL', 12),
        'persen' => (float) env('POS_GROSIR_PERSEN', 5),
    ],
    // Dipakai middleware JamOperasional
    'jam' => [
        'buka' => env('POS_JAM_BUKA', '07:00'),
        'tutup' => env('POS_JAM_TUTUP', '22:00'),
    ],
    /*
| Daftar kunci API kasir. INI HANYA UNTUK LATIHAN MODUL 3.
| Autentikasi sesungguhnya memakai Laravel Sanctum pada Modul 9.
| Jangan pernah menaruh kredensial produksi di dalam repositori.
*/
    'kasir' => [
        env('POS_KUNCI_KASIR', 'kasir-dev-001') => [
            'nama' => 'Bambang Saputra',
            'peran' => 'kasir',
        ],
        env('POS_KUNCI_SUPERVISOR', 'spv-dev-001') => [
            'nama' => 'Bagas Prakoso',
            'peran' => 'supervisor',
        ],
    ],
];

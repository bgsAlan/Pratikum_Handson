<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks tunggal pada created_at untuk daftar struk per tanggal.
 *
 * Indeks komposit (status, created_at) dari Modul 4 TIDAK dapat melayani
 * kueri ini, karena kueri tidak menyaring kolom paling kirinya (status).
 *
 * Migration BARU, bukan menyunting migration Modul 4 yang sudah di-merge.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->index('created_at'); // transaksi_created_at_index
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
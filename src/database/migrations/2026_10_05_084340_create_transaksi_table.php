<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 20)->unique();   // POS-20260907-0001
            $table->string('kasir', 100);
            $table->boolean('member')->default(false);
            $table->string('metode_bayar', 20);
            $table->string('status', 12)->default('selesai');

            // Semua uang = bilangan bulat rupiah, sengaja DISIMPAN
            // karena tarif PPN/diskon bisa berubah di masa depan.
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('diskon_grosir')->default(0);
            $table->unsignedInteger('diskon_member')->default(0);
            $table->unsignedInteger('total_diskon')->default(0);
            $table->unsignedInteger('dpp');
            $table->unsignedInteger('ppn');
            $table->unsignedInteger('total');
            $table->unsignedInteger('pembulatan')->default(0);
            $table->unsignedInteger('total_bayar');
            $table->unsignedInteger('dibayar');
            $table->unsignedInteger('kembalian')->default(0);

            // Jejak pembatalan (struk tidak dihapus, hanya diberi status batal)
            $table->string('alasan_batal', 200)->nullable();
            $table->string('dibatalkan_oleh', 100)->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']); // laporan harian
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
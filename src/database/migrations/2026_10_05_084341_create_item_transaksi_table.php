<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_transaksi', function (Blueprint $table) {
            $table->id();

            // Nota dihapus -> barisnya ikut terhapus
            $table->foreignId('transaksi_id')
                ->constrained(table: 'transaksi')
                ->cascadeOnDelete();

            // Produk tidak boleh terhapus selama masih tercatat di struk
            $table->foreignId('produk_id')
                ->constrained(table: 'produk')
                ->restrictOnDelete();

            // Snapshot riwayat (AB-12): harga kemarin tetap harga kemarin
            $table->string('sku', 20);
            $table->string('nama_produk', 150);
            $table->unsignedInteger('harga_satuan');

            $table->unsignedInteger('kuantitas');
            $table->unsignedInteger('diskon')->default(0);
            $table->unsignedInteger('total');
            $table->timestamps();

            $table->index('produk_id'); // laporan terlaris
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_transaksi');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pemasok_produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained(table: 'produk')->cascadeOnDelete();
            $table->foreignId('pemasok_id')->constrained(table: 'pemasok')->cascadeOnDelete();
            $table->unsignedInteger('harga_beli');
            $table->boolean('utama')->default(false);
            $table->timestamps();
            $table->unique(['produk_id', 'pemasok_id']);
        });
        // down(): Schema::dropIfExists('pemasok_produk');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemasok_produk');
    }
};

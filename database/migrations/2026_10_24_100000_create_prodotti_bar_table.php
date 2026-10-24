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
        Schema::create('prodotti_bar', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('codice_barre')->unique()->nullable();
            $table->string('categoria');
            $table->decimal('prezzo_acquisto', 10, 2);
            $table->decimal('prezzo_vendita', 10, 2);
            $table->decimal('giacenza_attuale', 10, 3)->default(0);
            $table->decimal('soglia_minima', 10, 3)->default(0);
            $table->boolean('ha_ricetta')->default(false);
            $table->text('allergeni')->nullable();
            $table->boolean('attivo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodotti_bar');
    }
};

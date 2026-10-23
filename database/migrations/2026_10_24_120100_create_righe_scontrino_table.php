<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Righe di dettaglio di ogni scontrino del POS Bar.
     *
     * Referenzia i prodotti bar venduti (con prezzo applicato al momento
     * della vendita) e gli ingredienti consumati per i prodotti a ricetta.
     */
    public function up(): void
    {
        Schema::create('righe_scontrino', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('scontrino_id')->constrained('scontrini')->cascadeOnDelete();
            $table->foreignId('prodotto_bar_id')->constrained('prodotti_bar')->restrictOnDelete();
            $table->string('nome_prodotto');
            $table->decimal('quantita', 10, 3);
            $table->decimal('prezzo_unitario', 10, 2);
            $table->decimal('subtotale', 10, 2);
            $table->text('ingredienti_consumati')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('righe_scontrino');
    }
};

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
        Schema::create('ricette_bar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodotto_bar_id')->constrained('prodotti_bar')->cascadeOnDelete();
            $table->string('ingrediente_nome');
            $table->decimal('quantita_uso', 10, 3);
            $table->string('unita_misura', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ricette_bar');
    }
};

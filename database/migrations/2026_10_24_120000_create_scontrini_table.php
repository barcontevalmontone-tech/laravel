<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabella degli scontrini emessi dal POS del Bar.
     *
     * Contiene solo il totale dello scontrino: le singole righe di vendita
     * sono tracciate in modo granulare nella tabella righe_scontrino.
     */
    public function up(): void
    {
        Schema::create('scontrini', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedInteger('numero_scontrino')->unique();
            $table->foreignId('utente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('totale', 10, 2)->default(0);
            $table->timestamp('data_vendita');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scontrini');
    }
};

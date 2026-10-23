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
        Schema::create('articoli', function (Blueprint $table) {
            $table->id();
            $table->string('codice')->unique();
            $table->string('barcode')->nullable();
            $table->string('nome');
            $table->string('unita_misura')->default('pz');
            $table->foreignId('categoria_id')->nullable()->constrained('categorie');
            $table->foreignId('fornitore_id')->nullable()->constrained('fornitori');
            $table->decimal('costo', 10, 2)->default(0);
            $table->decimal('prezzo_vendita', 10, 2)->default(0);
            $table->decimal('giacenza', 10, 3)->default(0);
            $table->decimal('giacenza_minima', 10, 3)->default(0);
            $table->decimal('giacenza_massima', 10, 3)->nullable();
            $table->boolean('attivo')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('barcode');
            $table->index('nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articoli');
    }
};

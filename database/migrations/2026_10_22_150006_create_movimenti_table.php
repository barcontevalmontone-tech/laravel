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
        Schema::create('movimenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articolo_id')->constrained('articoli');
            $table->enum('tipo', ['carico', 'scarico', 'rettifica']);
            $table->decimal('quantita', 10, 3);
            $table->decimal('giacenza_prima', 10, 3);
            $table->decimal('giacenza_dopo', 10, 3);
            $table->string('causale')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['articolo_id', 'created_at']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimenti');
    }
};

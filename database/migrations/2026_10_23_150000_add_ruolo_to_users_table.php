<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge il ruolo utente (admin | operatore) per la gestione dei permessi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('ruolo', 20)->default('operatore')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('ruolo');
        });
    }
};

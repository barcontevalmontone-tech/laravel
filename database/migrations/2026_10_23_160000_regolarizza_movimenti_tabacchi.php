<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Regolarizzazione del magazzino tabacchi: la causale diventa un valore
     * controllato (enum CausaleMagazzino) e ogni movimento registra l'utente
     * che lo ha eseguito per la tracciabilità completa.
     */
    public function up(): void
    {
        Schema::table('movimenti', function (Blueprint $table): void {
            $table->unsignedBigInteger('utente_id')->nullable()->after('articolo_id');
        });

        Schema::table('movimenti', function (Blueprint $table): void {
            $table->foreign('utente_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // Mappatura delle causali storiche libere verso quelle regolamentate.
        DB::table('movimenti')->whereNull('causale')->update(['causale' => 'rettifica-inventario']);

        DB::table('movimenti')
            ->whereNotNull('causale')
            ->whereNotIn('causale', [
                'acquisto', 'reso-cliente', 'riaprovvigionamento', 'spostamento-in-entrata',
                'rettifica-inventario', 'vendita', 'reso-fornitore', 'spostamento-in-uscita',
                'campione', 'deteriorato', 'rubato-o-smarrito', 'distruzione', 'sversamento',
                'furto-denunciato', 'amministrazione-controllata',
            ])
            ->update([
                'note' => DB::raw("COALESCE(note || ' | ', 'Regolarizzazione causale: ') || causale"),
                'causale' => 'rettifica-inventario',
            ]);
    }

    public function down(): void
    {
        Schema::table('movimenti', function (Blueprint $table): void {
            $table->dropForeign(['utente_id']);
            $table->dropColumn('utente_id');
        });
    }
};

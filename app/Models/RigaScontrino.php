<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riga di dettaglio di uno scontrino del POS Bar.
 *
 * Gli ingredienti consumati (per i prodotti a ricetta) sono salvati come
 * JSON leggibile: [{ingrediente_nome, quantita, unita_misura}, ...].
 */
class RigaScontrino extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'righe_scontrino';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'scontrino_id',
        'prodotto_bar_id',
        'nome_prodotto',
        'quantita',
        'prezzo_unitario',
        'subtotale',
        'ingredienti_consumati',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'quantita' => 'decimal:3',
        'prezzo_unitario' => 'decimal:2',
        'subtotale' => 'decimal:2',
        'ingredienti_consumati' => 'array',
    ];

    /**
     * Lo scontrino a cui la riga appartiene.
     */
    public function scontrino(): BelongsTo
    {
        return $this->belongsTo(Scontrino::class);
    }

    /**
     * Il prodotto bar venduto.
     */
    public function prodottoBar(): BelongsTo
    {
        return $this->belongsTo(ProdottoBar::class, 'prodotto_bar_id');
    }
}

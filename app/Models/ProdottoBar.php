<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdottoBar extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'prodotti_bar';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'codice_barre',
        'categoria',
        'prezzo_acquisto',
        'prezzo_vendita',
        'giacenza_attuale',
        'soglia_minima',
        'ha_ricetta',
        'allergeni',
        'attivo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prezzo_acquisto' => 'decimal:2',
        'prezzo_vendita' => 'decimal:2',
        'giacenza_attuale' => 'decimal:3',
        'soglia_minima' => 'decimal:3',
        'ha_ricetta' => 'boolean',
        'attivo' => 'boolean',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'giacenza_attuale' => 0,
        'soglia_minima' => 0,
        'ha_ricetta' => false,
        'attivo' => true,
    ];

    /**
     * The recipe ingredients of this bar product.
     */
    public function ricette(): HasMany
    {
        return $this->hasMany(RicettaBar::class)->orderBy('ingrediente_nome');
    }

    /**
     * Scope the query to only active products.
     */
    public function scopeAttivi(Builder $query): Builder
    {
        return $query->where('attivo', true);
    }

    /**
     * Scope the query to products at or below their minimum threshold.
     */
    public function scopeSottoSoglia(Builder $query): Builder
    {
        return $query->whereColumn('giacenza_attuale', '<=', 'soglia_minima');
    }
}

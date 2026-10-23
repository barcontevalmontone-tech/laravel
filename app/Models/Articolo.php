<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Articolo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'articoli';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'codice',
        'barcode',
        'nome',
        'unita_misura',
        'categoria_id',
        'fornitore_id',
        'costo',
        'prezzo_vendita',
        'giacenza',
        'giacenza_minima',
        'giacenza_massima',
        'attivo',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'costo' => 'decimal:2',
        'prezzo_vendita' => 'decimal:2',
        'giacenza' => 'decimal:3',
        'giacenza_minima' => 'decimal:3',
        'giacenza_massima' => 'decimal:3',
        'attivo' => 'boolean',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'unita_misura' => 'pz',
        'attivo' => true,
    ];

    /**
     * The movements of this article, newest first.
     */
    public function movimenti(): HasMany
    {
        return $this->hasMany(Movimento::class)->latest();
    }

    /**
     * The category this article belongs to.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * The vendor that supplies this article.
     */
    public function fornitore(): BelongsTo
    {
        return $this->belongsTo(Fornitore::class);
    }

    /**
     * Scope the query to only active articles.
     */
    public function scopeAttivi(Builder $query): Builder
    {
        return $query->where('attivo', true);
    }

    /**
     * Determine whether the stock is below the minimum level.
     */
    public function sottoSogliaMinima(): bool
    {
        return $this->giacenza < $this->giacenza_minima;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Scontrino emesso dal POS del Bar.
 */
class Scontrino extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scontrini';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'numero_scontrino',
        'utente_id',
        'totale',
        'data_vendita',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'numero_scontrino' => 'integer',
        'totale' => 'decimal:2',
        'data_vendita' => 'datetime',
    ];

    /**
     * Le righe di dettaglio dello scontrino.
     */
    public function righe(): HasMany
    {
        return $this->hasMany(RigaScontrino::class);
    }

    /**
     * L'operatore che ha battuto lo scontrino.
     */
    public function utente(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

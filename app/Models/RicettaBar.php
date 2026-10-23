<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RicettaBar extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ricette_bar';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prodotto_bar_id',
        'ingrediente_nome',
        'quantita_uso',
        'unita_misura',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantita_uso' => 'decimal:3',
    ];

    /**
     * The bar product this recipe line belongs to.
     */
    public function prodottoBar(): BelongsTo
    {
        return $this->belongsTo(ProdottoBar::class, 'prodotto_bar_id');
    }
}

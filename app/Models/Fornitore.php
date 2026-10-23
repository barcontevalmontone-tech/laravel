<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fornitore extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fornitori';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ragione_sociale',
        'partita_iva',
        'telefono',
        'email',
        'note',
    ];

    /**
     * The articles supplied by this vendor.
     */
    public function articoli(): HasMany
    {
        return $this->hasMany(Articolo::class);
    }
}

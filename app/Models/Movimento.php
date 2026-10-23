<?php

namespace App\Models;

use App\Enums\TipoMovimento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimento extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'movimenti';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'articolo_id',
        'tipo',
        'quantita',
        'giacenza_prima',
        'giacenza_dopo',
        'causale',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tipo' => TipoMovimento::class,
        'quantita' => 'decimal:3',
        'giacenza_prima' => 'decimal:3',
        'giacenza_dopo' => 'decimal:3',
    ];

    /**
     * The article this movement refers to.
     */
    public function articolo(): BelongsTo
    {
        return $this->belongsTo(Articolo::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categorie';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nome',
        'parent_id',
    ];

    /**
     * The parent category, if any.
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    /**
     * The child categories.
     */
    public function figlie(): HasMany
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    /**
     * The articles belonging to this category.
     */
    public function articoli(): HasMany
    {
        return $this->hasMany(Articolo::class);
    }
}

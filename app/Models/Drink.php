<?php

namespace App\Models;

use Database\Factories\DrinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'brand', 'description', 'alcoholic', 'cl', 'type', 'sold_to_minor', 'package_type', 'barcode', 'sugar_free', 'gluten_free'])]
class Drink extends Model
{
    /** @use HasFactory<DrinkFactory> */
    use HasFactory;

    public function items(): HasMany
    {
        return $this->hasMany(DrinkItem::class);
    }
}

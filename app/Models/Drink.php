<?php

namespace App\Models;

use App\Enum\DrinkType;
use Database\Factories\DrinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'brand', 'description', 'alcoholic', 'type', 'sold_to_minor', 'sugar_free', 'gluten_free'])]
class Drink extends Model
{
    /** @use HasFactory<DrinkFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => DrinkType::class,
        ];
    }

    /**
     * @return HasMany<DrinkItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DrinkItem::class);
    }
}

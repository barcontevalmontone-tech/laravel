<?php

namespace App\Models;

use App\Enum\DrinkPackageType;
use Cknow\Money\Casts\MoneyIntegerCast;
use Database\Factories\DrinkItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cl', 'package_type', 'barcode'])]

class DrinkItem extends Model
{
    /** @use HasFactory<DrinkItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'package_type' => DrinkPackageType::class,
            'price' => MoneyIntegerCast::class,
        ];
    }

    public function drink(): BelongsTo
    {
        return $this->belongsTo(Drink::class);
    }
}

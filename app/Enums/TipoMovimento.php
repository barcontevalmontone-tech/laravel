<?php

namespace App\Enums;

enum TipoMovimento: string
{
    case Carico = 'carico';
    case Scarico = 'scarico';
    case Rettifica = 'rettifica';

    /**
     * The sign applied to the quantity for this movement type.
     */
    public function segno(): int
    {
        return match ($this) {
            self::Carico => 1,
            self::Scarico => -1,
            self::Rettifica => 0,
        };
    }

    /**
     * A human readable label in Italian.
     */
    public function label(): string
    {
        return match ($this) {
            self::Carico => 'Carico',
            self::Scarico => 'Scarico',
            self::Rettifica => 'Rettifica',
        };
    }
}

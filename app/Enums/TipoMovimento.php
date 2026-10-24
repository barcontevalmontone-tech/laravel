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
    // SE L'ENUM E' DI TIPO STRINGA NON DEVE MAI RITORNARE NUMERI
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
    // LE TRADUZIONI VANNO MESSE SEMPRE NEI FILE DI LANG
    public function label(): string
    {
        return match ($this) {
            self::Carico => 'Carico',
            self::Scarico => 'Scarico',
            self::Rettifica => 'Rettifica',
        };
    }
}

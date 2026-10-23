<?php

namespace App\Support;

use App\Models\Articolo;

/**
 * Dati aggregati di un articolo che necessita riordino.
 */
final readonly class Riordino
{
    public function __construct(
        public Articolo $articolo,
        public float $giacenzaAttuale,
        public float $sogliaMinima,
        public float $quantitaDaOrdinare,
    ) {}

    /**
     * L'articolo è completamente esaurito (giacenza a zero o sotto).
     */
    public function esaurito(): bool
    {
        return $this->giacenzaAttuale <= 0;
    }
}

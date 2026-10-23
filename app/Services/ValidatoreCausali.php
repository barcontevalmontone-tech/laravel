<?php

namespace App\Services;

use App\Enums\CausaleMagazzino;
use App\Enums\TipoMovimento;
use App\Exceptions\CausaleNonConsentita;

class ValidatoreCausali
{
    /**
     * Validate a free-text causale against the regulated warehouse causali.
     *
     * @param  string|null  $causale  The raw causale submitted by the user or API client.
     * @param  bool  $admin  Whether the acting user has administrator privileges.
     *
     * @throws CausaleNonConsentita when the causale is missing, unknown,
     *                              not allowed for the movement type, or
     *                              reserved to administrators.
     */
    public function convalida(?string $causale, TipoMovimento $tipo, bool $admin): CausaleMagazzino
    {
        if ($causale === null || trim($causale) === '') {
            throw new CausaleNonConsentita('La causale è obbligatoria: scegli una causale di magazzino valida.');
        }

        $regola = CausaleMagazzino::tryFrom(trim($causale));

        if ($regola === null) {
            throw new CausaleNonConsentita(
                "La causale «{$causale}» non è prevista dal regolamento di magazzino."
            );
        }

        if (! in_array($tipo, $regola->tipiConsentiti(), true)) {
            throw new CausaleNonConsentita(
                "La causale «{$regola->label()}» non è consentita per un movimento di tipo {$tipo->label()}."
            );
        }

        if (! $admin && $regola->riservataAdmin()) {
            throw new CausaleNonConsentita(
                "La causale «{$regola->label()}» è riservata agli amministratori."
            );
        }

        return $regola;
    }
}

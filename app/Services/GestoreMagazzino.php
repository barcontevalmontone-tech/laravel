<?php

namespace App\Services;

use App\Enums\TipoMovimento;
use App\Exceptions\CausaleNonConsentita;
use App\Exceptions\GiacenzaInsufficiente;
use App\Models\Articolo;
use App\Models\Movimento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GestoreMagazzino
{
    public function __construct(private readonly ValidatoreCausali $validatoreCausali = new ValidatoreCausali) {}

    /**
     * Register a stock movement and atomically update the article's stock.
     *
     * For Carico and Scarico, $quantita is added or subtracted from the current stock.
     * For Rettifica, $quantita becomes the new absolute stock value.
     *
     * The causale is validated against the regulated warehouse causali (tobacco
     * license rules): it is mandatory, must match the movement type, and some
     * causalae are reserved to administrators. The acting user is recorded.
     *
     * @throws GiacenzaInsufficiente when a scarico would bring the stock below zero
     * @throws CausaleNonConsentita when the causale violates the warehouse regulation
     */
    public function registra(Articolo $articolo, TipoMovimento $tipo, float $quantita, string $causale, ?string $note = null): Movimento
    {
        if ($quantita < 0) {
            throw new InvalidArgumentException('La quantita di un movimento non puo essere negativa.');
        }

        $utente = Auth::user();

        $causaleValidata = $this->validatoreCausali->convalida(
            causale: $causale,
            tipo: $tipo,
            admin: $utente?->isAdmin() ?? false,
        );

        return DB::transaction(function () use ($articolo, $tipo, $quantita, $causaleValidata, $note, $utente): Movimento {
            $locked = Articolo::query()->lockForUpdate()->findOrFail($articolo->id);

            $giacenzaPrima = (float) $locked->giacenza;

            $giacenzaDopo = match ($tipo) {
                TipoMovimento::Carico => $giacenzaPrima + $quantita,
                TipoMovimento::Scarico => $giacenzaPrima - $quantita,
                TipoMovimento::Rettifica => $quantita,
            };

            if ($giacenzaDopo < 0) {
                throw new GiacenzaInsufficiente(
                    "Giacenza insufficiente per l'articolo {$locked->codice}: disponibile {$giacenzaPrima}, richiesto {$quantita}."
                );
            }

            $locked->forceFill(['giacenza' => $giacenzaDopo])->save();

            return Movimento::create([
                'articolo_id' => $locked->id,
                'utente_id' => $utente?->id,
                'tipo' => $tipo,
                'quantita' => $quantita,
                'giacenza_prima' => $giacenzaPrima,
                'giacenza_dopo' => $giacenzaDopo,
                'causale' => $causaleValidata->value,
                'note' => $note,
            ]);
        });
    }

    /**
     * Register a carico movement for the given article.
     */
    public function carico(Articolo $articolo, float $quantita, string $causale, ?string $note = null): Movimento
    {
        return $this->registra($articolo, TipoMovimento::Carico, $quantita, $causale, $note);
    }

    /**
     * Register a scarico movement for the given article.
     */
    public function scarico(Articolo $articolo, float $quantita, string $causale, ?string $note = null): Movimento
    {
        return $this->registra($articolo, TipoMovimento::Scarico, $quantita, $causale, $note);
    }

    /**
     * Set an absolute stock value for the given article through a rettifica.
     */
    public function rettifica(Articolo $articolo, float $nuovaGiacenza, string $causale, ?string $note = null): Movimento
    {
        return $this->registra($articolo, TipoMovimento::Rettifica, $nuovaGiacenza, $causale, $note);
    }
}

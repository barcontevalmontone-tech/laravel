<?php

namespace App\Services;

use App\Models\Articolo;
use App\Support\Riordino;
use Illuminate\Support\Collection;

/**
 * Calcola la lista degli articoli sotto soglia di riordino.
 *
 * Regole:
 * - solo articoli attivi con giacenza_minima > 0;
 * - un articolo è in riordino quando giacenza <= giacenza_minima;
 * - quantità da ordinare = (giacenza_massima - giacenza attuale),
 *   con fallback a (giacenza_minima * 2 - giacenza attuale) se la
 *   giacenza massima non è impostata o non è superiore alla minima;
 * - ordinamento: prima gli esauriti, poi per scorta crescente.
 */
class ReportRiordini
{
    /**
     * @return Collection<int, Riordino>
     */
    public function articoliDaOrdinare(): Collection
    {
        return Articolo::query()
            ->with(['categoria', 'fornitore'])
            ->where('attivo', true)
            ->where('giacenza_minima', '>', 0)
            ->whereColumn('giacenza', '<=', 'giacenza_minima')
            ->orderByRaw('CASE WHEN giacenza <= 0 THEN 0 ELSE 1 END')
            ->orderBy('giacenza')
            ->get()
            ->map(fn (Articolo $articolo): Riordino => new Riordino(
                articolo: $articolo,
                giacenzaAttuale: (float) $articolo->giacenza,
                sogliaMinima: (float) $articolo->giacenza_minima,
                quantitaDaOrdinare: $this->quantitaDaOrdinare($articolo),
            ));
    }

    /**
     * Riepilogo del valore (al costo) della merce da riordinare.
     *
     * @return array{conteggio: int, valore_costo: float}
     */
    public function riepilogo(): array
    {
        $lista = $this->articoliDaOrdinare();

        $valoreCosto = $lista->sum(
            fn (Riordino $r): float => round($r->quantitaDaOrdinare * (float) $r->articolo->costo, 2)
        );

        return [
            'conteggio' => $lista->count(),
            'valore_costo' => round($valoreCosto, 2),
        ];
    }

    private function quantitaDaOrdinare(Articolo $articolo): float
    {
        $giacenza = (float) $articolo->giacenza;
        $minima = (float) $articolo->giacenza_minima;
        $massima = (float) ($articolo->giacenza_massima ?? 0);

        $target = $massima > $minima ? $massima : $minima * 2;

        return max(0, round($target - $giacenza, 3));
    }
}

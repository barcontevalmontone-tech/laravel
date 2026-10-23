<?php

namespace App\Http\Controllers;

use App\Http\Requests\PosSearchRequest;
use App\Models\ProdottoBar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base logica del POS del Bar.
 *
 * Gestione della ricerca prodotti e dello scontrino temporaneo (carrello):
 * nessun aggiornamento di giacenza avviene qui — il carrello è volatile e
 * viene ricomposto a ogni richiesta con i dati correnti dal database.
 */
class PosController extends Controller
{
    /**
     * Numero massimo di risultati "simili" restituiti dalla ricerca.
     */
    private const MAX_RISULTATI_SIMILI = 10;

    /**
     * Ricerca prodotti attivi per codice_barre esatto o nome simile.
     */
    public function search(PosSearchRequest $request): JsonResponse
    {
        $query = $request->queryRicerca();

        // 1) Match esatto sul codice a barre (confronto normalizzato).
        $esatto = ProdottoBar::attivi()
            ->whereRaw('UPPER(codice_barre) = ?', [mb_strtoupper($query)])
            ->with('ricette')
            ->first();

        if ($esatto !== null) {
            return response()->json([
                'tipo' => 'match_esatto',
                'query' => $query,
                'prodotto' => $this->trasformaProdotto($esatto, quantita: 1),
            ]);
        }

        // 2) Nessuna corrispondenza esatta: lista di nomi simili (LIKE).
        $simili = ProdottoBar::attivi()
            ->where('nome', 'like', "%{$query}%")
            ->orderBy('nome')
            ->limit(self::MAX_RISULTATI_SIMILI)
            ->with('ricette')
            ->get();

        return response()->json([
            'tipo' => 'lista_suggerimenti',
            'query' => $query,
            'risultati' => $simili->map(fn (ProdottoBar $p) => $this->trasformaProdotto($p, quantita: 1))->values(),
        ]);
    }

    /**
     * Crea/aggiorna lo scontrino temporaneo (carrello) e ne restituisce il riepilogo.
     *
     * Payload atteso:
     * {
     *   "articoli": [
     *     { "prodotto_bar_id": 1, "quantita": 2 },
     *     ...
     *   ]
     * }
     */
    public function addToCart(Request $request): JsonResponse
    {
        $validati = $request->validate([
            'articoli' => ['required', 'array', 'min:1'],
            'articoli.*.prodotto_bar_id' => ['required', 'integer'],
            'articoli.*.quantita' => ['required', 'numeric', 'gt:0'],
        ], [], [
            'articoli.required' => 'L\'array articoli del carrello è obbligatorio.',
            'articoli.*.quantita.gt' => 'La quantità deve essere maggiore di zero.',
        ]);

        // Carico solo prodotti attivi (scope Eloquent) con le relative ricette.
        $prodotti = ProdottoBar::attivi()
            ->with('ricette')
            ->findMany(collect($validati['articoli'])->pluck('prodotto_bar_id')->unique());

        $righeCarrello = [];
        $errori = [];
        $totaleParziale = 0.0;

        foreach ($validati['articoli'] as $riga) {
            $prodotto = $prodotti->firstWhere('id', $riga['prodotto_bar_id']);

            if ($prodotto === null) {
                $errori[] = [
                    'prodotto_bar_id' => $riga['prodotto_bar_id'],
                    'motivo' => 'Prodotto non trovato o non attivo.',
                ];

                continue;
            }

            $quantita = (float) $riga['quantita'];
            $giacenza = (float) $prodotto->giacenza_attuale;

            if ($quantita > $giacenza) {
                $errori[] = [
                    'prodotto_bar_id' => $prodotto->id,
                    'nome' => $prodotto->nome,
                    'motivo' => 'Giacenza insufficiente.',
                    'richiesta' => $quantita,
                    'disponibile' => $giacenza,
                ];

                continue;
            }

            $subtotale = round((float) $prodotto->prezzo_vendita * $quantita, 2);
            $totaleParziale = round($totaleParziale + $subtotale, 2);

            $righeCarrello[] = $this->trasformaProdotto($prodotto, quantita: $quantita, subtotale: $subtotale);
        }

        // Se anche una sola riga è invalida, l'intero scontrino viene rifiutato.
        if ($errori !== []) {
            return response()->json([
                'message' => 'Carrello non valido: una o più righe superano la giacenza o referenziano prodotti inesistenti.',
                'errori' => $errori,
            ], 422);
        }

        return response()->json([
            'carrello' => [
                'righe' => $righeCarrello,
                'numero_articoli' => count($righeCarrello),
                'totale_pezzi' => round(array_sum(array_column($righeCarrello, 'quantita')), 3),
                'totale_parziale' => number_format($totaleParziale, 2, '.', ''),
            ],
        ]);
    }

    /**
     * Normalizza un prodotto per la risposta JSON, includendo la ricetta
     * scalata sulla quantità venduta quando presente.
     */
    private function trasformaProdotto(ProdottoBar $prodotto, float $quantita, ?float $subtotale = null): array
    {
        $haRicetta = $prodotto->ha_ricetta && $prodotto->relationLoaded('ricette') && $prodotto->ricette->isNotEmpty();

        return [
            'id' => $prodotto->id,
            'nome' => $prodotto->nome,
            'codice_barre' => $prodotto->codice_barre,
            'categoria' => $prodotto->categoria,
            'prezzo_vendita' => (float) $prodotto->prezzo_vendita,
            'giacenza_attuale' => (float) $prodotto->giacenza_attuale,
            'quantita' => $quantita,
            'subtotale' => $subtotale ?? round((float) $prodotto->prezzo_vendita * $quantita, 2),
            'allergeni' => $prodotto->allergeni,
            'ha_ricetta' => $haRicetta,
            // Ingredienti necessari per questa vendita (quantità già moltiplicata).
            'ingredienti_necessari' => $haRicetta
                ? $prodotto->ricette->map(fn ($r) => [
                    'ingrediente_nome' => $r->ingrediente_nome,
                    'quantita_per_vendita' => round((float) $r->quantita_uso * $quantita, 3),
                    'unita_misura' => $r->unita_misura,
                ])->values()
                : [],
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmSaleRequest;
use App\Http\Requests\PosSearchRequest;
use App\Models\ProdottoBar;
use App\Models\RigaScontrino;
use App\Models\Scontrino;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Base logica del POS del Bar.
 *
 * Gestione della ricerca prodotti, dello scontrino temporaneo (carrello)
 * e della conferma definitiva della vendita con scarico delle giacenze.
 */
class PosController extends Controller
{
    /**
     * Numero massimo di risultati "simili" restituiti dalla ricerca.
     */
    private const MAX_RISULTATI_SIMILI = 10;

    /**
     * Unità di misura ammesse per gli ingredienti a ricetta.
     * Le unità discrete (pz, pezzi...) indicano che l'ingrediente è un
     * prodotto finito venduto tal quale in prodotti_bar.
     */
    private const UNITA_DISCRETE = ['pz', 'pezzo', 'pezzi'];

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
     * Normalizza un nome per il confronto case-insensitive tra ingredienti
     * di ricetta e prodotti bar. Usa la case-folding Unicode (non
     * mb_strtoupper) perché SQLite UPPER() non piega le accentate maiuscole
     * (À -> A), mentre quelle minuscole sì (à -> A).
     */
    private function normalizzaNome(string $nome): string
    {
        return mb_convert_case(trim($nome), MB_CASE_FOLD, 'UTF-8');
    }

    /**
     * Conferma definitiva della vendita: scarica le giacenze e chiude lo scontrino.
     *
     * Regole:
     * - prodotto SENZA ricetta → decrementa giacenza_attuale in prodotti_bar;
     * - prodotto CON ricetta   → decrementa gli ingredienti: se l'ingrediente
     *   corrisponde a un prodotto_bar (match per nome) lo scarica come riga di
     *   magazzino, altrimenti ne tiene traccia solo nello scontrino (sfuso);
     * - atomicità garantita da transazione DB con lock pessimistico (FOR UPDATE)
     *   su tutte le righe coinvolte, lette in ordine di id per evitare deadlock.
     *
     * Payload atteso: { "articoli": [{ "prodotto_bar_id": 1, "quantita": 2 }, ...] }
     */
    public function confirmSale(ConfirmSaleRequest $request): JsonResponse
    {
        $articoli = $request->articoliNormalizzati();

        // 1) Lock pessimistico dei prodotti venduti (ordinati per id).
        $prodottiVenduti = ProdottoBar::query()
            ->attivi()
            ->whereIn('id', array_keys($articoli))
            ->orderBy('id')
            ->lockForUpdate()
            ->with('ricette')
            ->get();

        $errori = [];
        foreach ($articoli as $id => $quantita) {
            $prodotto = $prodottiVenduti->firstWhere('id', $id);

            if ($prodotto === null) {
                $errori[] = [
                    'prodotto_bar_id' => $id,
                    'motivo' => 'Prodotto non trovato o non attivo.',
                ];

                continue;
            }

            // I prodotti a ricetta si preparano al momento: la disponibilità
            // è data dagli ingredienti, non dal loro conteggio diretto.
            if (! $prodotto->ha_ricetta && (float) $quantita > (float) $prodotto->giacenza_attuale) {
                $errori[] = [
                    'prodotto_bar_id' => $prodotto->id,
                    'nome' => $prodotto->nome,
                    'motivo' => 'Giacenza insufficiente.',
                    'richiesta' => $quantita,
                    'disponibile' => (float) $prodotto->giacenza_attuale,
                ];
            }
        }

        // 2) Identifica gli ingredienti mappati su prodotti_bar e li locka.
        $nomiIngredienti = $prodottiVenduti
            ->filter(fn (ProdottoBar $p) => $p->ha_ricetta && $p->ricette->isNotEmpty())
            ->flatMap(fn (ProdottoBar $p) => $p->ricette->pluck('ingrediente_nome'))
            ->unique()
            ->values();

        $ingredientiProdotto = $nomiIngredienti->isEmpty()
            ? collect()
            : ProdottoBar::attivi()
                ->whereIn(DB::raw('LOWER(nome)'), $nomiIngredienti->map(fn ($n) => mb_convert_case($n, MB_CASE_FOLD, 'UTF-8'))->all())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (ProdottoBar $p) => $this->normalizzaNome($p->nome));

        // Fabbisogno totale di ingrediente (somma su tutte le righe di scontrino).
        $fabbisogno = [];
        foreach ($prodottiVenduti as $prodotto) {
            if (! $prodotto->ha_ricetta || $prodotto->ricette->isEmpty()) {
                continue;
            }

            foreach ($prodotto->ricette as $riga) {
                $chiave = $this->normalizzaNome($riga->ingrediente_nome);
                $consumo = round((float) $riga->quantita_uso * $articoli[$prodotto->id], 3);
                $fabbisogno[$chiave]['nome'] = $riga->ingrediente_nome;
                $fabbisogno[$chiave]['unita'] = $riga->unita_misura;
                $fabbisogno[$chiave]['totale'] = round(($fabbisogno[$chiave]['totale'] ?? 0) + $consumo, 3);
            }
        }

        foreach ($fabbisogno as $chiave => $dato) {
            $corrispondente = $ingredientiProdotto->get($chiave);

            if ($corrispondente !== null && $dato['totale'] > (float) $corrispondente->giacenza_attuale) {
                $errori[] = [
                    'ingrediente' => $dato['nome'],
                    'motivo' => 'Giacenza ingrediente insufficiente.',
                    'richiesto' => $dato['totale'].' '.$dato['unita'],
                    'disponibile' => (float) $corrispondente->giacenza_attuale.' '.$dato['unita'],
                ];
            }
        }

        if ($errori !== []) {
            return response()->json([
                'message' => 'Vendita non confermata: una o più righe superano la giacenza disponibile.',
                'errori' => $errori,
            ], 422);
        }

        // 3) Emissione dello scontrino e scarico giacenze in transazione atomica.
        $scontrino = DB::transaction(function () use ($prodottiVenduti, $articoli, $ingredientiProdotto): Scontrino {
            $numero = ((int) Scontrino::query()->max('numero_scontrino')) + 1;

            $scontrino = Scontrino::create([
                'numero_scontrino' => $numero,
                'utente_id' => auth()->id(),
                'totale' => 0,
                'data_vendita' => now(),
            ]);

            $totale = 0.0;

            foreach ($prodottiVenduti as $prodotto) {
                $quantita = $articoli[$prodotto->id];
                $subtotale = round((float) $prodotto->prezzo_vendita * $quantita, 2);
                $totale = round($totale + $subtotale, 2);

                $ingredientiConsumati = [];

                if ($prodotto->ha_ricetta && $prodotto->ricette->isNotEmpty()) {
                    // Scarico gli ingredienti che sono prodotti_bar conosciuti;
                    // gli altri (sfusi) restano tracciati solo sullo scontrino.
                    foreach ($prodotto->ricette as $riga) {
                        $consumo = round((float) $riga->quantita_uso * $quantita, 3);

                        $ingrediente = $ingredientiProdotto->get($this->normalizzaNome($riga->ingrediente_nome));

                        if ($ingrediente !== null) {
                            ProdottoBar::whereKey($ingrediente->id)
                                ->decrement('giacenza_attuale', $consumo);
                        }

                        $ingredientiConsumati[] = [
                            'ingrediente_nome' => $riga->ingrediente_nome,
                            'quantita' => $consumo,
                            'unita_misura' => $riga->unita_misura,
                            'scaricato_da_magazzino' => $ingrediente !== null,
                        ];
                    }
                } else {
                    // Riga semplice: decremento diretto del prodotto venduto.
                    ProdottoBar::whereKey($prodotto->id)
                        ->decrement('giacenza_attuale', $quantita);
                }

                $scontrino->righe()->create([
                    'prodotto_bar_id' => $prodotto->id,
                    'nome_prodotto' => $prodotto->nome,
                    'quantita' => $quantita,
                    'prezzo_unitario' => $prodotto->prezzo_vendita,
                    'subtotale' => $subtotale,
                    'ingredienti_consumati' => $ingredientiConsumati !== [] ? $ingredientiConsumati : null,
                ]);
            }

            $scontrino->update(['totale' => $totale]);

            return $scontrino;
        });

        return response()->json([
            'messaggio' => 'Vendita confermata.',
            'scontrino' => [
                'id' => $scontrino->id,
                'numero_scontrino' => $scontrino->numero_scontrino,
                'data_vendita' => $scontrino->data_vendita->toIso8601String(),
                'totale' => number_format((float) $scontrino->totale, 2, '.', ''),
                'righe' => $scontrino->load('righe')->righe->map(fn (RigaScontrino $r) => [
                    'nome_prodotto' => $r->nome_prodotto,
                    'quantita' => (float) $r->quantita,
                    'prezzo_unitario' => (float) $r->prezzo_unitario,
                    'subtotale' => (float) $r->subtotale,
                    'ingredienti_consumati' => $r->ingredienti_consumati ?? [],
                ])->values(),
            ],
        ], 201);
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

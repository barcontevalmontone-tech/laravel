<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportRiordini;
use App\Support\Riordino;
use Illuminate\Http\JsonResponse;

class RiordinoController extends Controller
{
    public function __construct(private readonly ReportRiordini $report) {}

    /**
     * List the articles that need to be reordered, with a summary.
     */
    public function index(): JsonResponse
    {
        $lista = $this->report->articoliDaOrdinare()->map(fn (Riordino $riordino): array => [
            'articolo_id' => $riordino->articolo->id,
            'codice' => $riordino->articolo->codice,
            'nome' => $riordino->articolo->nome,
            'fornitore' => $riordino->articolo->fornitore?->ragione_sociale,
            'giacenza_attuale' => $riordino->giacenzaAttuale,
            'soglia_minima' => $riordino->sogliaMinima,
            'quantita_da_ordinare' => $riordino->quantitaDaOrdinare,
            'esaurito' => $riordino->esaurito(),
        ])->values();

        return response()->json([
            'riepilogo' => $this->report->riepilogo(),
            'articoli' => $lista,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoMovimento;
use App\Exceptions\CausaleNonConsentita;
use App\Exceptions\GiacenzaInsufficiente;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovimentoRequest;
use App\Http\Resources\MovimentoResource;
use App\Models\Articolo;
use App\Services\GestoreMagazzino;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MovimentoController extends Controller
{
    public function __construct(private readonly GestoreMagazzino $gestore) {}

    /**
     * Register a stock movement for the given article.
     */
    public function store(StoreMovimentoRequest $request, Articolo $articolo): JsonResponse
    {
        try {
            $movimento = $this->gestore->registra(
                articolo: $articolo,
                tipo: $request->enum('tipo', TipoMovimento::class),
                quantita: (float) $request->input('quantita'),
                causale: $request->input('causale'),
                note: $request->input('note'),
            );
        } catch (GiacenzaInsufficiente | CausaleNonConsentita $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new MovimentoResource($movimento->load(['articolo', 'utente'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * List the last movements of the given article.
     */
    public function index(Articolo $articolo): AnonymousResourceCollection
    {
        $movimenti = $articolo->movimenti()->with(['articolo', 'utente'])->paginate(50);

        return MovimentoResource::collection($movimenti);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdottoBarRequest;
use App\Models\ProdottoBar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdottoBarController extends Controller
{
    /**
     * Elenca i prodotti bar attivi, paginati, con le relative ricette.
     */
    public function index(Request $request): JsonResponse
    {
        $prodotti = ProdottoBar::query()
            ->attivi()
            ->with('ricette')
            ->when(
                $request->filled('categoria'),
                fn ($q) => $q->where('categoria', $request->string('categoria'))
            )
            ->when(
                $request->boolean('sotto_soglia'),
                fn ($q) => $q->sottoSoglia()
            )
            ->orderBy('nome')
            ->paginate($request->integer('per_page', 15));

        return response()->json($prodotti);
    }

    /**
     * Crea un prodotto bar e, se a ricetta, ne popola gli ingredienti.
     */
    public function store(StoreProdottoBarRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['ingredienti']);

        $prodotto = DB::transaction(function () use ($data, $request): ProdottoBar {
            $prodotto = ProdottoBar::create($data);

            foreach ($request->ingredienti() as $ingrediente) {
                $prodotto->ricette()->create($ingrediente);
            }

            return $prodotto;
        });

        return response()->json(
            $prodotto->load('ricette')->fresh(),
            201
        );
    }

    /**
     * Disattiva logicamente il prodotto (nessuna cancellazione fisica).
     */
    public function destroy(ProdottoBar $prodottoBar): JsonResponse
    {
        $prodottoBar->update(['attivo' => false]);

        return response()->json([
            'messaggio' => "Il prodotto \"{$prodottoBar->nome}\" e' stato disattivato.",
            'id' => $prodottoBar->id,
            'attivo' => false,
        ]);
    }
}

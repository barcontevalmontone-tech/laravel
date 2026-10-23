<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticoloRequest;
use App\Http\Requests\UpdateArticoloRequest;
use App\Http\Resources\ArticoloResource;
use App\Models\Articolo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticoloController extends Controller
{
    /**
     * Display a listing of the articles, with optional search filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $articoli = Articolo::query()
            ->with(['categoria', 'fornitore'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($q) use ($search): void {
                    $q->where('nome', 'like', "%{$search}%")
                        ->orWhere('codice', 'like', "%{$search}%")
                        ->orWhere('barcode', $search);
                });
            })
            ->when($request->filled('categoria_id'), fn ($query) => $query->where('categoria_id', $request->integer('categoria_id')))
            ->when($request->boolean('attivi_only'), fn ($query) => $query->attivi())
            ->orderBy('nome')
            ->paginate(50);

        return ArticoloResource::collection($articoli);
    }

    /**
     * Store a newly created article.
     */
    public function store(StoreArticoloRequest $request): JsonResponse
    {
        $articolo = Articolo::create($request->validated());

        return (new ArticoloResource($articolo->load(['categoria', 'fornitore'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified article.
     */
    public function show(Articolo $articolo): ArticoloResource
    {
        return new ArticoloResource($articolo->load(['categoria', 'fornitore']));
    }

    /**
     * Update the specified article.
     */
    public function update(UpdateArticoloRequest $request, Articolo $articolo): ArticoloResource
    {
        $articolo->update($request->validated());

        return new ArticoloResource($articolo->load(['categoria', 'fornitore']));
    }

    /**
     * Remove the specified article. Articles with movements are protected.
     */
    public function destroy(Articolo $articolo): JsonResponse
    {
        if ($articolo->movimenti()->exists()) {
            return response()->json([
                'message' => "L'articolo {$articolo->codice} ha movimenti registrati e non può essere eliminato. Disattivalo invece.",
            ], 409);
        }

        $articolo->delete();

        return response()->json(null, 204);
    }
}

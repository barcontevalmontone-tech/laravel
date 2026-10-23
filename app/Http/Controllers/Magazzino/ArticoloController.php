<?php

namespace App\Http\Controllers\Magazzino;

use App\Enums\TipoMovimento;
use App\Exceptions\GiacenzaInsufficiente;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticoloRequest;
use App\Http\Requests\StoreMovimentoRequest;
use App\Http\Requests\UpdateArticoloRequest;
use App\Models\Articolo;
use App\Models\Categoria;
use App\Models\Fornitore;
use App\Services\GestoreMagazzino;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticoloController extends Controller
{
    public function __construct(private readonly GestoreMagazzino $gestore) {}

    /**
     * Display a listing of the articles.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $articoli = Articolo::query()
            ->with(['categoria', 'fornitore'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('nome', 'like', "%{$search}%")
                        ->orWhere('codice', 'like', "%{$search}%")
                        ->orWhere('barcode', $search);
                });
            })
            ->orderBy('nome')
            ->paginate(25)
            ->withQueryString();

        return view('magazzino.articoli.index', [
            'articoli' => $articoli,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new article.
     */
    public function create(): View
    {
        return view('magazzino.articoli.form', [
            'articolo' => new Articolo,
            'categorie' => Categoria::orderBy('nome')->get(),
            'fornitori' => Fornitore::orderBy('ragione_sociale')->get(),
        ]);
    }

    /**
     * Store a newly created article.
     */
    public function store(StoreArticoloRequest $request): RedirectResponse
    {
        $articolo = Articolo::create($request->validated());

        return redirect()
            ->route('magazzino.articoli.show', $articolo)
            ->with('status', "Articolo {$articolo->codice} creato.");
    }

    /**
     * Display the specified article with its movement history.
     */
    public function show(Articolo $articolo): View
    {
        return view('magazzino.articoli.show', [
            'articolo' => $articolo->load(['categoria', 'fornitore']),
            'movimenti' => $articolo->movimenti()->paginate(15),
            'tipiMovimento' => TipoMovimento::cases(),
        ]);
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(Articolo $articolo): View
    {
        return view('magazzino.articoli.form', [
            'articolo' => $articolo,
            'categorie' => Categoria::orderBy('nome')->get(),
            'fornitori' => Fornitore::orderBy('ragione_sociale')->get(),
        ]);
    }

    /**
     * Update the specified article.
     */
    public function update(UpdateArticoloRequest $request, Articolo $articolo): RedirectResponse
    {
        $articolo->update($request->validated());

        return redirect()
            ->route('magazzino.articoli.show', $articolo)
            ->with('status', "Articolo {$articolo->codice} aggiornato.");
    }

    /**
     * Remove the specified article.
     */
    public function destroy(Articolo $articolo): RedirectResponse
    {
        if ($articolo->movimenti()->exists()) {
            return back()->with('error', "L'articolo {$articolo->codice} ha movimenti registrati e non può essere eliminato. Disattivalo invece.");
        }

        $articolo->delete();

        return redirect()
            ->route('magazzino.articoli.index')
            ->with('status', "Articolo {$articolo->codice} eliminato.");
    }

    /**
     * Register a stock movement for the given article from the UI.
     */
    public function movimenta(StoreMovimentoRequest $request, Articolo $articolo): RedirectResponse
    {
        try {
            $this->gestore->registra(
                articolo: $articolo,
                tipo: $request->enum('tipo', TipoMovimento::class),
                quantita: (float) $request->input('quantita'),
                causale: $request->input('causale'),
                note: $request->input('note'),
            );
        } catch (GiacenzaInsufficiente $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Movimento registrato.');
    }
}

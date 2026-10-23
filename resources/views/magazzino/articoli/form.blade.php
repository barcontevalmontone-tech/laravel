<x-layout :title="$articolo->exists ? 'Modifica articolo' : 'Nuovo articolo'">
    @php
        $isEdit = $articolo->exists;
    @endphp

    <h1 class="text-xl font-semibold">
        {{ $isEdit ? "Modifica {$articolo->codice}" : 'Nuovo articolo' }}
    </h1>

    <form method="POST"
          action="{{ $isEdit ? route('magazzino.articoli.update', $articolo) : route('magazzino.articoli.store') }}"
          class="mt-4 max-w-2xl rounded-lg bg-white p-6 shadow-sm">
        @if ($isEdit)
            @method('PUT')
        @endif
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="codice" class="block text-sm font-medium text-slate-700">Codice *</label>
                <input type="text" name="codice" id="codice" value="{{ old('codice', $articolo->codice) }}" required
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label for="barcode" class="block text-sm font-medium text-slate-700">Barcode (EAN)</label>
                <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $articolo->barcode) }}"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div class="sm:col-span-2">
                <label for="nome" class="block text-sm font-medium text-slate-700">Nome *</label>
                <input type="text" name="nome" id="nome" value="{{ old('nome', $articolo->nome) }}" required
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label for="unita_misura" class="block text-sm font-medium text-slate-700">Unità di misura</label>
                <select name="unita_misura" id="unita_misura"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    @foreach (['pz', 'kg', 'lt', 'conf', 'pallet'] as $uom)
                        <option value="{{ $uom }}" @selected(old('unita_misura', $articolo->unita_misura ?? 'pz') === $uom)>{{ $uom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="categoria_id" class="block text-sm font-medium text-slate-700">Categoria</label>
                <select name="categoria_id" id="categoria_id"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">— nessuna —</option>
                    @foreach ($categorie as $categoria)
                        <option value="{{ $categoria->id }}" @selected((int) old('categoria_id', $articolo->categoria_id) === $categoria->id)>
                            {{ $categoria->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="fornitore_id" class="block text-sm font-medium text-slate-700">Fornitore</label>
                <select name="fornitore_id" id="fornitore_id"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    <option value="">— nessuno —</option>
                    @foreach ($fornitori as $fornitore)
                        <option value="{{ $fornitore->id }}" @selected((int) old('fornitore_id', $articolo->fornitore_id) === $fornitore->id)>
                            {{ $fornitore->ragione_sociale }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="costo" class="block text-sm font-medium text-slate-700">Costo d'acquisto (€)</label>
                <input type="number" step="0.01" min="0" name="costo" id="costo" value="{{ old('costo', $articolo->costo) }}"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label for="prezzo_vendita" class="block text-sm font-medium text-slate-700">Prezzo di vendita (€)</label>
                <input type="number" step="0.01" min="0" name="prezzo_vendita" id="prezzo_vendita" value="{{ old('prezzo_vendita', $articolo->prezzo_vendita) }}"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label for="giacenza_minima" class="block text-sm font-medium text-slate-700">Giacenza minima (soglia riordino)</label>
                <input type="number" step="0.001" min="0" name="giacenza_minima" id="giacenza_minima" value="{{ old('giacenza_minima', $articolo->giacenza_minima) }}"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label for="giacenza_massima" class="block text-sm font-medium text-slate-700">Giacenza massima (target scorta)</label>
                <input type="number" step="0.001" min="0" name="giacenza_massima" id="giacenza_massima" value="{{ old('giacenza_massima', $articolo->giacenza_massima) }}"
                       class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div class="sm:col-span-2">
                <label for="note" class="block text-sm font-medium text-slate-700">Note</label>
                <textarea name="note" id="note" rows="2"
                          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">{{ old('note', $articolo->note) }}</textarea>
            </div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <input type="hidden" name="attivo" value="0">
                <input type="checkbox" name="attivo" id="attivo" value="1"
                       @checked((bool) old('attivo', $articolo->attivo ?? true))
                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <label for="attivo" class="text-sm font-medium text-slate-700">Articolo attivo</label>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                {{ $isEdit ? 'Salva modifiche' : 'Crea articolo' }}
            </button>
            <a href="{{ $isEdit ? route('magazzino.articoli.show', $articolo) : route('magazzino.articoli.index') }}"
               class="text-sm text-slate-500 hover:text-slate-700">Annulla</a>
        </div>
    </form>
</x-layout>

<x-layout :title="$articolo->nome">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-semibold">{{ $articolo->nome }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Codice <span class="font-mono">{{ $articolo->codice }}</span>
                @if ($articolo->barcode) · EAN {{ $articolo->barcode }} @endif
                @unless ($articolo->attivo)
                    · <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-600">disattivo</span>
                @endunless
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('magazzino.articoli.edit', $articolo) }}"
               class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm hover:bg-slate-50">Modifica</a>
            <form method="POST" action="{{ route('magazzino.articoli.destroy', $articolo) }}"
                  onsubmit="return confirm('Eliminare definitivamente questo articolo?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-md border border-red-300 bg-white px-3 py-1.5 text-sm text-red-700 hover:bg-red-50">Elimina</button>
            </form>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Giacenza attuale</p>
            <p class="mt-1 text-3xl font-semibold {{ $articolo->sottoSogliaMinima() ? 'text-red-600' : '' }}">
                {{ rtrim(rtrim($articolo->giacenza, '0'), '.') }} <span class="text-base font-normal">{{ $articolo->unita_misura }}</span>
            </p>
            <p class="mt-1 text-xs text-slate-400">
                soglia minima: {{ rtrim(rtrim($articolo->giacenza_minima, '0'), '.') ?: '—' }}
                @if ($articolo->giacenza_massima)
                    · massima: {{ rtrim(rtrim($articolo->giacenza_massima, '0'), '.') }}
                @endif
            </p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Costo / Prezzo</p>
            <p class="mt-1 text-lg font-medium">€ {{ number_format((float) $articolo->costo, 2, ',', '.') }} → € {{ number_format((float) $articolo->prezzo_vendita, 2, ',', '.') }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Anagrafiche</p>
            <p class="mt-1 text-sm">Categoria: {{ $articolo->categoria?->nome ?? '—' }}</p>
            <p class="text-sm">Fornitore: {{ $articolo->fornitore?->ragione_sociale ?? '—' }}</p>
        </div>
    </div>

    {{-- Form nuovo movimento --}}
    <div class="mt-8 rounded-lg bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold">Registra un movimento</h2>
        <form method="POST" action="{{ route('magazzino.articoli.movimenta', $articolo) }}" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label for="tipo" class="block text-sm font-medium text-slate-700">Tipo</label>
                <select name="tipo" id="tipo" required
                        class="mt-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                    @foreach ($tipiMovimento as $tipo)
                        <option value="{{ $tipo->value }}" @selected(old('tipo') === $tipo->value)>{{ $tipo->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quantita" class="block text-sm font-medium text-slate-700">Quantità</label>
                <input type="number" step="0.001" min="0.001" name="quantita" id="quantita" value="{{ old('quantita') }}" required
                       class="mt-1 w-32 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label for="causale" class="block text-sm font-medium text-slate-700">Causale</label>
                <input type="text" name="causale" id="causale" value="{{ old('causale') }}" placeholder="es. acquisto, vendita, inventario"
                       class="mt-1 w-64 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
                Registra
            </button>
        </form>
        <p class="mt-2 text-xs text-slate-400">
            Carico e scarico sommano/sottraggono quantità; la rettifica imposta il valore assoluto (per gli inventari).
        </p>
    </div>

    {{-- Storico movimenti --}}
    <div class="mt-8">
        <h2 class="text-lg font-semibold">Storico movimenti</h2>
        <div class="mt-3 overflow-x-auto rounded-lg bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2">Data</th>
                        <th class="px-4 py-2">Tipo</th>
                        <th class="px-4 py-2 text-right">Quantità</th>
                        <th class="px-4 py-2 text-right">Prima</th>
                        <th class="px-4 py-2 text-right">Dopo</th>
                        <th class="px-4 py-2">Causale</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movimenti as $movimento)
                        <tr>
                            <td class="px-4 py-2 text-slate-500">{{ $movimento->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">
                                @php
                                    $badge = match ($movimento->tipo) {
                                        \App\Enums\TipoMovimento::Carico => 'bg-emerald-100 text-emerald-700',
                                        \App\Enums\TipoMovimento::Scarico => 'bg-red-100 text-red-700',
                                        \App\Enums\TipoMovimento::Rettifica => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-0.5 text-xs {{ $badge }}">{{ $movimento->tipo->label() }}</span>
                            </td>
                            <td class="px-4 py-2 text-right">{{ rtrim(rtrim($movimento->quantita, '0'), '.') }}</td>
                            <td class="px-4 py-2 text-right text-slate-500">{{ rtrim(rtrim($movimento->giacenza_prima, '0'), '.') }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ rtrim(rtrim($movimento->giacenza_dopo, '0'), '.') }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $movimento->causale ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">Nessun movimento registrato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $movimenti->links() }}</div>
    </div>
</x-layout>

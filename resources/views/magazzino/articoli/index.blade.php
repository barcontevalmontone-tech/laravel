<x-layout title="Articoli">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Elenco articoli</h1>
        <a href="{{ route('magazzino.articoli.create') }}"
           class="rounded-md bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
            + Nuovo articolo
        </a>
    </div>

    <form method="GET" action="{{ route('magazzino.articoli.index') }}" class="mt-4 flex gap-2">
        <input type="text" name="search" value="{{ $search }}" placeholder="Cerca per nome, codice o barcode…"
               class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
        <button type="submit" class="rounded-md bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-800">Cerca</button>
    </form>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2">Codice</th>
                    <th class="px-4 py-2">Nome</th>
                    <th class="px-4 py-2">Categoria</th>
                    <th class="px-4 py-2 text-right">Giacenza</th>
                    <th class="px-4 py-2 text-right">Prezzo vendita</th>
                    <th class="px-4 py-2">Stato</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($articoli as $articolo)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2 font-mono">
                            <a href="{{ route('magazzino.articoli.show', $articolo) }}" class="text-blue-700 hover:underline">
                                {{ $articolo->codice }}
                            </a>
                        </td>
                        <td class="px-4 py-2">{{ $articolo->nome }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $articolo->categoria?->nome ?? '—' }}</td>
                        <td class="px-4 py-2 text-right {{ $articolo->sottoSogliaMinima() ? 'font-semibold text-red-600' : '' }}">
                            {{ rtrim(rtrim($articolo->giacenza, '0'), '.') }} {{ $articolo->unita_misura }}
                        </td>
                        <td class="px-4 py-2 text-right">€ {{ number_format((float) $articolo->prezzo_vendita, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">
                            @if ($articolo->attivo)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">attivo</span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs text-slate-600">disattivo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            Nessun articolo trovato. {{ $search !== '' ? 'Prova con un\'altra ricerca.' : 'Crea il primo articolo.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $articoli->links() }}
    </div>
</x-layout>

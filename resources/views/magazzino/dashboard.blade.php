<x-layout title="Dashboard magazzino">
    {{-- KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Articoli totali</p>
            <p class="mt-1 text-3xl font-semibold">{{ $totaleArticoli }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $articoliAttivi }} attivi</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Valore giacenza (al costo)</p>
            <p class="mt-1 text-3xl font-semibold">€ {{ number_format($valoreGiacenza, 2, ',', '.') }}</p>
        </div>
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Da riordinare</p>
            <p class="mt-1 text-3xl font-semibold {{ $riepilogo['conteggio'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                {{ $riepilogo['conteggio'] }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
                valore stimato € {{ number_format($riepilogo['valore_costo'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Riordini --}}
    <div class="mt-8">
        <h2 class="text-lg font-semibold">Avvisi di riordino</h2>

        @if ($riordini->isEmpty())
            <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                Nessun articolo sotto soglia. Magazzino in ordine. ✅
            </div>
        @else
            <div class="mt-3 overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2">Codice</th>
                            <th class="px-4 py-2">Articolo</th>
                            <th class="px-4 py-2">Fornitore</th>
                            <th class="px-4 py-2 text-right">Giacenza</th>
                            <th class="px-4 py-2 text-right">Soglia</th>
                            <th class="px-4 py-2 text-right">Da ordinare</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($riordini as $riordino)
                            <tr class="{{ $riordino->esaurito() ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-2 font-mono">
                                    <a class="text-blue-700 hover:underline"
                                       href="{{ route('magazzino.articoli.show', $riordino->articolo) }}">
                                        {{ $riordino->articolo->codice }}
                                    </a>
                                </td>
                                <td class="px-4 py-2">{{ $riordino->articolo->nome }}</td>
                                <td class="px-4 py-2 text-slate-500">{{ $riordino->articolo->fornitore?->ragione_sociale ?? '—' }}</td>
                                <td class="px-4 py-2 text-right font-medium {{ $riordino->esaurito() ? 'text-red-600' : '' }}">
                                    {{ rtrim(rtrim(number_format($riordino->giacenzaAttuale, 3, ',', ''), '0'), '.') }}
                                    {{ $riordino->articolo->unita_misura }}
                                </td>
                                <td class="px-4 py-2 text-right text-slate-500">
                                    {{ rtrim(rtrim(number_format($riordino->sogliaMinima, 3, ',', ''), '0'), '.') }}
                                </td>
                                <td class="px-4 py-2 text-right font-semibold text-amber-700">
                                    +{{ rtrim(rtrim(number_format($riordino->quantitaDaOrdinare, 3, ',', ''), '0'), '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layout>

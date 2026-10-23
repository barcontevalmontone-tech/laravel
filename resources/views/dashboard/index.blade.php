<x-layout title="Dashboard operativa">
    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Dashboard operativa</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ now()->format('d/m/Y') }} · Bar e Tabacchi in un'unica vista
            </p>
        </div>
        @if ($totaliAlert > 0)
            <span class="rounded-full bg-amber-100 px-4 py-1.5 text-sm font-semibold text-amber-800">
                ⚠ {{ $totaliAlert }} alert attivi
            </span>
        @else
            <span class="rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-semibold text-emerald-800">
                ✓ Nessun alert
            </span>
        @endif
    </div>

    {{-- Griglia KPI / Alert --}}
    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">

        {{-- Card 1: Alert Magazzino Bar --}}
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-700">🥤 Alert Magazzino Bar</h2>
                <span class="text-sm text-slate-400">{{ $alertBar->count() }} prodotti</span>
            </div>

            @if ($alertBar->isEmpty())
                <p class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                    Nessuna criticità: tutte le giacenze sono sopra soglia.
                </p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($alertBar->take(6) as $prodotto)
                        <li class="flex items-center justify-between py-2.5 text-sm">
                            <div>
                                <p class="font-medium">{{ $prodotto->nome }}</p>
                                <p class="text-xs text-slate-400">{{ $prodotto->categoria }}</p>
                            </div>
                            <div class="text-right">
                                <p class="{{ (float) $prodotto->giacenza_attuale <= 0 ? 'font-semibold text-red-600' : 'font-semibold text-amber-600' }}">
                                    {{ rtrim(rtrim(number_format((float) $prodotto->giacenza_attuale, 3, ',', '.'), '0'), ',') }} pz
                                </p>
                                <p class="text-xs text-slate-400">soglia {{ rtrim(rtrim(number_format((float) $prodotto->soglia_minima, 3, ',', '.'), '0'), ',') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($alertBar->count() > 6)
                    <p class="mt-2 text-xs text-slate-400">+ altri {{ $alertBar->count() - 6 }} prodotti sotto soglia</p>
                @endif
            @endif
        </div>

        {{-- Card 2: Alert Magazzino Tabacchi (sola lettura) --}}
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-700">🚬 Alert Magazzino Tabacchi</h2>
                <span class="text-sm text-slate-400">{{ $riepilogoTabacchi['conteggio'] }} da riordinare</span>
            </div>

            @if ($riordiniTabacchi->isEmpty())
                <p class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                    Nessun articolo sotto soglia di riordino.
                </p>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($riordiniTabacchi->take(6) as $riordino)
                        <li class="flex items-center justify-between py-2.5 text-sm">
                            <div>
                                <p class="font-medium">{{ $riordino->articolo->nome }}</p>
                                <p class="text-xs text-slate-400">{{ $riordino->articolo->codice }}</p>
                            </div>
                            <div class="text-right">
                                <p class="{{ $riordino->giacenzaAttuale <= 0 ? 'font-semibold text-red-600' : 'font-semibold text-amber-600' }}">
                                    ord. {{ rtrim(rtrim(number_format($riordino->quantitaDaOrdinare, 3, ',', '.'), '0'), ',') }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    € {{ number_format($riordino->quantitaDaOrdinare * (float) $riordino->articolo->costo, 2, ',', '.') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-2 text-xs text-slate-400">
                    Valore totale riordino stimato: € {{ number_format($riepilogoTabacchi['valore_costo'], 2, ',', '.') }}
                    — <a href="{{ route('magazzino.dashboard') }}" class="text-indigo-600 hover:underline">dettaglio nel magazzino tabacchi</a>
                </p>
            @endif
        </div>

        {{-- Card 3: Incasso Bar Oggi --}}
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-700">💶 Incasso Bar Oggi</h2>
                <a href="{{ route('pos.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Apri POS →</a>
            </div>
            <p class="mt-4 text-4xl font-bold tracking-tight {{ $incassoOggi > 0 ? 'text-emerald-600' : 'text-slate-300' }}">
                € {{ number_format($incassoOggi, 2, ',', '.') }}
            </p>
            <div class="mt-3 flex items-center gap-6 text-sm text-slate-500">
                <span><span class="font-semibold text-slate-700">{{ $scontriniOggi }}</span> scontrini emessi</span>
                <span>
                    media € {{ number_format($scontriniOggi > 0 ? $incassoOggi / $scontriniOggi : 0, 2, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- Card 4: Flusso Tabacchi Oggi --}}
        <div class="rounded-lg bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-700">📊 Flusso Tabacchi Oggi</h2>
                <a href="{{ route('magazzino.dashboard') }}" class="text-xs font-medium text-indigo-600 hover:underline">Vai al magazzino →</a>
            </div>
            <p class="mt-4 text-4xl font-bold tracking-tight {{ $movimentiOggi > 0 ? 'text-slate-800' : 'text-slate-300' }}">
                {{ $movimentiOggi }}
            </p>
            <p class="mt-1 text-sm text-slate-500">movimenti registrati oggi</p>
            <div class="mt-3 text-sm text-slate-500">
                di cui <span class="font-semibold text-slate-700">{{ $scarichiOggi }}</span> scarichi
            </div>
        </div>
    </div>
</x-layout>

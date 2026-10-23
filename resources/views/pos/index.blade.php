<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>POS Bar · {{ config('app.name', 'Gestionale') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Alpine.js: unico layer JS del POS (nessun framework pesante). -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>

    {{--
        ┌─────────────────────────────────────────────────────────────┐
        │ Componente Alpine unico: posApp()                           │
        │                                                             │
        │ STATO                                                       │
        │  query           → testo nella barra di ricerca             │
        │  risultati       → card prodotto mostrate a destra          │
        │  carrello        → [{prodotto, quantita}] (in memoria)      │
        │  erroreCarrello  → messaggio 422 da addToCart               │
        │  cercando / inConfirmo → flag di loading                    │
        │                                                             │
        │ DERIVATE                                                    │
        │  totale            → Σ prezzo_vendita × quantita            │
        │  totalePezzi       → Σ quantità                             │
        │  ingredientiScontrino → ricettario aggregato della vendita  │
        │                                                             │
        │ AZIONI                                                      │
        │  cerca()           → GET /api/pos/search (debounce 300 ms,  │
        │                       immediata su Enter = lettura barcode) │
        │  aggiungi(p)       → nuova riga o incrementa (max giacenza) │
        │  aumenta/rimuovi   → +/− sulla riga con re-scalatura        │
        │                       degli ingredienti                     │
        │  confermaVendita() → POST /api/pos/cart come validazione    │
        │                       server-side prima di chiudere lo      │
        │                       scontrino                             │
        └─────────────────────────────────────────────────────────────┘
    --}}
    <body class="min-h-screen bg-slate-950 text-slate-100 antialiased" x-data="posApp()" x-init="cerca()">

        {{-- Barra superiore --}}
        <header class="border-b border-slate-800 bg-slate-900 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold tracking-tight">☕ POS Bar</h1>
                <a href="{{ url('/magazzino') }}"
                   class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700">
                    ← Magazzino
                </a>
            </div>
        </header>

        {{-- Layout a due pannelli: 40% scontrino / 60% catalogo --}}
        <div class="flex min-h-[calc(100vh-73px)] flex-col lg:flex-row">

            {{-- ══════════════ PANNELLO SINISTRO: SCONTRINO CORRENTE (40%) ══════════════ --}}
            <aside class="flex w-full flex-col border-b border-slate-800 bg-slate-900 lg:w-[40%] lg:border-r lg:border-b-0">
                <div class="flex items-center justify-between px-6 py-4">
                    <h2 class="text-lg font-semibold">🧾 Scontrino corrente</h2>
                    <span class="rounded-full bg-slate-800 px-3 py-1 text-sm font-medium text-slate-300"
                          x-text="carrello.length + ' articoli'"></span>
                </div>

                {{-- Lista articoli --}}
                <div class="flex-1 space-y-3 overflow-y-auto px-6 pb-4">

                    <p x-show="carrello.length === 0" x-cloak
                       class="mt-8 text-center text-slate-500">
                        Nessun articolo.<br>
                        Tocca una card nel catalogo per aggiungerlo.
                    </p>

                    <template x-for="riga in carrello" :key="riga.prodotto.id">
                        <div class="rounded-xl border border-slate-700 bg-slate-800 p-4 shadow">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-base font-semibold" x-text="riga.prodotto.nome"></p>
                                    <p class="text-sm text-slate-400">
                                        € <span x-text="riga.prodotto.prezzo_vendita.toFixed(2)"></span> / pz
                                        · disp. <span x-text="riga.prodotto.giacenza_attuale"></span>
                                    </p>
                                </div>
                                <p class="whitespace-nowrap text-base font-bold text-emerald-400"
                                   x-text="'€ ' + (riga.prodotto.prezzo_vendita * riga.quantita).toFixed(2)"></p>
                            </div>

                            {{-- Controlli quantità touch --}}
                            <div class="mt-3 flex items-center justify-between">
                                <button type="button"
                                        @click="rimuovi(riga)"
                                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-700 text-2xl font-bold text-white transition active:scale-95 hover:bg-red-600"
                                        aria-label="Diminuisci quantità">−</button>

                                <span class="text-2xl font-bold tabular-nums" x-text="riga.quantita"></span>

                                <button type="button"
                                        @click="aumenta(riga)"
                                        :disabled="riga.quantita >= riga.prodotto.giacenza_attuale"
                                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-700 text-2xl font-bold text-white transition active:scale-95 hover:bg-emerald-600 disabled:cursor-not-allowed disabled:opacity-40"
                                        aria-label="Aumenta quantità">+</button>
                            </div>

                            {{-- Ingredienti necessari (prodotti con ricetta) --}}
                            <div x-show="riga.prodotto.ha_ricetta && riga.prodotto.ingredienti_necessari.length > 0"
                                 x-cloak
                                 class="mt-3 rounded-lg border border-amber-700/50 bg-amber-950/40 px-3 py-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-amber-400">
                                    🍳 Da preparare — ingredienti
                                </p>
                                <ul class="mt-1 space-y-0.5 text-sm text-amber-200">
                                    <template x-for="ing in riga.prodotto.ingredienti_necessari" :key="ing.ingrediente_nome">
                                        <li>
                                            <span x-text="ing.ingrediente_nome"></span>:
                                            <strong x-text="ing.quantita_per_vendita"></strong>
                                            <span x-text="ing.unita_misura"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer: totale + conferma --}}
                <div class="border-t border-slate-800 bg-slate-900 px-6 py-5">
                    <p x-show="erroreCarrello" x-cloak
                       class="mb-3 whitespace-pre-line rounded-lg border border-red-700 bg-red-950 px-4 py-3 text-sm font-medium text-red-300"
                       x-text="erroreCarrello"></p>

                    {{-- Ricettario aggregato dello scontrino --}}
                    <div x-show="ingredientiScontrino.length > 0" x-cloak
                         class="mb-3 rounded-lg bg-slate-800 px-4 py-2 text-xs text-slate-400">
                        <span class="font-semibold text-slate-300">Totale ingredienti da prelevare:</span>
                        <span x-text="ingredientiScontrino.map(i => `${i.nome} ${i.q} ${i.u}`).join(' · ')"></span>
                    </div>

                    <div class="mb-4 flex items-end justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-wide text-slate-400">Totale parziale</p>
                            <p class="text-sm text-slate-500"><span x-text="totalePezzi"></span> pezzi</p>
                        </div>
                        <p class="text-4xl font-extrabold tabular-nums text-emerald-400"
                           x-text="'€ ' + totale.toFixed(2)"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="svuota()"
                                :disabled="carrello.length === 0"
                                class="h-16 rounded-xl border-2 border-slate-700 bg-transparent text-lg font-semibold text-slate-300 transition active:scale-95 hover:border-red-600 hover:text-red-400 disabled:cursor-not-allowed disabled:opacity-40">
                            Annulla
                        </button>
                        <button type="button" @click="confermaVendita()"
                                :disabled="carrello.length === 0 || inConfirmo"
                                class="h-16 rounded-xl bg-emerald-600 text-lg font-bold text-white shadow-lg shadow-emerald-900/50 transition active:scale-95 hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-40">
                            <span x-show="!inConfirmo">✔ Conferma vendita</span>
                            <span x-show="inConfirmo" x-cloak>Verifica…</span>
                        </button>
                    </div>
                </div>
            </aside>

            {{-- ══════════════ PANNELLO DESTRO: CATALOGO PRODOTTI (60%) ══════════════ --}}
            <section class="flex w-full flex-1 flex-col lg:w-[60%]">

                {{-- Barra di ricerca --}}
                <div class="sticky top-0 z-10 border-b border-slate-800 bg-slate-950/95 px-6 py-4 backdrop-blur">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-2xl text-slate-500">🔍</span>
                        <input type="search"
                               x-model="query"
                               @input="debounceCerca()"
                               @keyup.enter="cerca()"
                               placeholder="Scansiona barcode o digita il nome del prodotto…"
                               class="h-16 w-full rounded-xl border-2 border-slate-700 bg-slate-900 pl-14 pr-32 text-lg text-white placeholder-slate-500 outline-none transition focus:border-emerald-500">
                        <span x-show="cercando" x-cloak
                              class="absolute inset-y-0 right-4 flex items-center text-sm text-slate-400">
                            ⏳ ricerca…
                        </span>
                    </div>
                    <p x-show="matchEsatto" x-cloak
                       class="mt-2 text-sm font-medium text-emerald-400">
                        ✔ Trovato match esatto per codice a barre
                    </p>
                </div>

                {{-- Griglia responsive card prodotto --}}
                <div class="grid flex-1 grid-cols-1 content-start gap-4 p-6 sm:grid-cols-2 xl:grid-cols-3">

                    <p x-show="risultati.length === 0 && !cercando" x-cloak
                       class="col-span-full mt-10 text-center text-lg text-slate-500">
                        Nessun prodotto trovato. Prova con un altro nome o codice.
                    </p>

                    <template x-for="p in risultati" :key="p.id">
                        <button type="button"
                                @click="aggiungi(p)"
                                :disabled="p.giacenza_attuale <= 0"
                                class="group flex min-h-[9rem] flex-col rounded-xl border-2 border-slate-800 bg-slate-900 p-5 text-left shadow transition active:scale-[0.98] hover:border-emerald-500 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">

                            <div class="flex items-start justify-between gap-2">
                                <p class="text-lg font-bold leading-snug" x-text="p.nome"></p>
                                <span x-show="p.ha_ricetta" x-cloak
                                      class="shrink-0 rounded-md bg-amber-900/60 px-2 py-0.5 text-xs font-semibold text-amber-300"
                                      title="Prodotto con ricetta">🍳</span>
                            </div>

                            <p class="mt-1 text-sm uppercase tracking-wide text-slate-500" x-text="p.categoria"></p>

                            <p class="mt-auto pt-3 text-2xl font-extrabold text-emerald-400"
                               x-text="'€ ' + p.prezzo_vendita.toFixed(2)"></p>

                            <div class="mt-1 flex items-center justify-between text-sm">
                                <span class="text-slate-400" x-text="'Barcode: ' + (p.codice_barre ?? '—')"></span>
                                <span class="font-semibold"
                                      :class="p.giacenza_attuale <= 0 ? 'text-red-400' : 'text-slate-300'"
                                      x-text="p.giacenza_attuale <= 0 ? 'ESAURITO' : 'gac. ' + p.giacenza_attuale"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </section>
        </div>

        {{-- Toast di conferma vendita --}}
        <div x-show="confermato" x-cloak
             x-transition.opacity.duration.500ms
             class="fixed inset-x-0 bottom-8 mx-auto w-fit rounded-xl bg-emerald-600 px-8 py-4 text-xl font-bold text-white shadow-2xl">
            ✅ Vendita confermata — nuovo scontrino pronto
        </div>

        <script>
            function posApp() {
                return {
                    // ── Stato ─────────────────────────────────────────────
                    query: '',
                    risultati: [],
                    carrello: [],            // [{ prodotto: {...}, quantita: Number }]
                    cercando: false,
                    inConfirmo: false,
                    confermato: false,
                    matchEsatto: false,
                    erroreCarrello: '',
                    _timer: null,

                    // ── Proprietà derivate ────────────────────────────────
                    get totale() {
                        return this.carrello.reduce(
                            (t, r) => t + r.prodotto.prezzo_vendita * r.quantita, 0
                        );
                    },

                    get totalePezzi() {
                        return Math.round(this.carrello.reduce((t, r) => t + r.quantita, 0) * 1000) / 1000;
                    },

                    // Ricettario aggregato: somma degli ingredienti di tutte le righe.
                    get ingredientiScontrino() {
                        const agg = {};
                        for (const riga of this.carrello) {
                            for (const ing of (riga.prodotto.ingredienti_necessari ?? [])) {
                                const k = ing.ingrediente_nome + '|' + ing.unita_misura;
                                agg[k] ??= { nome: ing.ingrediente_nome, q: 0, u: ing.unita_misura };
                                agg[k].q = Math.round((agg[k].q + ing.quantita_per_vendita) * 1000) / 1000;
                            }
                        }
                        return Object.values(agg);
                    },

                    // ── Ricerca (debounce 300 ms) ─────────────────────────
                    debounceCerca() {
                        clearTimeout(this._timer);
                        this._timer = setTimeout(() => this.cerca(), 300);
                    },

                    async cerca() {
                        clearTimeout(this._timer);
                        this.cercando = true;
                        this.matchEsatto = false;
                        this.erroreCarrello = '';

                        try {
                            const q = encodeURIComponent(this.query.trim());
                            const res = await fetch(`/api/pos/search?query=${q}`, {
                                headers: { Accept: 'application/json' },
                            });
                            if (!res.ok) throw new Error('HTTP ' + res.status);

                            const json = await res.json();

                            if (json.tipo === 'match_esatto') {
                                this.matchEsatto = true;
                                this.risultati = [json.prodotto];
                                // Barcode letto dal lettore: aggiunge direttamente al
                                // carrello e azzera il campo per la lettura successiva.
                                this.aggiungi(json.prodotto);
                                this.query = '';
                            } else {
                                this.risultati = json.risultati ?? [];
                            }
                        } catch (e) {
                            this.erroreCarrello = 'Errore di rete nella ricerca prodotti.';
                        } finally {
                            this.cercando = false;
                        }
                    },

                    // ── Carrello (stato in memoria) ───────────────────────
                    aggiungi(prodotto) {
                        if (prodotto.giacenza_attuale <= 0) return;

                        const riga = this.carrello.find(r => r.prodotto.id === prodotto.id);

                        if (riga) {
                            this.aumenta(riga);
                        } else {
                            // Conserva la ricetta base unitaria per re-scalare gli
                            // ingredienti quando l'operatore cambia la quantità.
                            const copia = { ...prodotto };
                            copia._ricetta_base = (prodotto.ingredienti_necessari ?? []).map(ing => ({
                                ...ing,
                                quantita_unitaria: ing.quantita_per_vendita, // quantità per 1 pz (search usa quantita=1)
                            }));
                            this.carrello.push({ prodotto: copia, quantita: 1 });
                        }

                        this.erroreCarrello = '';
                    },

                    aumenta(riga) {
                        if (riga.quantita < riga.prodotto.giacenza_attuale) {
                            riga.quantita += 1;
                            this.ricalcolaIngredienti(riga);
                        }
                    },

                    rimuovi(riga) {
                        riga.quantita -= 1;
                        if (riga.quantita <= 0) {
                            this.carrello = this.carrello.filter(r => r.prodotto.id !== riga.prodotto.id);
                        } else {
                            this.ricalcolaIngredienti(riga);
                        }
                    },

                    // Gli ingredienti sono derivati: li ri-scaliamo lato client dalla
                    // ricetta base unitaria moltiplicata per la nuova quantità.
                    ricalcolaIngredienti(riga) {
                        if (!riga.prodotto.ha_ricetta) return;
                        riga.prodotto.ingredienti_necessari = (riga.prodotto._ricetta_base ?? []).map(ing => ({
                            ingrediente_nome: ing.ingrediente_nome,
                            unita_misura: ing.unita_misura,
                            quantita_per_vendita: Math.round(ing.quantita_unitaria * riga.quantita * 1000) / 1000,
                        }));
                    },

                    svuota() {
                        this.carrello = [];
                        this.erroreCarrello = '';
                    },

                    // ── Conferma: validazione server-side via /api/pos/cart ─
                    async confermaVendita() {
                        if (this.carrello.length === 0) return;

                        this.inConfirmo = true;
                        this.erroreCarrello = '';

                        const payload = {
                            articoli: this.carrello.map(r => ({
                                prodotto_bar_id: r.prodotto.id,
                                quantita: r.quantita,
                            })),
                        };

                        try {
                            const res = await fetch('/api/pos/cart', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify(payload),
                            });

                            const json = await res.json();

                            if (res.status === 422) {
                                const dettagli = (json.errori ?? [])
                                    .map(e => `${e.nome ?? 'ID ' + e.prodotto_bar_id}: ${e.motivo}`)
                                    .join(' — ');
                                this.erroreCarrello = json.message + (dettagli ? '\n' + dettagli : '');
                                return;
                            }

                            if (!res.ok) throw new Error('HTTP ' + res.status);

                            // Tutto valido: chiudi lo scontrino e riparti da zero.
                            this.confermato = true;
                            this.svuota();
                            setTimeout(() => { this.confermato = false; }, 2500);
                            this.cerca(); // ricarica il catalogo con le giacenze correnti
                        } catch (e) {
                            this.erroreCarrello = 'Errore di rete durante la conferma della vendita.';
                        } finally {
                            this.inConfirmo = false;
                        }
                    },
                };
            }
        </script>
    </body>
</html>

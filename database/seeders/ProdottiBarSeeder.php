<?php

namespace Database\Seeders;

use App\Models\ProdottoBar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProdottiBarSeeder extends Seeder
{
    /**
     * Popola il catalogo bar con prodotti di esempio realistici.
     */
    public function run(): void
    {
        // Idempotente: inserisce solo i prodotti non ancora presenti.
        $prodotti = [
            [
                'nome' => 'Caffè espresso',
                'codice_barre' => '8001234567890',
                'categoria' => 'Bevande calde',
                'prezzo_acquisto' => 0.18,
                'prezzo_vendita' => 1.20,
                'giacenza_attuale' => 480.000,   // n. tazzine (dosato dal macchinario)
                'soglia_minima' => 100.000,
                'ha_ricetta' => false,
                'allergeni' => null,
            ],
            [
                'nome' => 'Cornetto alla crema',
                'codice_barre' => '8001234567891',
                'categoria' => 'Pasticceria',
                'prezzo_acquisto' => 0.55,
                'prezzo_vendita' => 1.50,
                'giacenza_attuale' => 12.000,
                'soglia_minima' => 20.000,        // sotto soglia: da riordinare
                'ha_ricetta' => false,
                'allergeni' => 'Glutine, Uova, Latte',
            ],
            [
                'nome' => 'Acqua naturale 50cl',
                'codice_barre' => '8001234567892',
                'categoria' => 'Bevande fredde',
                'prezzo_acquisto' => 0.20,
                'prezzo_vendita' => 1.00,
                'giacenza_attuale' => 96.000,
                'soglia_minima' => 24.000,
                'ha_ricetta' => false,
                'allergeni' => null,
            ],
            [
                'nome' => 'Birra chiara 33cl',
                'codice_barre' => '8001234567893',
                'categoria' => 'Bevande fredde',
                'prezzo_acquisto' => 0.62,
                'prezzo_vendita' => 3.50,
                'giacenza_attuale' => 72.000,
                'soglia_minima' => 24.000,
                'ha_ricetta' => false,
                'allergeni' => 'Glutine (orzo)',
            ],
            [
                'nome' => 'Panino farcito',
                'codice_barre' => null,           // prodotto sfuso, senza barcode
                'categoria' => 'Salati',
                'prezzo_acquisto' => 1.10,
                'prezzo_vendita' => 3.00,
                'giacenza_attuale' => 8.000,
                'soglia_minima' => 5.000,
                'ha_ricetta' => false,
                'allergeni' => 'Glutine, Latte, Sedano',
            ],
        ];

        foreach ($prodotti as $prodotto) {
            ProdottoBar::query()
                ->firstOrCreate(['nome' => $prodotto['nome']], $prodotto);
        }

        // Cappuccino: prodotto a ricetta scomposta in ingredienti.
        $cappuccino = ProdottoBar::query()->firstOrCreate(
            ['nome' => 'Cappuccino'],
            [
                'codice_barre' => '8001234567894',
                'categoria' => 'Bevande calde',
                'prezzo_acquisto' => 0.45,
                'prezzo_vendita' => 1.60,
                'giacenza_attuale' => 0.000,      // si prepara al momento
                'soglia_minima' => 0.000,
                'ha_ricetta' => true,
                'allergeni' => 'Latte',
            ]
        );

        $ricetta = [
            ['ingrediente_nome' => 'Caffè macinato', 'quantita_uso' => 7.000, 'unita_misura' => 'g'],
            ['ingrediente_nome' => 'Latte intero', 'quantita_uso' => 0.120, 'unita_misura' => 'l'],
            ['ingrediente_nome' => 'Zucchero', 'quantita_uso' => 5.000, 'unita_misura' => 'g'],
        ];

        DB::transaction(function () use ($cappuccino, $ricetta): void {
            $cappuccino->ricette()->delete();

            foreach ($ricetta as $riga) {
                $cappuccino->ricette()->create($riga);
            }
        });
    }
}

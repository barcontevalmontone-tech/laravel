<?php

namespace Database\Seeders;

use App\Enums\TipoMovimento;
use App\Models\Articolo;
use App\Models\Categoria;
use App\Models\Fornitore;
use App\Models\Movimento;
use Illuminate\Database\Seeder;

/**
 * Popola il magazzino con dati di demo realistici: categorie, fornitori,
 * articoli (alcuni in riordino) e uno storico movimenti coerente.
 */
class DemoMagazzinoSeeder extends Seeder
{
    public function run(): void
    {
        $materiePrime = Categoria::create(['nome' => 'Materie prime']);
        $elettronica = Categoria::create(['nome' => 'Elettronica']);
        $imballaggi = Categoria::create(['nome' => 'Imballaggi']);
        Categoria::create(['nome' => 'Cancelleria']);

        $fornitoreAcciaio = Fornitore::create([
            'ragione_sociale' => 'Acciaie Riunite S.r.l.',
            'email' => 'ordini@acciairiunite.it',
            'telefono' => '+39 02 5551 0001',
        ]);
        $fornitoreChip = Fornitore::create([
            'ragione_sociale' => 'EuroChip Distribution',
            'email' => 'sales@eurochip.eu',
            'telefono' => '+49 30 5550 0002',
        ]);
        Fornitore::create([
            'ragione_sociale' => 'Cartotecnica Veneta',
            'email' => 'info@cartotecnicaveneta.it',
        ]);

        $articoli = [
            [
                'codice' => 'ART-1001', 'nome' => 'Piastra acciaio 2mm', 'unita_misura' => 'kg',
                'categoria_id' => $materiePrime->id, 'fornitore_id' => $fornitoreAcciaio->id,
                'costo' => 3.4, 'prezzo_vendita' => 5.9, 'giacenza' => 850, 'giacenza_minima' => 200,
                'giacenza_massima' => 1200,
            ],
            [
                'codice' => 'ART-1002', 'nome' => 'Vite M6 x 20 zincata', 'unita_misura' => 'pz',
                'categoria_id' => $materiePrime->id, 'fornitore_id' => $fornitoreAcciaio->id,
                'costo' => 0.05, 'prezzo_vendita' => 0.15, 'giacenza' => 40, 'giacenza_minima' => 500,
                'giacenza_massima' => 5000,
            ],
            [
                'codice' => 'ART-2001', 'nome' => 'Microcontrollore ESP32-WROOM', 'unita_misura' => 'pz',
                'categoria_id' => $elettronica->id, 'fornitore_id' => $fornitoreChip->id,
                'costo' => 6.8, 'prezzo_vendita' => 12.5, 'giacenza' => 0, 'giacenza_minima' => 50,
                'giacenza_massima' => 400,
            ],
            [
                'codice' => 'ART-2002', 'nome' => 'Sensore temperatura DS18B20', 'unita_misura' => 'pz',
                'categoria_id' => $elettronica->id, 'fornitore_id' => $fornitoreChip->id,
                'costo' => 1.9, 'prezzo_vendita' => 4.5, 'giacenza' => 120, 'giacenza_minima' => 80,
                'giacenza_massima' => null,
            ],
            [
                'codice' => 'ART-3001', 'nome' => 'Scatone imballo 40x30x20', 'unita_misura' => 'pz',
                'categoria_id' => $imballaggi->id, 'fornitore_id' => null,
                'costo' => 0.75, 'prezzo_vendita' => 0, 'giacenza' => 15, 'giacenza_minima' => 100,
                'giacenza_massima' => 600,
            ],
            [
                'codice' => 'ART-9999', 'nome' => 'Vecchio connettore DB9 (fuori listino)', 'unita_misura' => 'pz',
                'categoria_id' => $elettronica->id, 'fornitore_id' => null,
                'costo' => 2.2, 'prezzo_vendita' => 0, 'giacenza' => 3, 'giacenza_minima' => 10,
                'giacenza_massima' => null, 'attivo' => false,
            ],
        ];

        foreach ($articoli as $dati) {
            $articolo = Articolo::firstOrCreate(
                ['codice' => $dati['codice']],
                array_diff_key($dati, ['codice' => null]),
            );

            if ($articolo->wasRecentlyCreated) {
                $caricata = max($dati['giacenza'], $dati['giacenza_minima']);

                Movimento::create([
                    'articolo_id' => $articolo->id,
                    'tipo' => TipoMovimento::Carico,
                    'quantita' => $caricata,
                    'giacenza_prima' => 0,
                    'giacenza_dopo' => $caricata,
                    'causale' => 'Giacenza iniziale',
                ]);

                $scaricato = round(max(0, $caricata - $dati['giacenza']), 3);

                if ($scaricato > 0) {
                    Movimento::create([
                        'articolo_id' => $articolo->id,
                        'tipo' => TipoMovimento::Scarico,
                        'quantita' => $scaricato,
                        'giacenza_prima' => $caricata,
                        'giacenza_dopo' => $dati['giacenza'],
                        'causale' => 'Consumo di produzione',
                    ]);
                }
            }
        }
    }
}

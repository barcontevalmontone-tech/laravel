<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Conferma definitiva di una vendita dal POS del Bar.
 *
 * Riceve l'array degli articoli venduti (id prodotto + quantità); la
 * validazione business (prodotti attivi, giacenza/ingredienti sufficienti)
 * avviene nel controller, in transazione e con lock sulle righe.
 */
class ConfirmSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'articoli' => ['required', 'array', 'min:1'],
            'articoli.*.prodotto_bar_id' => ['required', 'integer'],
            'articoli.*.quantita' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'articoli.required' => 'L\'array articoli dello scontrino è obbligatorio.',
            'articoli.min' => 'Lo scontrino deve contenere almeno un articolo.',
            'articoli.*.prodotto_bar_id.required' => 'Ogni riga deve referenziare un prodotto bar.',
            'articoli.*.quantita.gt' => 'La quantità deve essere maggiore di zero.',
        ];
    }

    /**
     * Articoli della vendita raggruppati per prodotto (somma delle righe
     * duplicate) con quantità normalizzata in float.
     *
     * @return array<int, float> mappa prodotto_bar_id => quantita
     */
    public function articoliNormalizzati(): array
    {
        $risultato = [];

        foreach ($this->validated()['articoli'] as $riga) {
            $id = (int) $riga['prodotto_bar_id'];
            $risultato[$id] = round(($risultato[$id] ?? 0) + (float) $riga['quantita'], 3);
        }

        return $risultato;
    }
}

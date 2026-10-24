<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Richiesta di ricerca prodotti per il POS del Bar.
 *
 * La query viene usata dal controller per cercare prima un match esatto
 * sul codice_barre e, in assenza, una lista di nomi simili (LIKE).
 */
class PosSearchRequest extends FormRequest
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
            'query' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }

    /**
     * Query normalizzata: trim e maiuscole per il confronto barcode.
     */
    public function queryRicerca(): string
    {
        return trim((string) $this->validated()['query']);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'query.required' => 'Il campo query è obbligatorio per la ricerca.',
            'query.string' => 'La query di ricerca deve essere una stringa.',
            'query.min' => 'La query di ricerca deve contenere almeno un carattere.',
            'query.max' => 'La query di ricerca non può superare 255 caratteri.',
        ];
    }
}

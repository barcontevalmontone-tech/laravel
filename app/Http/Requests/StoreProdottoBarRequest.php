<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProdottoBarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'codice_barre' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('prodotti_bar', 'codice_barre'),
            ],
            'categoria' => ['required', 'string', 'max:255'],
            'prezzo_acquisto' => ['nullable', 'numeric', 'min:0'],
            'prezzo_vendita' => ['required', 'numeric', 'gt:0'],
            'giacenza_attuale' => ['nullable', 'numeric', 'min:0'],
            'soglia_minima' => ['nullable', 'numeric', 'min:0'],
            'ha_ricetta' => ['sometimes', 'boolean'],
            'allergeni' => ['nullable', 'string'],
            'attivo' => ['sometimes', 'boolean'],
            // Richiesti solo quando il prodotto e' a ricetta (es. cappuccino).
            'ingredienti' => [
                Rule::requiredIf(fn (): bool => (bool) $this->input('ha_ricetta', false)),
                'array',
                'min:1',
            ],
            'ingredienti.*.ingrediente_nome' => ['required_with:ingredienti', 'string', 'max:255'],
            'ingredienti.*.quantita_uso' => ['required_with:ingredienti', 'numeric', 'gt:0'],
            'ingredienti.*.unita_misura' => ['required_with:ingredienti', 'string', 'max:10'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prezzo_vendita.gt' => 'Il prezzo di vendita deve essere maggiore di zero.',
            'ingredienti.required' => 'Se il prodotto ha una ricetta, occorre indicare almeno un ingrediente.',
            'ingredienti.min' => 'Se il prodotto ha una ricetta, occorre indicare almeno un ingrediente.',
        ];
    }

    /**
     * Restituisce gli ingredienti validati (vuoti rimossi).
     *
     * @return list<array{ingrediente_nome: string, quantita_uso: float, unita_misura: string}>
     */
    public function ingredienti(): array
    {
        if (! $this->boolean('ha_ricetta')) {
            return [];
        }

        return collect($this->validated()['ingredienti'] ?? [])
            ->filter(fn (array $i): bool => filled($i['ingrediente_nome'] ?? null))
            ->values()
            ->all();
    }
}

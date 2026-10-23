<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticoloRequest extends FormRequest
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
        $articoloId = $this->route('articolo');

        return [
            'codice' => ['required', 'string', 'max:255', Rule::unique('articoli', 'codice')->ignore($articoloId)],
            'barcode' => ['nullable', 'string', 'max:255'],
            'nome' => ['required', 'string', 'max:255'],
            'unita_misura' => ['nullable', 'string', 'max:10'],
            'categoria_id' => ['nullable', 'integer', 'exists:categorie,id'],
            'fornitore_id' => ['nullable', 'integer', 'exists:fornitori,id'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'prezzo_vendita' => ['nullable', 'numeric', 'min:0'],
            'giacenza_minima' => ['nullable', 'numeric', 'min:0'],
            'giacenza_massima' => ['nullable', 'numeric', 'gt:giacenza_minima'],
            'attivo' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * Customize the error messages for validators failing.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codice.unique' => 'Il codice articolo è già in uso.',
            'giacenza_massima.gt' => 'La giacenza massima deve essere maggiore della minima.',
        ];
    }
}

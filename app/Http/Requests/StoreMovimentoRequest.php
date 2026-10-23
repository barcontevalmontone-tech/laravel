<?php

namespace App\Http\Requests;

use App\Enums\TipoMovimento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovimentoRequest extends FormRequest
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
            'tipo' => ['required', Rule::enum(TipoMovimento::class)],
            'quantita' => ['required', 'numeric', 'gt:0'],
            'causale' => ['nullable', 'string', 'max:255'],
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
            'tipo.required' => 'Indica il tipo di movimento (carico, scarico o rettifica).',
            'quantita.gt' => 'La quantità deve essere maggiore di zero.',
        ];
    }
}

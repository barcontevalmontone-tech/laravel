<?php

namespace App\Http\Requests;

use App\Enums\CausaleMagazzino;
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
            'causale' => ['required', 'string', Rule::enum(CausaleMagazzino::class)],
            'note' => ['nullable', 'string', 'max:2000'],
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
            'causale.required' => 'La causale è obbligatoria: scegli una causale di magazzino valida.',
            'causale.enum' => 'La causale scelta non è prevista dal regolamento di magazzino.',
        ];
    }
}

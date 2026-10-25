<?php

namespace App\Http\Requests;

use App\Enum\DrinkPackageType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DrinkItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // if want to create a new Drink, all fields are required
            'name' => 'required_without:drink_id|nullable|string|max:255|unique:drinks,name',
            'brand' => 'required_without:drink_id|nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'alcoholic' => 'required_without:drink_id|boolean',
            'type' => ['required_without:drink_id', Rule::in(DrinkPackageType::cases())],
            'sold_to_minor' => 'required_without:drink_id|boolean',
            'sugar_free' => 'required_without:drink_id|boolean',
            'gluten_free' => 'required_without:drink_id|boolean',

            'drink_id' => 'required_without:name|nullable|exists:drinks,id',
            'cl' => 'required|integer|min:5|max:100',
            'package_type' => ['required', Rule::in(DrinkPackageType::cases())],
            'barcode' => 'required|string|max:255|unique:drink_items,barcode',
            'price' => 'nullable|integer|min:1',
        ];
    }
}

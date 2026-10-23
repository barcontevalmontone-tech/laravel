<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FornitoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ragione_sociale' => $this->ragione_sociale,
            'partita_iva' => $this->partita_iva,
            'telefono' => $this->telefono,
            'email' => $this->email,
        ];
    }
}

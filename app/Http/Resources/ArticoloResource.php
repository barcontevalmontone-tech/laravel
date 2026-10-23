<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticoloResource extends JsonResource
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
            'codice' => $this->codice,
            'barcode' => $this->barcode,
            'nome' => $this->nome,
            'unita_misura' => $this->unita_misura,
            'categoria' => new CategoriaResource($this->whenLoaded('categoria')),
            'fornitore' => new FornitoreResource($this->whenLoaded('fornitore')),
            'costo' => $this->costo,
            'prezzo_vendita' => $this->prezzo_vendita,
            'giacenza' => $this->giacenza,
            'giacenza_minima' => $this->giacenza_minima,
            'giacenza_massima' => $this->giacenza_massima,
            'attivo' => $this->attivo,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

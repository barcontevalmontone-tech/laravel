<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimentoResource extends JsonResource
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
            'articolo' => new ArticoloResource($this->whenLoaded('articolo')),
            'tipo' => $this->tipo->value,
            'tipo_label' => $this->tipo->label(),
            'quantita' => $this->quantita,
            'giacenza_prima' => $this->giacenza_prima,
            'giacenza_dopo' => $this->giacenza_dopo,
            'causale' => $this->causale?->value,
            'causale_label' => $this->causale?->label(),
            'note' => $this->note,
            'utente' => $this->whenLoaded('utente', fn () => [
                'id' => $this->utente?->id,
                'nome' => $this->utente?->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}

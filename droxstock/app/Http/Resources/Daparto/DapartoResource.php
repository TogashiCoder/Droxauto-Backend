<?php

namespace App\Http\Resources\Daparto;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Daparto Resource for API responses
 */
class DapartoResource extends JsonResource
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
            'tiltle' => $this->tiltle,
            'teilemarke_teilenummer' => $this->teilemarke_teilenummer,
            'preis' => $this->preis,
            'interne_artikelnummer' => $this->interne_artikelnummer,
            'zustand' => $this->zustand,
            'pfand' => $this->pfand,
            'versandklasse' => $this->versandklasse,
            'lieferzeit' => $this->lieferzeit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}

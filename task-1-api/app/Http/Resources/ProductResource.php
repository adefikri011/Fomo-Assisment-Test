<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'flash_sale_price' => $this->flash_sale_price 
                ? (float) $this->flash_sale_price 
                : null,
            'is_flash_sale' => (bool) $this->is_flash_sale,
            'stock' => $this->stock,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
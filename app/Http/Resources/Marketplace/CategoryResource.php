<?php

namespace App\Http\Resources\Marketplace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'icon' => upload_url($this->icon, defaultDir: 'categories'),
            'order_column' => $this->order_column,
            'products_count' => (int) ($this->products_count ?? 0),
        ];
    }
}

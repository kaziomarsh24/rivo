<?php

namespace App\Http\Resources\Training;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'icon' => upload_url($this->icon),
            'order_column' => $this->order_column,
            'lessons_count' => $this->whenCounted('lessons', $this->lessons_count),
        ];
    }
}


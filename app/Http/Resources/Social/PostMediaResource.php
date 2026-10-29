<?php

namespace App\Http\Resources\Social;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'media_url' => upload_url($this->media_path),
            'media_type' => $this->media_type,
            'thumbnail_url' => upload_url($this->thumbnail_path),
            'order' => $this->order,
        ];
    }
}

<?php

namespace App\Http\Resources\Training;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingLessonStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'step_number' => $this->step_number,
            'title' => $this->title,
            'instruction' => $this->instruction,
            'tip' => $this->tip,
            'image_url' => upload_url($this->image_path),
        ];
    }
}


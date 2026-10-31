<?php

namespace App\Http\Resources\Training;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingLessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new TrainingCategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'slug' => $this->slug,
            'tag' => $this->tag,
            'species' => $this->species,
            'difficulty' => $this->difficulty,
            'duration_minutes' => $this->duration_minutes,
            'duration_formatted' => "{$this->duration_minutes} min",
            'impact' => $this->impact,
            'reward_tp' => $this->reward_tp,
            'reward_tp_formatted' => "+{$this->reward_tp} TP",
            'reward_xp' => $this->reward_xp,
            'summary' => $this->summary,
            'description' => $this->description,
            'thumbnail' => upload_url($this->thumbnail),
            'video_url' => $this->video_url,
            'is_plus_only' => (bool) $this->is_plus_only,
            'is_bookmarked' => (bool) ($this->is_bookmarked ?? false),
            'is_completed' => (bool) ($this->is_completed ?? false),
            'completed_at' => $this->completed_at ?? null,
        ];
    }
}


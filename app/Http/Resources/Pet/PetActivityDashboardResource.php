<?php

namespace App\Http\Resources\Pet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetActivityDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'pet' => $this->resource['pet'],
            'level' => $this->resource['level'],
            'rank' => $this->resource['rank'],
            'metrics' => $this->resource['metrics'],
            'today_activities' => $this->resource['today_activities'],
            'streaks_summary' => $this->resource['streaks_summary'],
        ];
    }
}


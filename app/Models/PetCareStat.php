<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;

class PetCareStat extends Model
{
    use HasFactory, AutoClearsCache;

    protected $fillable = [
        'pet_id',
        'total_completed_tasks',
        'total_xp',
        'current_streak_days',
        'longest_streak_days',
        'last_care_date',
    ];

    protected $casts = [
        'total_completed_tasks' => 'integer',
        'total_xp' => 'integer',
        'current_streak_days' => 'integer',
        'longest_streak_days' => 'integer',
        'last_care_date' => 'date',
    ];

    public array $cacheParents = ['pet'];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;

class PetTrainingStat extends Model
{
    use HasFactory, AutoClearsCache;

    protected $fillable = [
        'pet_id',
        'total_tp',
        'current_rank',
        'current_streak_days',
        'longest_streak_days',
        'last_training_date',
    ];

    protected $casts = [
        'total_tp' => 'integer',
        'current_streak_days' => 'integer',
        'longest_streak_days' => 'integer',
        'last_training_date' => 'date',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}


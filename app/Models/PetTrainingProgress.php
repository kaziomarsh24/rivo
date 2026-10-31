<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;

class PetTrainingProgress extends Model
{
    use HasFactory, AutoClearsCache;

    protected $table = 'pet_training_progress';

    protected $fillable = [
        'pet_id',
        'lesson_id',
        'status',
        'completed_at',
        'tp_earned',
        'xp_earned',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'tp_earned' => 'integer',
        'xp_earned' => 'integer',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'lesson_id');
    }
}


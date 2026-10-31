<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetTrainingBookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pet_id',
        'lesson_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'lesson_id');
    }
}


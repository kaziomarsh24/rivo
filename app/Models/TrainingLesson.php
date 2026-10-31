<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;

class TrainingLesson extends Model
{
    use HasFactory, AutoClearsCache, HasUploadPurge;

    /**
     * Invalidate parent category cache when a lesson changes.
     */
    protected array $cacheParents = ['category'];

    /**
     * File attributes that should be purged automatically by HasUploadPurge.
     */
    protected array $purgeableFiles = ['thumbnail'];

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'tag',
        'species',
        'difficulty',
        'duration_minutes',
        'impact',
        'reward_tp',
        'reward_xp',
        'summary',
        'description',
        'thumbnail',
        'video_url',
        'is_plus_only',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'reward_tp' => 'integer',
        'reward_xp' => 'integer',
        'is_plus_only' => 'boolean',
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'category_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TrainingLessonStep::class, 'lesson_id')->orderBy('step_number');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(PetTrainingProgress::class, 'lesson_id');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(PetTrainingBookmark::class, 'lesson_id');
    }
}


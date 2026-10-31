<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;

class TrainingLessonStep extends Model
{
    use HasFactory, AutoClearsCache, HasUploadPurge;

    /**
     * Invalidate parent lesson cache when step changes.
     */
    protected array $cacheParents = ['lesson'];

    /**
     * File attributes that should be purged automatically by HasUploadPurge.
     */
    protected array $purgeableFiles = ['image_path'];

    protected $fillable = [
        'lesson_id',
        'step_number',
        'title',
        'instruction',
        'tip',
        'image_path',
    ];

    protected $casts = [
        'step_number' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'lesson_id');
    }
}


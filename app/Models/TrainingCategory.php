<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;

class TrainingCategory extends Model
{
    use HasFactory, AutoClearsCache, HasUploadPurge;

    /**
     * File attributes that should be purged automatically by HasUploadPurge.
     */
    protected array $purgeableFiles = ['icon'];

    protected $fillable = [
        'name',
        'slug',
        'subtitle',
        'icon',
        'order_column',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class, 'category_id')->orderBy('order_column');
    }
}


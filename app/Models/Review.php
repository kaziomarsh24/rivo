<?php

namespace App\Models;

use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Facades\Upload;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    /**
     * HasUploadPurge: Automatically cleans up physical review photos from storage disk
     * with Smart Array Diffing on update, and purges all photos when review is deleted.
     */
    use HasFactory, AutoClearsCache, HasUploadPurge;

    /**
     * File attributes that should be purged automatically by HasUploadPurge.
     */
    protected array $purgeableFiles = ['photos'];

    protected $fillable = [
        'user_id',
        'order_id',
        'reviewable_type',
        'reviewable_id',
        'rating',
        'comment',
        'photos',
        'is_anonymous',
    ];

    protected $casts = [
        'rating' => 'integer',
        'photos' => 'array',
        'is_anonymous' => 'boolean',
    ];

    protected static function booted(): void
    {
        // When a review is deleted, clean up physical files via Smart Upload and recalculate ratings
        static::deleted(function (Review $review) {
            // 1. Delete physical photo files from disk using Smart Upload
            if (!empty($review->photos) && is_array($review->photos)) {
                foreach ($review->photos as $photo) {
                    Upload::delete($photo);
                }
            }

            // 2. Recalculate parent Product or Store rating and reviews_count
            $reviewable = $review->reviewable;
            if ($reviewable) {
                $avg = $reviewable->reviews()->avg('rating') ?: 5.0;
                $count = $reviewable->reviews()->count();
                $reviewable->update([
                    'rating' => round($avg, 1),
                    'reviews_count' => $count,
                ]);

                $table = $reviewable->getTable();
                try {
                    if (Cache::supportsTags()) {
                        Cache::tags(['reviews', "{$table}:{$reviewable->id}", $table])->flush();
                    }
                } catch (\Throwable $e) {}
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Cache tags of related models to invalidate on change.
     */
    public function getRelatedCacheTags(): array
    {
        return ['products', 'stores'];
    }
}
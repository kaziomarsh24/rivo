<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use KaziOmar\AtomicCache\Facades\AtomicCache;
use KaziOmar\AtomicCache\Traits\HasAtomicCache;

/**
 * Trait Cacheable
 *
 * Backed by the official thekaziomar/laravel-atomic-cache package.
 * Provides distributed lock stampede protection, granular tags, and query normalization.
 */
trait Cacheable
{
    use HasAtomicCache;

    /**
     * Backward-compatible helper to flush custom cache tags.
     *
     * @param string|array $tags Tag name or array of tag names to flush.
     */
    public function flushCacheTags(string|array $tags): void
    {
        AtomicCache::clearTags((array) $tags);
    }

    /**
     * Backward-compatible helper to clear table / collection cache.
     *
     * @param string|null $tableName Explicit table or tag name to flush.
     */
    public function clearTableCache(?string $tableName = null): void
    {
        $tag = $tableName ?? $this->getCacheResource();
        AtomicCache::clearCollection($tag);
    }
}

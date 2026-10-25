<?php

namespace App\Traits;

use KaziOmar\AtomicCache\Traits\AutoClearsCache as PackageAutoClearsCache;

/**
 * Trait AutoClearsCache
 *
 * Uses the official thekaziomar/laravel-atomic-cache package.
 * Granular cache tags and related tags are invalidated on model save/delete inside DB::afterCommit.
 */
trait AutoClearsCache
{
    use PackageAutoClearsCache;
}

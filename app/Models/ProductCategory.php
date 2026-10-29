<?php

namespace App\Models;

use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory, AutoClearsCache, HasUploadPurge;

    /**
     * HasUploadPurge: Automatically cleans up old category icons from storage disk
     * when the icon is updated or when the category is deleted.
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Cache tags of related models to invalidate on change.
     */
    public function getRelatedCacheTags(): array
    {
        return ['products'];
    }
}
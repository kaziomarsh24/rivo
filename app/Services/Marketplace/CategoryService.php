<?php

namespace App\Services\Marketplace;

use App\Models\ProductCategory;
use App\Services\BaseService;
use KaziOmar\SmartUpload\Facades\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;

class CategoryService extends BaseService
{
    protected string $modelClass = ProductCategory::class;

    protected function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('is_active'),
            AllowedFilter::partial('name'),
            AllowedFilter::partial('slug'),
        ];
    }

    protected function getAllowedIncludes(): array
    {
        return ['products'];
    }

    protected function getAllowedSorts(): array
    {
        return ['order_column', 'name', 'created_at', 'id'];
    }

    /**
     * Get all active root categories with their child categories.
     * Cached with the 'categories' tag.
     */
    public function getActiveCategories()
    {
        return ProductCategory::query()
            ->where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('order_column')
            ->get();
    }

    /**
     * Create a new category with optional icon upload.
     * (For Admin Portal & Management)
     */
    public function createCategory(array $data, ?Request $request = null): ProductCategory
    {
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request && $request->hasFile('icon')) {
            $data['icon'] = Upload::file($request->file('icon'))
                ->scaleDown(200, 200)
                ->keepOriginalFormat()
                ->save('categories')
                ->path;
        }

        /** @var ProductCategory $category */
        $category = $this->create($data);

        return $category;
    }

    /**
     * Update an existing category with icon file replacement.
     * Note: Old icon is automatically purged by ProductCategory's HasUploadPurge trait on model update.
     */
    public function updateCategory(ProductCategory $category, array $data, ?Request $request = null): ProductCategory
    {
        if ($request && $request->hasFile('icon')) {
            $data['icon'] = Upload::file($request->file('icon'))
                ->scaleDown(200, 200)
                ->keepOriginalFormat()
                ->save('categories')
                ->path;
        }

        /** @var ProductCategory $updated */
        $updated = $this->update($category->id, $data);

        return $updated;
    }

    /**
     * Delete a category and clean up its stored icon file.
     * Note: Icon is automatically purged by ProductCategory's HasUploadPurge trait on model delete.
     */
    public function deleteCategory(ProductCategory $category): bool
    {
        return (bool) $this->delete($category->id);
    }
}

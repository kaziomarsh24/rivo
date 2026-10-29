<?php

namespace App\Models;

use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDocument extends Model
{
    use HasFactory, AutoClearsCache, HasUploadPurge;

    /**
     * HasUploadPurge: Automatically cleans up physical KYC document files from storage disk
     * when the document record is deleted.
     */
    protected array $purgeableFiles = ['file_path'];

    protected $fillable = [
        'store_id',
        'type',
        'file_path',
        'original_name',
        'status',
        'rejection_reason',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
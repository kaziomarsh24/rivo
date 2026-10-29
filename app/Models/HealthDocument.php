<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;

class HealthDocument extends Model
{
    use HasFactory, HasUploadPurge;

    /**
     * HasUploadPurge: Automatically cleans up physical health document files from storage disk
     * when the document record is deleted.
     */
    protected array $purgeableFiles = ['file_path'];

    protected $fillable = [
        'pet_id',
        'title',
        'file_path',
        'type',
        'file_size_bytes'
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }
}

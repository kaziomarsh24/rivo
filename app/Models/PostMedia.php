<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;

class PostMedia extends Model
{
    /**
     * HasUploadPurge: Automatically cleans up physical media files from storage disk
     * when this model record is deleted, or when these file paths are updated/replaced.
     * Prevents orphan files and storage leaks.
     */
    use HasUploadPurge;

    /**
     * File attributes that should be purged automatically by HasUploadPurge.
     */
    protected array $purgeableFiles = ['media_path', 'thumbnail_path'];

    protected $fillable = ['post_id', 'media_path', 'media_type', 'thumbnail_path', 'order'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}

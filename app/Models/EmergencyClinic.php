<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;

class EmergencyClinic extends Model
{
    use HasFactory, AutoClearsCache, HasUploadPurge;

    protected $fillable = [
        'name',
        'slug',
        'phone_number',
        'emergency_hotline',
        'email',
        'street_address',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'is_open_24_7',
        'open_hours_text',
        'rating',
        'reviews_count',
        'services',
        'photo',
        'website',
        'is_verified',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_open_24_7' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'float',
        'reviews_count' => 'integer',
        'services' => 'array',
    ];

    /**
     * File fields for SmartUpload auto-purge on delete or update.
     */
    protected array $purgeableFiles = ['photo'];

    /**
     * Scope to only include active clinics.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter and sort clinics by geographic distance using Haversine formula (in kilometers).
     */
    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusKm = 50.0): Builder
    {
        $haversine = "(6371 * acos(least(1.0, greatest(-1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))))";

        return $query
            ->select('emergency_clinics.*')
            ->selectRaw("{$haversine} AS distance_km", [$latitude, $longitude, $latitude])
            ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderBy('distance_km', 'asc');
    }

    /**
     * Get formatted photo URL using SmartUpload helper.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return upload_url($this->photo);
    }

    /**
     * Get Google Maps navigation URL.
     */
    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://www.google.com/maps/dir/?api=1&destination={$this->latitude},{$this->longitude}";
    }
}

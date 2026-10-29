<?php

namespace App\Models;

use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory, AutoClearsCache;

    protected $fillable = [
        'user_id',
        'pet_id',
        'title',
        'type',
        'vet_name',
        'location',
        'latitude',
        'longitude',
        'datetime',
        'reminder',
        'status',
    ];

    protected array $cacheParents = ['pet', 'user'];

    protected $casts = [
        'datetime' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AppointmentNote::class);
    }
}

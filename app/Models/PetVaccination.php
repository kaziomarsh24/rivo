<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use KaziOmar\AtomicCache\Traits\AutoClearsCache;
use KaziOmar\SmartUpload\Traits\HasUploadPurge;

class PetVaccination extends Model
{
    use HasFactory, AutoClearsCache, HasUploadPurge;

    protected $fillable = [
        'pet_id',
        'name',
        'vaccine_type',
        'administered_at',
        'due_at',
        'clinic_name',
        'veterinarian_name',
        'batch_number',
        'certificate_file',
        'notes',
    ];

    protected $casts = [
        'administered_at' => 'date',
        'due_at' => 'date',
    ];

    /**
     * File fields for SmartUpload auto-purge on delete or update.
     */
    protected array $purgeableFiles = ['certificate_file'];

    /**
     * Cache invalidation parents.
     */
    public array $cacheParents = ['pet'];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    /**
     * Compute real-time status: up_to_date, due_soon, or overdue.
     */
    public function getStatusAttribute(): string
    {
        $today = Carbon::today();
        $dueDate = Carbon::parse($this->due_at)->startOfDay();

        if ($dueDate->lt($today)) {
            return 'overdue';
        }

        if ($dueDate->lte($today->copy()->addDays(30))) {
            return 'due_soon';
        }

        return 'up_to_date';
    }

    /**
     * Status badge metadata matching Figma UI tags.
     */
    public function getStatusBadgeAttribute(): array
    {
        $status = $this->status;
        $today = Carbon::today();
        $dueDate = Carbon::parse($this->due_at)->startOfDay();
        $daysDiff = (int) $today->diffInDays($dueDate, false);

        switch ($status) {
            case 'overdue':
                $daysOverdue = abs($daysDiff);
                return [
                    'status' => 'overdue',
                    'label' => 'Overdue',
                    'color' => '#EF4444', // Red
                    'background_color' => '#FEE2E2',
                    'icon' => 'alert_circle',
                    'description' => "Overdue by {$daysOverdue} " . ($daysOverdue === 1 ? 'day' : 'days'),
                ];
            case 'due_soon':
                return [
                    'status' => 'due_soon',
                    'label' => 'Due Soon',
                    'color' => '#F59E0B', // Orange / Amber
                    'background_color' => '#FEF3C7',
                    'icon' => 'clock',
                    'description' => "Due in {$daysDiff} " . ($daysDiff === 1 ? 'day' : 'days'),
                ];
            case 'up_to_date':
            default:
                return [
                    'status' => 'up_to_date',
                    'label' => 'Up to Date',
                    'color' => '#10B981', // Green / Emerald
                    'background_color' => '#D1FAE5',
                    'icon' => 'check_circle',
                    'description' => 'Valid and fully protected',
                ];
        }
    }

    /**
     * Days remaining until due (positive) or overdue (negative).
     */
    public function getDaysRemainingAttribute(): int
    {
        return (int) Carbon::today()->diffInDays(Carbon::parse($this->due_at)->startOfDay(), false);
    }

    /**
     * Format certificate URL with SmartUpload helper.
     */
    public function getCertificateUrlAttribute(): ?string
    {
        return upload_url($this->certificate_file);
    }
}

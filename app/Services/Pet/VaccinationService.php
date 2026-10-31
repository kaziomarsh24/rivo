<?php

namespace App\Services\Pet;

use App\Models\Pet;
use App\Models\PetVaccination;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use KaziOmar\AtomicCache\Facades\AtomicCache;
use KaziOmar\SmartUpload\Facades\Upload;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VaccinationService extends BaseService
{
    protected string $modelClass = PetVaccination::class;

    protected function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('name'),
            AllowedFilter::exact('vaccine_type'),
            AllowedFilter::partial('clinic_name'),
            AllowedFilter::partial('veterinarian_name'),
            AllowedFilter::callback('status', function ($query, $value) {
                $today = Carbon::today()->toDateString();
                $dueSoonThreshold = Carbon::today()->addDays(30)->toDateString();
                if ($value === 'overdue') {
                    $query->where('due_at', '<', $today);
                } elseif ($value === 'due_soon') {
                    $query->where('due_at', '>=', $today)->where('due_at', '<=', $dueSoonThreshold);
                } elseif ($value === 'up_to_date') {
                    $query->where('due_at', '>', $dueSoonThreshold);
                }
            }),
        ];
    }

    protected function getAllowedIncludes(): array
    {
        return ['pet'];
    }

    protected function getAllowedSorts(): array
    {
        return ['due_at', 'administered_at', 'name', 'created_at', 'id'];
    }

    /**
     * Get all vaccinations for a pet.
     */
    public function getVaccinations(Pet $pet): Collection
    {
        $queryString = request()->getQueryString() ?? 'default';
        $cacheKey = "pet:{$pet->id}:vaccinations:" . md5($queryString);

        return $this->rememberCache(
            $cacheKey,
            function () use ($pet) {
                return QueryBuilder::for(PetVaccination::where('pet_id', $pet->id))
                    ->allowedFilters($this->getAllowedFilters())
                    ->allowedSorts($this->getAllowedSorts())
                    ->defaultSort('due_at')
                    ->get();
            },
            ttl: 3600,
            tags: ['pet_vaccinations', "pet_{$pet->id}"],
            lock: true
        );
    }

    /**
     * Get vaccination summary for a pet matching Figma UI cards.
     */
    public function getVaccinationSummary(Pet $pet): array
    {
        $cacheKey = "pet:{$pet->id}:vaccinations:summary";

        return $this->rememberCache(
            $cacheKey,
            function () use ($pet) {
                $vaccinations = PetVaccination::where('pet_id', $pet->id)
                    ->orderBy('due_at', 'asc')
                    ->get();

                $total = $vaccinations->count();
                $upToDate = 0;
                $dueSoon = 0;
                $overdue = 0;

                /** @var PetVaccination|null $nextDue */
                $nextDue = null;

                foreach ($vaccinations as $v) {
                    $status = $v->status;
                    if ($status === 'overdue') {
                        $overdue++;
                    } elseif ($status === 'due_soon') {
                        $dueSoon++;
                        if (!$nextDue) {
                            $nextDue = $v;
                        }
                    } else {
                        $upToDate++;
                        if (!$nextDue) {
                            $nextDue = $v;
                        }
                    }
                }

                $overallStatus = 'up_to_date';
                $overallBadge = [
                    'label' => 'All Up to Date',
                    'color' => '#10B981',
                    'background_color' => '#D1FAE5',
                    'icon' => 'check_circle',
                ];

                if ($overdue > 0) {
                    $overallStatus = 'attention_needed';
                    $overallBadge = [
                        'label' => "{$overdue} " . ($overdue === 1 ? 'Vaccine Overdue' : 'Vaccines Overdue'),
                        'color' => '#EF4444',
                        'background_color' => '#FEE2E2',
                        'icon' => 'alert_circle',
                    ];
                } elseif ($dueSoon > 0) {
                    $overallStatus = 'due_soon';
                    $overallBadge = [
                        'label' => "{$dueSoon} " . ($dueSoon === 1 ? 'Vaccine Due Soon' : 'Vaccines Due Soon'),
                        'color' => '#F59E0B',
                        'background_color' => '#FEF3C7',
                        'icon' => 'clock',
                    ];
                }

                return [
                    'pet_id' => $pet->id,
                    'total_count' => $total,
                    'up_to_date_count' => $upToDate,
                    'due_soon_count' => $dueSoon,
                    'overdue_count' => $overdue,
                    'overall_status' => $overallStatus,
                    'overall_badge' => $overallBadge,
                    'next_due_vaccine' => $nextDue ? [
                        'id' => $nextDue->id,
                        'name' => $nextDue->name,
                        'due_at' => Carbon::parse($nextDue->due_at)->format('Y-m-d'),
                        'due_at_formatted' => Carbon::parse($nextDue->due_at)->format('d M Y'),
                        'days_remaining' => $nextDue->days_remaining,
                        'status' => $nextDue->status,
                        'status_badge' => $nextDue->status_badge,
                    ] : null,
                ];
            },
            ttl: 3600,
            tags: ['pet_vaccinations', "pet_{$pet->id}"],
            lock: true
        );
    }

    /**
     * Create a new vaccination record.
     */
    public function createVaccination(Pet $pet, array $data, ?UploadedFile $certificate = null): PetVaccination
    {
        if ($certificate) {
            $data['certificate_file'] = Upload::file($certificate)
                ->keepOriginalFormat()
                ->save('vaccinations/' . $pet->id)
                ->path;
        }

        $data['pet_id'] = $pet->id;
        $vaccination = PetVaccination::create($data);

        $this->syncPetVaccinatedStatus($pet);
        $this->clearPetVaccinationCache($pet);

        return $vaccination;
    }

    /**
     * Update an existing vaccination record.
     */
    public function updateVaccination(PetVaccination $vaccination, array $data, ?UploadedFile $certificate = null): PetVaccination
    {
        if ($certificate) {
            $data['certificate_file'] = Upload::file($certificate)
                ->keepOriginalFormat()
                ->save('vaccinations/' . $vaccination->pet_id)
                ->path;
        }

        $vaccination->update($data);

        $pet = $vaccination->pet;
        if ($pet) {
            $this->syncPetVaccinatedStatus($pet);
            $this->clearPetVaccinationCache($pet);
        }

        return $vaccination->fresh();
    }

    /**
     * Delete a vaccination record.
     */
    public function deleteVaccination(PetVaccination $vaccination): bool
    {
        $pet = $vaccination->pet;
        $deleted = $vaccination->delete();

        if ($pet) {
            $this->syncPetVaccinatedStatus($pet);
            $this->clearPetVaccinationCache($pet);
        }

        return (bool) $deleted;
    }

    /**
     * Synchronize pet's is_vaccinated boolean status based on active vaccinations.
     */
    public function syncPetVaccinatedStatus(Pet $pet): void
    {
        $hasActiveVaccines = $pet->vaccinations()
            ->where('due_at', '>=', Carbon::today())
            ->exists();

        $pet->update(['is_vaccinated' => $hasActiveVaccines]);
    }

    /**
     * Clear atomic cache for the pet's vaccination records.
     */
    public function clearPetVaccinationCache(Pet $pet): void
    {
        AtomicCache::clearTags(['pet_vaccinations', "pet_{$pet->id}"]);
    }
}


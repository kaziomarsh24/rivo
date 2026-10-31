<?php

namespace App\Services\Pet;

use App\Models\Pet;
use App\Models\HealthDocument;
use App\Models\PetWeightLog;
use App\Services\Ai\RivoAiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use KaziOmar\SmartUpload\Facades\Upload;

class HealthVaultService
{
    protected $aiService;

    public function __construct(RivoAiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function getDashboard(Pet $pet): array
    {
        // 1. Calculate Health Score
        $healthScoreInfo = $this->calculateHealthScore($pet);

        // 2. Get Upcoming Vaccinations & Summary
        $vaccinationSummary = app(VaccinationService::class)->getVaccinationSummary($pet);
        $vaccinationRecords = $pet->vaccinations()->orderBy('due_at', 'asc')->get();

        // Get upcoming appointments for AI insights
        $upcomingVaccineAppointments = $pet->appointments()
            ->where('type', 'vaccination')
            ->where('datetime', '>=', now())
            ->orderBy('datetime', 'asc')
            ->take(2)
            ->get();

        if ($vaccinationRecords->isNotEmpty()) {
            $vaccinationsData = [
                'total_count' => $vaccinationSummary['total_count'],
                'up_to_date_count' => $vaccinationSummary['up_to_date_count'],
                'due_soon_count' => $vaccinationSummary['due_soon_count'],
                'overdue_count' => $vaccinationSummary['overdue_count'],
                'overall_status' => $vaccinationSummary['overall_status'],
                'overall_badge' => $vaccinationSummary['overall_badge'],
                'upcoming_count' => $vaccinationSummary['due_soon_count'] + $vaccinationSummary['overdue_count'],
                'upcoming_list' => $vaccinationRecords->take(2)->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'title' => $v->name,
                        'date' => Carbon::parse($v->due_at)->format('d M Y'),
                        'days_left' => $v->days_remaining,
                        'status' => $v->status,
                        'status_badge' => $v->status_badge,
                    ];
                })->values(),
            ];
        } else {
            $vaccinationsData = [
                'total_count' => 0,
                'up_to_date_count' => 0,
                'due_soon_count' => 0,
                'overdue_count' => 0,
                'overall_status' => 'no_records',
                'overall_badge' => [
                    'label' => 'No Records',
                    'color' => '#6B7280',
                    'background_color' => '#F3F4F6',
                    'icon' => 'shield_question',
                ],
                'upcoming_count' => $upcomingVaccineAppointments->count(),
                'upcoming_list' => $upcomingVaccineAppointments->map(function ($v) {
                    return [
                        'id' => $v->id,
                        'title' => $v->title,
                        'date' => $v->datetime->format('d M Y'),
                        'days_left' => floor(now()->diffInDays($v->datetime)),
                        'status' => 'scheduled',
                        'status_badge' => [
                            'label' => 'Scheduled',
                            'color' => '#3B82F6',
                            'background_color' => '#DBEAFE',
                            'icon' => 'calendar',
                        ],
                    ];
                })->values(),
            ];
        }

        // 3. Get Recent Weight
        $currentWeight = $pet->weightLogs()->latest('logged_at')->first();

        // 4. Get Documents Count
        $documentsCount = $pet->healthDocuments()->count();

        // 5. Get AI Insight
        $aiInsight = $this->aiService->getAppointmentInsights($upcomingVaccineAppointments);

        return [
            'health_score' => $healthScoreInfo,
            'vaccinations' => $vaccinationsData,
            'weight_tracking' => [
                'current_weight_kg' => $currentWeight ? $currentWeight->weight_kg : $pet->weight,
                'last_updated' => $currentWeight ? $currentWeight->logged_at->diffForHumans() : 'No recent logs'
            ],
            'health_documents' => [
                'total_files' => $documentsCount,
            ],
            'ai_insight' => $aiInsight
        ];
    }

    public function uploadDocument(Pet $pet, $file, string $title): HealthDocument
    {
        // Check Free limit
        if ($pet->user->plan->name === 'Free' && $pet->healthDocuments()->count() >= 5) {
            throw new \Exception('Free plan limit reached. Upgrade to Rivo Plus to store unlimited documents.', 403);
        }

        $path = Upload::file($file)->keepOriginalFormat()->save('health_documents/' . $pet->id)->path;
        
        $extension = strtolower($file->getClientOriginalExtension());
        $type = in_array($extension, ['jpg', 'jpeg', 'png']) ? 'image' : 'pdf';

        return $pet->healthDocuments()->create([
            'title' => $title,
            'file_path' => $path,
            'type' => $type,
            'file_size_bytes' => $file->getSize()
        ]);
    }

    public function deleteDocument(HealthDocument $document): bool
    {
        return $document->delete();
    }

    public function logWeight(Pet $pet, float $weightKg): PetWeightLog
    {
        $log = $pet->weightLogs()->create([
            'weight_kg' => $weightKg,
            'logged_at' => now(),
        ]);

        // Update pet's main weight field
        $pet->update(['weight' => $weightKg]);

        return $log;
    }

    private function calculateHealthScore(Pet $pet): array
    {
        // Care Task Completion (40 points)
        $tasksCount = $pet->careTasks()->count();
        $logsLast7Days = $pet->careTaskLogs()
            ->where('completed_at', '>=', now()->subDays(7))
            ->count();
        
        $expectedTasksLast7Days = $tasksCount * 7;
        $careScore = 40;
        if ($expectedTasksLast7Days > 0) {
            $careScore = min(40, round(($logsLast7Days / $expectedTasksLast7Days) * 40));
        }

        // Walk Activity (30 points)
        $walkStat = $pet->walkStats()->first();
        $walkScore = 0;
        if ($walkStat && $walkStat->current_streak_days > 0) {
            $walkScore = min(30, $walkStat->current_streak_days * 5); // 5 points per streak day up to 30
        }

        // Vaccinations (20 points)
        if ($pet->vaccinations()->exists()) {
            $hasOverdue = $pet->vaccinations()->where('due_at', '<', now()->startOfDay())->exists();
            $vaccineScore = $hasOverdue ? 0 : 20;
        } else {
            $hasMissedVaccine = $pet->appointments()
                ->where('type', 'vaccination')
                ->where('datetime', '<', now())
                ->where('status', 'scheduled')
                ->exists();
            $vaccineScore = $hasMissedVaccine ? 0 : 20;
        }

        // Weight (10 points)
        $hasRecentWeight = $pet->weightLogs()
            ->where('logged_at', '>=', now()->subDays(30))
            ->exists();
        $weightScore = $hasRecentWeight ? 10 : 0;

        $totalScore = $careScore + $walkScore + $vaccineScore + $weightScore;

        // Status text
        $status = 'Healthy';
        if ($totalScore < 50) $status = 'Needs Attention';
        elseif ($totalScore < 80) $status = 'Good';

        return [
            'score' => $totalScore,
            'out_of' => 100,
            'status' => $status,
            'last_updated' => now()->diffForHumans(),
            'breakdown' => [
                'care' => $careScore,
                'activity' => $walkScore,
                'vaccines' => $vaccineScore,
                'weight' => $weightScore,
            ]
        ];
    }
}


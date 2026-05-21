<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Pages;

use App\Filament\Resources\RetreatActivityAttendances\RetreatActivityAttendanceResource;
use App\Models\RetreatActivityAttendance;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatParticipant;
use App\Services\RetreatAtelierAuthorizationService;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateRetreatActivityAttendance extends CreateRecord
{
    protected static string $resource = RetreatActivityAttendanceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['recorded_by'] ??= Auth::id();
        $data['check_in_at'] = $this->mergeActivityDateWithTime($data['check_in_at'] ?? null, $data['activity_plan_id'] ?? null);
        $data['check_out_at'] = $this->mergeActivityDateWithTime($data['check_out_at'] ?? null, $data['activity_plan_id'] ?? null);

        $participantIds = (array) ($data['participant_ids'] ?? $this->data['participant_ids'] ?? []);
        $singleParticipantId = $data['participant_id'] ?? null;

        if (($singleParticipantId !== null) && ($singleParticipantId !== '')) {
            $participantIds[] = (int) $singleParticipantId;
        }

        $participantIds = array_values(array_unique(array_filter($participantIds)));

        if (count($participantIds) === 0) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Selectionne au moins un participant avant de valider le pointage.',
            ]);
        }

        $first = null;

        foreach ($participantIds as $participantId) {
            $participant = RetreatParticipant::query()->with('atelier')->find($participantId);
            if ($participant && ! app(RetreatAtelierAuthorizationService::class)->canManageParticipant(Auth::user(), $participant)) {
                throw ValidationException::withMessages([
                    'participant_ids' => 'Vous n’êtes pas autorisé à pointer la présence pour certains participants (atelier non géré).',
                ]);
            }

            $record = RetreatActivityAttendance::query()->updateOrCreate(
                [
                    'activity_plan_id' => $data['activity_plan_id'],
                    'participant_id' => $participantId,
                ],
                [
                    'status' => $data['status'],
                    'check_in_at' => $data['check_in_at'] ?? null,
                    'check_out_at' => $data['check_out_at'] ?? null,
                    'scan_source' => $data['scan_source'],
                    'recorded_by' => $data['recorded_by'] ?? null,
                    'note' => $data['note'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                ]
            );

            $first ??= $record;
        }

        return $first;
    }

    private function mergeActivityDateWithTime(?string $time, mixed $activityPlanId): ?string
    {
        if (blank($time)) {
            return null;
        }

        if (str_contains($time, '-')) {
            return Carbon::parse($time)->format('Y-m-d H:i:s');
        }

        $activityPlan = RetreatActivityPlan::query()
            ->with('session')
            ->find($activityPlanId);

        $date = $activityPlan?->session?->start_at?->toDateString() ?? now()->toDateString();

        return Carbon::parse($date.' '.$time)->format('Y-m-d H:i:s');
    }
}

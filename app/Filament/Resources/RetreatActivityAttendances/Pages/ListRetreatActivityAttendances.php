<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Pages;

use App\Filament\Resources\RetreatActivityAttendances\RetreatActivityAttendanceResource;
use App\Filament\Resources\RetreatActivityAttendances\Widgets\RetreatActivityAttendancesStats;
use App\Models\RetreatActivityAttendance;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatParticipant;
use App\Services\RetreatAtelierAuthorizationService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ListRetreatActivityAttendances extends ListRecords
{
    protected static string $resource = RetreatActivityAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pointage_atelier')
                ->label('Pointage par atelier')
                ->icon('heroicon-o-user-group')
                ->url(RetreatActivityAttendanceResource::getUrl('atelier-pointage')),
            CreateAction::make()
                ->modal()
                ->modalWidth(Width::SevenExtraLarge)
                ->modalAlignment(Alignment::Center)
                ->using(fn (array $data): Model => $this->createAttendances($data)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatActivityAttendancesStats::class,
        ];
    }

    private function createAttendances(array $data): Model
    {
        $participantIds = array_values(array_unique(array_filter((array) ($data['participant_ids'] ?? []))));

        if (count($participantIds) === 0) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Selectionne au moins un participant avant de valider le pointage.',
            ]);
        }

        $data['recorded_by'] ??= Auth::id();
        $data['check_in_at'] = $this->mergeActivityDateWithTime($data['check_in_at'] ?? null, $data['activity_plan_id'] ?? null);
        $data['check_out_at'] = $this->mergeActivityDateWithTime($data['check_out_at'] ?? null, $data['activity_plan_id'] ?? null);

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

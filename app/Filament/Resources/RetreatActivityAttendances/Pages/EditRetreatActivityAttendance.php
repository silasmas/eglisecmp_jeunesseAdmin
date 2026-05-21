<?php

namespace App\Filament\Resources\RetreatActivityAttendances\Pages;

use App\Filament\Resources\RetreatActivityAttendances\RetreatActivityAttendanceResource;
use App\Models\RetreatActivityPlan;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditRetreatActivityAttendance extends EditRecord
{
    protected static string $resource = RetreatActivityAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['recorded_by'] ??= Auth::id();
        $data['check_in_at'] = $this->mergeActivityDateWithTime($data['check_in_at'] ?? null, $data['activity_plan_id'] ?? null);
        $data['check_out_at'] = $this->mergeActivityDateWithTime($data['check_out_at'] ?? null, $data['activity_plan_id'] ?? null);

        return $data;
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

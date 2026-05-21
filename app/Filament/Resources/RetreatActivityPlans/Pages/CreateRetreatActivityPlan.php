<?php

namespace App\Filament\Resources\RetreatActivityPlans\Pages;

use App\Filament\Resources\RetreatActivityPlans\RetreatActivityPlanResource;
use App\Models\RetreatActivityPlan;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRetreatActivityPlan extends CreateRecord
{
    protected static string $resource = RetreatActivityPlanResource::class;

    protected bool $hasSimilarActivity = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->hasSimilarActivity = RetreatActivityPlan::query()
            ->where('session_id', $data['session_id'] ?? null)
            ->where('title', $data['title'] ?? null)
            ->where('activity_type', $data['activity_type'] ?? null)
            ->where('starts_at', $data['starts_at'] ?? null)
            ->where('ends_at', $data['ends_at'] ?? null)
            ->exists();

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->hasSimilarActivity) {
            return;
        }

        Notification::make()
            ->title('Activite similaire detectee')
            ->body("Une activite avec la meme session, titre, type, debut et fin existe deja. L'enregistrement a ete conserve.")
            ->warning()
            ->send();
    }
}

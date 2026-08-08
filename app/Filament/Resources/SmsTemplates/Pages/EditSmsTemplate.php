<?php

namespace App\Filament\Resources\SmsTemplates\Pages;

use App\Filament\Resources\SmsTemplates\SmsTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d’un modèle SMS.
 */
class EditSmsTemplate extends EditRecord
{
    protected static string $resource = SmsTemplateResource::class;

    /**
     * @return array<int, DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

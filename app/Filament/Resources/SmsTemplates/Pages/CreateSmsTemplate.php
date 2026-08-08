<?php

namespace App\Filament\Resources\SmsTemplates\Pages;

use App\Filament\Resources\SmsTemplates\SmsTemplateResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d’un modèle SMS.
 */
class CreateSmsTemplate extends CreateRecord
{
    protected static string $resource = SmsTemplateResource::class;
}

<?php

namespace App\Filament\Resources\ChurchEvents\Pages;

use App\Enums\EventAccessAuthMode;
use App\Filament\Resources\ChurchEvents\ChurchEventResource;
use Filament\Resources\Pages\CreateRecord;
use UnitEnum;

class CreateChurchEvent extends CreateRecord
{
    protected static string $resource = ChurchEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['access_auth_mode'] = $this->normalizeEnumValue(
            $data['access_auth_mode'] ?? null,
            EventAccessAuthMode::Password->value
        );
        $data['access_otp_channel'] = $this->normalizeEnumValue($data['access_otp_channel'] ?? null);

        return $data;
    }

    protected function normalizeEnumValue(mixed $value, ?string $default = null): ?string
    {
        if ($value instanceof UnitEnum) {
            return $value instanceof \BackedEnum ? (string) $value->value : $value->name;
        }

        return blank($value) ? $default : (string) $value;
    }
}

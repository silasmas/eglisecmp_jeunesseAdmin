<?php

namespace App\Filament\Resources\ChurchEvents\Pages;

use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use App\Filament\Resources\ChurchEvents\ChurchEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use UnitEnum;

class EditChurchEvent extends EditRecord
{
    protected static string $resource = ChurchEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

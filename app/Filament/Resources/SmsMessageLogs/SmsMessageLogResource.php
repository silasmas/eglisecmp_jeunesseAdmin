<?php

namespace App\Filament\Resources\SmsMessageLogs;

use App\Filament\Resources\SmsMessageLogs\Pages\ListSmsMessageLogs;
use App\Filament\Resources\SmsMessageLogs\Tables\SmsMessageLogsTable;
use App\Models\SmsMessageLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class SmsMessageLogResource extends Resource
{
    protected static ?string $model = SmsMessageLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Historique SMS';

    protected static string|UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?string $modelLabel = 'Historique SMS';

    protected static ?string $pluralModelLabel = 'Historique SMS';

    protected static ?int $navigationSort = 11;

    public static function table(Table $table): Table
    {
        return SmsMessageLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsMessageLogs::route('/'),
        ];
    }
}

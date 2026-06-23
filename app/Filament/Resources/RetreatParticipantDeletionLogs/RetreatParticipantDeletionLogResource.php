<?php

namespace App\Filament\Resources\RetreatParticipantDeletionLogs;

use App\Filament\Resources\RetreatParticipantDeletionLogs\Pages\ListRetreatParticipantDeletionLogs;
use App\Filament\Resources\RetreatParticipantDeletionLogs\Pages\ViewRetreatParticipantDeletionLog;
use App\Filament\Resources\RetreatParticipantDeletionLogs\Schemas\RetreatParticipantDeletionLogInfolist;
use App\Filament\Resources\RetreatParticipantDeletionLogs\Tables\RetreatParticipantDeletionLogsTable;
use App\Models\RetreatParticipantDeletionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatParticipantDeletionLogResource extends Resource
{
    protected static ?string $model = RetreatParticipantDeletionLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box-x-mark';

    protected static ?string $navigationLabel = 'Historique suppressions';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'Suppression participant';

    protected static ?string $pluralModelLabel = 'Historique suppressions participants';

    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return RetreatParticipantDeletionLogInfolist::configure($schema);
    }

    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return RetreatParticipantDeletionLogsTable::configure($table);
    }

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRetreatParticipantDeletionLogs::route('/'),
            'view' => ViewRetreatParticipantDeletionLog::route('/{record}'),
        ];
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return false;
    }
}

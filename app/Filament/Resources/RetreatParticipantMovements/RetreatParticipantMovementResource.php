<?php

namespace App\Filament\Resources\RetreatParticipantMovements;

use App\Filament\Resources\RetreatParticipantMovements\Pages\CreateRetreatParticipantMovement;
use App\Filament\Resources\RetreatParticipantMovements\Pages\EditRetreatParticipantMovement;
use App\Filament\Resources\RetreatParticipantMovements\Pages\ListRetreatParticipantMovements;
use App\Filament\Resources\RetreatParticipantMovements\Pages\ManageAtelierParticipantMovements;
use App\Filament\Resources\RetreatParticipantMovements\Pages\ViewRetreatParticipantMovement;
use App\Filament\Resources\RetreatParticipantMovements\Schemas\RetreatParticipantMovementForm;
use App\Filament\Resources\RetreatParticipantMovements\Schemas\RetreatParticipantMovementInfolist;
use App\Filament\Resources\RetreatParticipantMovements\Tables\RetreatParticipantMovementsTable;
use App\Models\RetreatParticipantMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatParticipantMovementResource extends Resource
{
    protected static ?string $model = RetreatParticipantMovement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Mouvements participants';

    protected static string|UnitEnum|null $navigationGroup = 'Operations terrain';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'Mouvement participant';

    protected static ?string $pluralModelLabel = 'Mouvements participants';

    public static function form(Schema $schema): Schema
    {
        return RetreatParticipantMovementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatParticipantMovementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatParticipantMovementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRetreatParticipantMovements::route('/'),
            'atelier-mouvements' => ManageAtelierParticipantMovements::route('/atelier-mouvements'),
            'create' => CreateRetreatParticipantMovement::route('/create'),
            'view' => ViewRetreatParticipantMovement::route('/{record}'),
            'edit' => EditRetreatParticipantMovement::route('/{record}/edit'),
        ];
    }
}

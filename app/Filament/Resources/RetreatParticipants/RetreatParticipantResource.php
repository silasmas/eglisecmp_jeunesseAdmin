<?php

namespace App\Filament\Resources\RetreatParticipants;

use App\Filament\Resources\RetreatParticipants\Pages\CreateRetreatParticipant;
use App\Filament\Resources\RetreatParticipants\Pages\EditRetreatParticipant;
use App\Filament\Resources\RetreatParticipants\Pages\ListRetreatParticipants;
use App\Filament\Resources\RetreatParticipants\Pages\ViewRetreatParticipant;
use App\Filament\Resources\RetreatParticipants\Schemas\RetreatParticipantForm;
use App\Filament\Resources\RetreatParticipants\Schemas\RetreatParticipantInfolist;
use App\Filament\Resources\RetreatParticipants\Tables\RetreatParticipantsTable;
use App\Models\RetreatParticipant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatParticipantResource extends Resource
{
    protected static ?string $model = RetreatParticipant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Participants retraite';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?string $modelLabel = 'Participant retraite';

    protected static ?string $pluralModelLabel = 'Participants retraite';

    public static function form(Schema $schema): Schema
    {
        return RetreatParticipantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatParticipantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatParticipantsTable::configure($table);
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
            'index' => ListRetreatParticipants::route('/'),
            'create' => CreateRetreatParticipant::route('/create'),
            'view' => ViewRetreatParticipant::route('/{record}'),
            'edit' => EditRetreatParticipant::route('/{record}/edit'),
        ];
    }
}

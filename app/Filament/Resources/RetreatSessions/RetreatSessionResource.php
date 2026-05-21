<?php

namespace App\Filament\Resources\RetreatSessions;

use App\Filament\Resources\RetreatSessions\Pages\CreateRetreatSession;
use App\Filament\Resources\RetreatSessions\Pages\EditRetreatSession;
use App\Filament\Resources\RetreatSessions\Pages\ListRetreatSessions;
use App\Filament\Resources\RetreatSessions\Pages\ViewRetreatSession;
use App\Filament\Resources\RetreatSessions\Schemas\RetreatSessionForm;
use App\Filament\Resources\RetreatSessions\Schemas\RetreatSessionInfolist;
use App\Filament\Resources\RetreatSessions\Tables\RetreatSessionsTable;
use App\Models\RetreatSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatSessionResource extends Resource
{
    protected static ?string $model = RetreatSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Sessions retraite';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation retraite';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Session retraite';

    protected static ?string $pluralModelLabel = 'Sessions retraite';

    public static function form(Schema $schema): Schema
    {
        return RetreatSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatSessionsTable::configure($table);
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
            'index' => ListRetreatSessions::route('/'),
            'create' => CreateRetreatSession::route('/create'),
            'view' => ViewRetreatSession::route('/{record}'),
            'edit' => EditRetreatSession::route('/{record}/edit'),
        ];
    }
}

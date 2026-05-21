<?php

namespace App\Filament\Resources\ChurchEvents;

use App\Filament\Resources\ChurchEvents\Pages\CreateChurchEvent;
use App\Filament\Resources\ChurchEvents\Pages\EditChurchEvent;
use App\Filament\Resources\ChurchEvents\Pages\ListChurchEvents;
use App\Filament\Resources\ChurchEvents\Pages\ViewChurchEvent;
use App\Filament\Resources\ChurchEvents\Schemas\ChurchEventForm;
use App\Filament\Resources\ChurchEvents\Schemas\ChurchEventInfolist;
use App\Filament\Resources\ChurchEvents\Tables\ChurchEventsTable;
use App\Models\ChurchEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

class ChurchEventResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = ChurchEvent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Evenements';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?string $modelLabel = 'Evenement';

    protected static ?string $pluralModelLabel = 'Evenements';

    public static function form(Schema $schema): Schema
    {
        return ChurchEventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChurchEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChurchEventsTable::configure($table);
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
            'index' => ListChurchEvents::route('/'),
            'create' => CreateChurchEvent::route('/create'),
            'view' => ViewChurchEvent::route('/{record}'),
            'edit' => EditChurchEvent::route('/{record}/edit'),
        ];
    }
}

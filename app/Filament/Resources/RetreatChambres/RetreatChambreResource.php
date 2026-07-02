<?php

namespace App\Filament\Resources\RetreatChambres;

use App\Filament\Resources\RetreatChambres\Pages\CreateRetreatChambre;
use App\Filament\Resources\RetreatChambres\Pages\EditRetreatChambre;
use App\Filament\Resources\RetreatChambres\Pages\ListRetreatChambres;
use App\Filament\Resources\RetreatChambres\Pages\ViewRetreatChambre;
use App\Filament\Resources\RetreatChambres\Schemas\RetreatChambreForm;
use App\Filament\Resources\RetreatChambres\Schemas\RetreatChambreInfolist;
use App\Filament\Resources\RetreatChambres\Tables\RetreatChambresTable;
use App\Models\RetreatChambre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RetreatChambreResource extends Resource
{
    protected static ?string $model = RetreatChambre::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Chambres';

    protected static string|UnitEnum|null $navigationGroup = 'Logistique';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'Chambre';

    protected static ?string $pluralModelLabel = 'Chambres';

    public static function form(Schema $schema): Schema
    {
        return RetreatChambreForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatChambreInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatChambresTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
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
            'index' => ListRetreatChambres::route('/'),
            'create' => CreateRetreatChambre::route('/create'),
            'view' => ViewRetreatChambre::route('/{record}'),
            'edit' => EditRetreatChambre::route('/{record}/edit'),
        ];
    }
}

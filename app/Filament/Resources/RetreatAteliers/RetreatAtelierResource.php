<?php

namespace App\Filament\Resources\RetreatAteliers;

use App\Filament\Resources\RetreatAteliers\Pages\CreateRetreatAtelier;
use App\Filament\Resources\RetreatAteliers\Pages\EditRetreatAtelier;
use App\Filament\Resources\RetreatAteliers\Pages\ListRetreatAteliers;
use App\Filament\Resources\RetreatAteliers\Pages\ViewRetreatAtelier;
use App\Filament\Resources\RetreatAteliers\Schemas\RetreatAtelierForm;
use App\Filament\Resources\RetreatAteliers\Schemas\RetreatAtelierInfolist;
use App\Filament\Resources\RetreatAteliers\Tables\RetreatAteliersTable;
use App\Models\RetreatAtelier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RetreatAtelierResource extends Resource
{
    protected static ?string $model = RetreatAtelier::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Ateliers';

    protected static string|UnitEnum|null $navigationGroup = 'Logistique';

    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = 'Atelier';

    protected static ?string $pluralModelLabel = 'Ateliers';

    public static function form(Schema $schema): Schema
    {
        return RetreatAtelierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatAtelierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatAteliersTable::configure($table);
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
            'index' => ListRetreatAteliers::route('/'),
            'create' => CreateRetreatAtelier::route('/create'),
            'view' => ViewRetreatAtelier::route('/{record}'),
            'edit' => EditRetreatAtelier::route('/{record}/edit'),
        ];
    }
}

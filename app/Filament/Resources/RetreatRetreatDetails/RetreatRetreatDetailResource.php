<?php

namespace App\Filament\Resources\RetreatRetreatDetails;

use App\Filament\Resources\RetreatRetreatDetails\Pages\CreateRetreatRetreatDetail;
use App\Filament\Resources\RetreatRetreatDetails\Pages\EditRetreatRetreatDetail;
use App\Filament\Resources\RetreatRetreatDetails\Pages\ListRetreatRetreatDetails;
use App\Filament\Resources\RetreatRetreatDetails\Pages\ViewRetreatRetreatDetail;
use App\Filament\Resources\RetreatRetreatDetails\Schemas\RetreatRetreatDetailForm;
use App\Filament\Resources\RetreatRetreatDetails\Schemas\RetreatRetreatDetailInfolist;
use App\Filament\Resources\RetreatRetreatDetails\Tables\RetreatRetreatDetailsTable;
use App\Models\RetreatRetreatDetail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatRetreatDetailResource extends Resource
{
    protected static ?string $model = RetreatRetreatDetail::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Details retraite';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation retraite';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Detail retraite';

    protected static ?string $pluralModelLabel = 'Details retraite';

    public static function form(Schema $schema): Schema
    {
        return RetreatRetreatDetailForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatRetreatDetailInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatRetreatDetailsTable::configure($table);
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
            'index' => ListRetreatRetreatDetails::route('/'),
            'create' => CreateRetreatRetreatDetail::route('/create'),
            'view' => ViewRetreatRetreatDetail::route('/{record}'),
            'edit' => EditRetreatRetreatDetail::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\RetreatPolicies;

use App\Filament\Resources\RetreatPolicies\Pages\CreateRetreatPolicy;
use App\Filament\Resources\RetreatPolicies\Pages\EditRetreatPolicy;
use App\Filament\Resources\RetreatPolicies\Pages\ListRetreatPolicies;
use App\Filament\Resources\RetreatPolicies\Pages\ViewRetreatPolicy;
use App\Filament\Resources\RetreatPolicies\Schemas\RetreatPolicyForm;
use App\Filament\Resources\RetreatPolicies\Schemas\RetreatPolicyInfolist;
use App\Filament\Resources\RetreatPolicies\Tables\RetreatPoliciesTable;
use App\Models\RetreatPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatPolicyResource extends Resource
{
    protected static ?string $model = RetreatPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Politiques';

    protected static string|UnitEnum|null $navigationGroup = 'Conformite';

    protected static ?int $navigationSort = 80;

    protected static ?string $modelLabel = 'Politique';

    protected static ?string $pluralModelLabel = 'Politiques';

    public static function form(Schema $schema): Schema
    {
        return RetreatPolicyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatPolicyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatPoliciesTable::configure($table);
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
            'index' => ListRetreatPolicies::route('/'),
            'create' => CreateRetreatPolicy::route('/create'),
            'view' => ViewRetreatPolicy::route('/{record}'),
            'edit' => EditRetreatPolicy::route('/{record}/edit'),
        ];
    }
}

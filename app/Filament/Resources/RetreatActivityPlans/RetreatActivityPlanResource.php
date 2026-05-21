<?php

namespace App\Filament\Resources\RetreatActivityPlans;

use App\Filament\Resources\RetreatActivityPlans\Pages\CreateRetreatActivityPlan;
use App\Filament\Resources\RetreatActivityPlans\Pages\EditRetreatActivityPlan;
use App\Filament\Resources\RetreatActivityPlans\Pages\ListRetreatActivityPlans;
use App\Filament\Resources\RetreatActivityPlans\Pages\ViewRetreatActivityPlan;
use App\Filament\Resources\RetreatActivityPlans\Schemas\RetreatActivityPlanForm;
use App\Filament\Resources\RetreatActivityPlans\Schemas\RetreatActivityPlanInfolist;
use App\Filament\Resources\RetreatActivityPlans\Tables\RetreatActivityPlansTable;
use App\Models\RetreatActivityPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

class RetreatActivityPlanResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = RetreatActivityPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Plans activites';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation retraite';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Plan activite';

    protected static ?string $pluralModelLabel = 'Plans activites';

    public static function form(Schema $schema): Schema
    {
        return RetreatActivityPlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatActivityPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatActivityPlansTable::configure($table);
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
            'index' => ListRetreatActivityPlans::route('/'),
            'create' => CreateRetreatActivityPlan::route('/create'),
            'view' => ViewRetreatActivityPlan::route('/{record}'),
            'edit' => EditRetreatActivityPlan::route('/{record}/edit'),
        ];
    }
}

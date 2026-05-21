<?php

namespace App\Filament\Resources\RetreatPolicyAcknowledgements;

use App\Filament\Resources\RetreatPolicyAcknowledgements\Pages\CreateRetreatPolicyAcknowledgement;
use App\Filament\Resources\RetreatPolicyAcknowledgements\Pages\EditRetreatPolicyAcknowledgement;
use App\Filament\Resources\RetreatPolicyAcknowledgements\Pages\ListRetreatPolicyAcknowledgements;
use App\Filament\Resources\RetreatPolicyAcknowledgements\Pages\ViewRetreatPolicyAcknowledgement;
use App\Filament\Resources\RetreatPolicyAcknowledgements\Schemas\RetreatPolicyAcknowledgementForm;
use App\Filament\Resources\RetreatPolicyAcknowledgements\Schemas\RetreatPolicyAcknowledgementInfolist;
use App\Filament\Resources\RetreatPolicyAcknowledgements\Tables\RetreatPolicyAcknowledgementsTable;
use App\Models\RetreatPolicyAcknowledgement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatPolicyAcknowledgementResource extends Resource
{
    protected static ?string $model = RetreatPolicyAcknowledgement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Accuses politiques';

    protected static string|UnitEnum|null $navigationGroup = 'Conformite';

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'Accuse politique';

    protected static ?string $pluralModelLabel = 'Accuses politiques';

    public static function form(Schema $schema): Schema
    {
        return RetreatPolicyAcknowledgementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatPolicyAcknowledgementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatPolicyAcknowledgementsTable::configure($table);
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
            'index' => ListRetreatPolicyAcknowledgements::route('/'),
            'create' => CreateRetreatPolicyAcknowledgement::route('/create'),
            'view' => ViewRetreatPolicyAcknowledgement::route('/{record}'),
            'edit' => EditRetreatPolicyAcknowledgement::route('/{record}/edit'),
        ];
    }
}

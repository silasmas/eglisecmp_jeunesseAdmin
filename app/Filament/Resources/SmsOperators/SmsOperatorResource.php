<?php

namespace App\Filament\Resources\SmsOperators;

use App\Filament\Resources\SmsOperators\Pages\CreateSmsOperator;
use App\Filament\Resources\SmsOperators\Pages\EditSmsOperator;
use App\Filament\Resources\SmsOperators\Pages\ListSmsOperators;
use App\Filament\Resources\SmsOperators\Schemas\SmsOperatorForm;
use App\Filament\Resources\SmsOperators\Tables\SmsOperatorsTable;
use App\Models\SmsOperator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class SmsOperatorResource extends Resource
{
    protected static ?string $model = SmsOperator::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Opérateurs SMS';

    protected static string|UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?string $modelLabel = 'Opérateur SMS';

    protected static ?string $pluralModelLabel = 'Opérateurs SMS';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return SmsOperatorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsOperatorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsOperators::route('/'),
            'create' => CreateSmsOperator::route('/create'),
            'edit' => EditSmsOperator::route('/{record}/edit'),
        ];
    }
}

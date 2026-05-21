<?php

namespace App\Filament\Resources\RetreatPayments;

use App\Filament\Resources\RetreatPayments\Pages\CreateRetreatPayment;
use App\Filament\Resources\RetreatPayments\Pages\EditRetreatPayment;
use App\Filament\Resources\RetreatPayments\Pages\ListRetreatPayments;
use App\Filament\Resources\RetreatPayments\Pages\ViewRetreatPayment;
use App\Filament\Resources\RetreatPayments\Schemas\RetreatPaymentForm;
use App\Filament\Resources\RetreatPayments\Schemas\RetreatPaymentInfolist;
use App\Filament\Resources\RetreatPayments\Tables\RetreatPaymentsTable;
use App\Models\RetreatPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatPaymentResource extends Resource
{
    protected static ?string $model = RetreatPayment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Paiements retraite';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?string $modelLabel = 'Paiement retraite';

    protected static ?string $pluralModelLabel = 'Paiements retraite';

    public static function form(Schema $schema): Schema
    {
        return RetreatPaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatPaymentsTable::configure($table);
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
            'index' => ListRetreatPayments::route('/'),
            'create' => CreateRetreatPayment::route('/create'),
            'view' => ViewRetreatPayment::route('/{record}'),
            'edit' => EditRetreatPayment::route('/{record}/edit'),
        ];
    }
}

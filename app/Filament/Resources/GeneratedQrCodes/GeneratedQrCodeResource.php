<?php

namespace App\Filament\Resources\GeneratedQrCodes;

use App\Filament\Resources\GeneratedQrCodes\Pages\CreateGeneratedQrCode;
use App\Filament\Resources\GeneratedQrCodes\Pages\EditGeneratedQrCode;
use App\Filament\Resources\GeneratedQrCodes\Pages\ListGeneratedQrCodes;
use App\Filament\Resources\GeneratedQrCodes\Pages\ViewGeneratedQrCode;
use App\Filament\Resources\GeneratedQrCodes\Schemas\GeneratedQrCodeForm;
use App\Filament\Resources\GeneratedQrCodes\Schemas\GeneratedQrCodeInfolist;
use App\Filament\Resources\GeneratedQrCodes\Tables\GeneratedQrCodesTable;
use App\Models\GeneratedQrCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Ressource Filament : génération et gestion de QR codes (lien + logo optionnel).
 */
class GeneratedQrCodeResource extends Resource
{
    protected static ?string $model = GeneratedQrCode::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR codes';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 25;

    protected static ?string $modelLabel = 'QR code';

    protected static ?string $pluralModelLabel = 'QR codes';

    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function form(Schema $schema): Schema
    {
        return GeneratedQrCodeForm::configure($schema);
    }

    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return GeneratedQrCodeInfolist::configure($schema);
    }

    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return GeneratedQrCodesTable::configure($table);
    }

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListGeneratedQrCodes::route('/'),
            'create' => CreateGeneratedQrCode::route('/create'),
            'view' => ViewGeneratedQrCode::route('/{record}'),
            'edit' => EditGeneratedQrCode::route('/{record}/edit'),
        ];
    }
}

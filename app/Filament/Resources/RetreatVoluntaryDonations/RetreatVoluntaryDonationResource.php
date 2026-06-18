<?php

namespace App\Filament\Resources\RetreatVoluntaryDonations;

use App\Filament\Resources\RetreatVoluntaryDonations\Pages\ListRetreatVoluntaryDonations;
use App\Filament\Resources\RetreatVoluntaryDonations\Pages\ViewRetreatVoluntaryDonation;
use App\Filament\Resources\RetreatVoluntaryDonations\Schemas\RetreatVoluntaryDonationInfolist;
use App\Filament\Resources\RetreatVoluntaryDonations\Tables\RetreatVoluntaryDonationsTable;
use App\Models\RetreatVoluntaryDonation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Ressource Filament : consultation des dons volontaires retraite.
 */
class RetreatVoluntaryDonationResource extends Resource
{
    protected static ?string $model = RetreatVoluntaryDonation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Dons volontaires';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 45;

    protected static ?string $modelLabel = 'Don volontaire';

    protected static ?string $pluralModelLabel = 'Dons volontaires';

    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public static function infolist(Schema $schema): Schema
    {
        return RetreatVoluntaryDonationInfolist::configure($schema);
    }

    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return RetreatVoluntaryDonationsTable::configure($table);
    }

    /**
     * @return Builder<RetreatVoluntaryDonation>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'event',
            'vouchers.redeemedByParticipant',
        ]);
    }

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRetreatVoluntaryDonations::route('/'),
            'view' => ViewRetreatVoluntaryDonation::route('/{record}'),
        ];
    }

    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return false;
    }
}

<?php

namespace App\Filament\Resources\ChurchEventHistories;

use App\Filament\Resources\ChurchEventHistories\Pages\ListChurchEventHistories;
use App\Filament\Resources\ChurchEventHistories\Pages\ViewChurchEventHistory;
use App\Filament\Resources\ChurchEventHistories\Tables\ChurchEventHistoriesTable;
use App\Models\ChurchEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Consultation des retraites archivées et de leurs participants.
 */
class ChurchEventHistoryResource extends Resource
{
    protected static ?string $model = ChurchEvent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Historique retraites';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Retraite archivée';

    protected static ?string $pluralModelLabel = 'Historique retraites';

    protected static ?string $slug = 'church-event-histories';

    /**
     * @return Builder<ChurchEvent>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['participants', 'ateliers', 'chambres'])
            ->whereNotNull('archived_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return ChurchEventHistoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChurchEventHistories::route('/'),
            'view' => ViewChurchEventHistory::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}

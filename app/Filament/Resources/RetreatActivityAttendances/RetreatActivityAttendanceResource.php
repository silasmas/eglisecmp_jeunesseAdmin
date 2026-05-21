<?php

namespace App\Filament\Resources\RetreatActivityAttendances;

use App\Filament\Resources\RetreatActivityAttendances\Pages\CreateRetreatActivityAttendance;
use App\Filament\Resources\RetreatActivityAttendances\Pages\EditRetreatActivityAttendance;
use App\Filament\Resources\RetreatActivityAttendances\Pages\ListRetreatActivityAttendances;
use App\Filament\Resources\RetreatActivityAttendances\Pages\ManageAtelierActivityAttendance;
use App\Filament\Resources\RetreatActivityAttendances\Pages\ViewRetreatActivityAttendance;
use App\Filament\Resources\RetreatActivityAttendances\Schemas\RetreatActivityAttendanceForm;
use App\Filament\Resources\RetreatActivityAttendances\Schemas\RetreatActivityAttendanceInfolist;
use App\Filament\Resources\RetreatActivityAttendances\Tables\RetreatActivityAttendancesTable;
use App\Models\RetreatActivityAttendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatActivityAttendanceResource extends Resource
{
    protected static ?string $model = RetreatActivityAttendance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Pointages activites';

    protected static string|UnitEnum|null $navigationGroup = 'Operations terrain';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Pointage activite';

    protected static ?string $pluralModelLabel = 'Pointages activites';

    public static function form(Schema $schema): Schema
    {
        return RetreatActivityAttendanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatActivityAttendanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatActivityAttendancesTable::configure($table);
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
            'index' => ListRetreatActivityAttendances::route('/'),
            'atelier-pointage' => ManageAtelierActivityAttendance::route('/atelier-pointage'),
            'create' => CreateRetreatActivityAttendance::route('/create'),
            'view' => ViewRetreatActivityAttendance::route('/{record}'),
            'edit' => EditRetreatActivityAttendance::route('/{record}/edit'),
        ];
    }
}

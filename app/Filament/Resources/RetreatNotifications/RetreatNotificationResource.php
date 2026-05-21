<?php

namespace App\Filament\Resources\RetreatNotifications;

use App\Filament\Resources\RetreatNotifications\Pages\CreateRetreatNotification;
use App\Filament\Resources\RetreatNotifications\Pages\EditRetreatNotification;
use App\Filament\Resources\RetreatNotifications\Pages\ListRetreatNotifications;
use App\Filament\Resources\RetreatNotifications\Pages\ViewRetreatNotification;
use App\Filament\Resources\RetreatNotifications\Schemas\RetreatNotificationForm;
use App\Filament\Resources\RetreatNotifications\Schemas\RetreatNotificationInfolist;
use App\Filament\Resources\RetreatNotifications\Tables\RetreatNotificationsTable;
use App\Models\RetreatNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RetreatNotificationResource extends Resource
{
    protected static ?string $model = RetreatNotification::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Notifications retraite';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?string $modelLabel = 'Notification retraite';

    protected static ?string $pluralModelLabel = 'Notifications retraite';

    public static function form(Schema $schema): Schema
    {
        return RetreatNotificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RetreatNotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetreatNotificationsTable::configure($table);
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
            'index' => ListRetreatNotifications::route('/'),
            'create' => CreateRetreatNotification::route('/create'),
            'view' => ViewRetreatNotification::route('/{record}'),
            'edit' => EditRetreatNotification::route('/{record}/edit'),
        ];
    }
}

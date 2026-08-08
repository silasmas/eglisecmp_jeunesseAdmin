<?php

namespace App\Filament\Resources\SmsTemplates;

use App\Filament\Resources\SmsTemplates\Pages\CreateSmsTemplate;
use App\Filament\Resources\SmsTemplates\Pages\EditSmsTemplate;
use App\Filament\Resources\SmsTemplates\Pages\ListSmsTemplates;
use App\Filament\Resources\SmsTemplates\Schemas\SmsTemplateForm;
use App\Filament\Resources\SmsTemplates\Tables\SmsTemplatesTable;
use App\Models\SmsTemplate;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * CRUD des modèles SMS dynamiques (groupe Notifications).
 */
class SmsTemplateResource extends Resource
{
    protected static ?string $model = SmsTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Modèles SMS';

    protected static string|UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?string $modelLabel = 'Modèle SMS';

    protected static ?string $pluralModelLabel = 'Modèles SMS';

    protected static ?int $navigationSort = 12;

    /**
     * @param  Schema  $schema  Schéma Filament
     */
    public static function form(Schema $schema): Schema
    {
        return SmsTemplateForm::configure($schema);
    }

    /**
     * @param  Table  $table  Table Filament
     */
    public static function table(Table $table): Table
    {
        return SmsTemplatesTable::configure($table);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListSmsTemplates::route('/'),
            'create' => CreateSmsTemplate::route('/create'),
            'edit' => EditSmsTemplate::route('/{record}/edit'),
        ];
    }
}

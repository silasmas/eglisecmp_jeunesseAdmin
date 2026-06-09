<?php

namespace App\Filament\Resources\RegistrationFormConfigs;

use App\Filament\Resources\RegistrationFormConfigs\Pages\CreateRegistrationFormConfig;
use App\Filament\Resources\RegistrationFormConfigs\Pages\EditRegistrationFormConfig;
use App\Filament\Resources\RegistrationFormConfigs\Pages\ListRegistrationFormConfigs;
use App\Filament\Resources\RegistrationFormConfigs\Schemas\RegistrationFormConfigForm;
use App\Filament\Resources\RegistrationFormConfigs\Tables\RegistrationFormConfigsTable;
use App\Models\RegistrationFormConfigSet;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Ressource Filament : configuration du formulaire d'inscription publique.
 */
class RegistrationFormConfigResource extends Resource
{
    protected static ?string $model = RegistrationFormConfigSet::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Configuration du formulaire';

    protected static string|UnitEnum|null $navigationGroup = 'Organisation retraite';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'Configuration formulaire';

    protected static ?string $pluralModelLabel = 'Configurations formulaire';

    /**
     * Schéma du formulaire d'édition.
     */
    public static function form(Schema $schema): Schema
    {
        return RegistrationFormConfigForm::configure($schema);
    }

    /**
     * Table de liste.
     */
    public static function table(Table $table): Table
    {
        return RegistrationFormConfigsTable::configure($table);
    }

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRegistrationFormConfigs::route('/'),
            'create' => CreateRegistrationFormConfig::route('/create'),
            'edit' => EditRegistrationFormConfig::route('/{record}/edit'),
        ];
    }
}

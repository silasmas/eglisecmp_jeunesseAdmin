<?php

namespace App\Filament\Resources\RetreatAteliers\Schemas;

use App\Models\ChurchEvent;
use App\Support\RetreatLogisticsFormSupport;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatAtelierForm
{
    /**
     * @param  Schema  $schema Schéma Filament
     * @return Schema
     */
    public static function configure(Schema $schema): Schema
    {
        $logisticsForm = app(RetreatLogisticsFormSupport::class);

        return $schema
            ->components([
                Section::make("Configuration de l'atelier")
                    ->columnSpanFull()
                    ->schema([
                        Select::make('event_id')
                            ->label('Retraite (édition)')
                            ->options(fn (): array => $logisticsForm->operationalEventOptions())
                            ->default(fn (): ?int => ChurchEvent::resolveOperationalLogisticsEvent()?->getKey())
                            ->searchable()
                            ->required()
                            ->disabledOn('edit')
                            ->dehydrated()
                            ->helperText('Chaque atelier appartient à une édition de retraite. Seules les retraites non archivées et non clôturées sont proposées.'),
                        TextInput::make('numero')
                            ->label('Numero atelier')
                            ->helperText('Numero unique pour cette retraite (avec le responsable).')
                            ->required()
                            ->numeric(),
                        TextInput::make('age_min')
                            ->label('Age minimum')
                            ->helperText('Tranche d\'age cible (inclus). Laissez vide si non limite.')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(99),
                        TextInput::make('age_max')
                            ->label('Age maximum')
                            ->helperText('Tranche d\'age cible (inclus). Doit etre >= age minimum.')
                            ->numeric()
                            ->minValue(15)
                            ->maxValue(99)
                            ->gte('age_min'),
                        UserSelect::make('responsable_user_id')
                            ->label("Responsable d'atelier")
                            ->relationship('responsable', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText("Personne chargee de piloter l'atelier.")
                            ->required(),
                        UserSelect::make('adjoint_user_id')
                            ->label("Adjoint d'atelier")
                            ->relationship('adjoint', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Peut aussi marquer les presences et mouvements de l\'atelier.'),
                        Select::make('role_on_atelier')
                            ->label('Role du responsable')
                            ->options([
                                'responsable' => 'Responsable',
                                'adjoint' => 'Adjoint',
                                'assistant' => 'Assistant',
                            ])
                            ->helperText('Precise le role metier du responsable sur cet atelier.')
                            ->required()
                            ->default('responsable'),
                        Textarea::make('description')
                            ->label('Description')
                            ->helperText('Objectif et organisation de cet atelier.')
                            ->columnSpanFull(),
                        Textarea::make('rapport_final')
                            ->label('Rapport final')
                            ->helperText('Bilan final des activites realisees dans cet atelier.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Atelier actif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\RetreatParticipantMovements\Schemas;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Services\RetreatAtelierAuthorizationService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Zvizvi\UserFields\Components\UserSelect;

class RetreatParticipantMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mouvement participant')
                    ->columnSpanFull()
                    ->schema([
                        UserSelect::make('participant_id')
                            ->label('Participant')
                            ->getSearchResultsUsing(fn (string $search): array => app(RetreatAtelierAuthorizationService::class)
                                ->scopeParticipantsForUser(RetreatParticipant::query(), Auth::user())
                                ->where(function ($query) use ($search): void {
                                    $query->where('nom', 'like', "%{$search}%")
                                        ->orWhere('prenom', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (RetreatParticipant $p): array => [$p->id => $p->full_name])
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => RetreatParticipant::query()->find($value)?->full_name)
                            ->searchable()
                            ->helperText('Participants de vos ateliers uniquement (responsable/adjoint).')
                            ->required(),
                        Select::make('event_id')
                            ->label('Evenement')
                            ->relationship(
                                'event',
                                'name',
                                modifyQueryUsing: fn ($query) => $query->where(function ($inner): void {
                                    $inner->whereNull('end_at')->orWhere('end_at', '>=', now());
                                })
                            )
                            ->getOptionLabelFromRecordUsing(fn (ChurchEvent $record): string => "{$record->name} ({$record->start_at?->format('d/m/Y')})")
                            ->searchable()
                            ->helperText('Seuls les evenements en cours ou futurs sont proposes.')
                            ->required(),
                        Select::make('movement_type')
                            ->label('Type de mouvement')
                            ->options([
                                'exit' => 'Sortie',
                                'return' => 'Retour',
                            ])
                            ->helperText('Sortie ou retour du participant.')
                            ->required(),
                        DateTimePicker::make('moved_at')
                            ->label('Date et heure')
                            ->default(now())
                            ->required(),
                        UserSelect::make('authorized_by')
                            ->label('Autorise par')
                            ->relationship('authorizedBy', 'name')
                            ->default(fn (): ?int => Auth::id())
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Enregistre automatiquement l\'utilisateur connecte.'),
                        Textarea::make('reason')
                            ->label('Motif')
                            ->columnSpanFull(),
                        Textarea::make('note')
                            ->label('Observation')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Mouvement actif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}

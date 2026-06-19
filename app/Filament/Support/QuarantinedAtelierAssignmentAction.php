<?php

namespace App\Filament\Support;

use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\RetreatAtelierProposalService;
use App\Services\RetreatPlacementAssignmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Zvizvi\UserFields\Components\UserSelect;

/**
 * Action Filament : propositions d'ateliers et validation manuelle par l'admin.
 */
class QuarantinedAtelierAssignmentAction
{
    /**
     * @param string $name Identifiant de l'action Filament
     * @param string|null $label Libellé du bouton
     * @return Action Action configurée
     */
    public static function make(string $name = 'validateAtelierAssignment', ?string $label = null): Action
    {
        return Action::make($name)
            ->label($label ?? 'Valider l\'affectation')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalHeading('Propositions d\'atelier')
            ->modalDescription('Choisissez un atelier existant recommandé ou créez-en un nouveau. L\'affectation n\'est appliquée qu\'après validation.')
            ->fillForm(function (RetreatParticipant $record): array {
                $proposal = app(RetreatAtelierProposalService::class)->buildForParticipant($record);
                $creation = $proposal['creation_suggestion'];

                return [
                    'decision_mode' => $proposal['recommended_atelier_id'] !== null ? 'existing' : 'create',
                    'atelier_id' => $proposal['recommended_atelier_id'],
                    'new_numero' => $creation['suggested_numero'] ?? null,
                    'new_age_min' => $creation['suggested_age_min'] ?? null,
                    'new_age_max' => $creation['suggested_age_max'] ?? null,
                ];
            })
            ->form(function (RetreatParticipant $record): array {
                $proposal = app(RetreatAtelierProposalService::class)->buildForParticipant($record);
                $creation = $proposal['creation_suggestion'];

                $proposalLines = collect($proposal['eligible'])
                    ->map(fn (array $row): string => ($row['recommended'] ? '★ ' : '• ').$row['label'])
                    ->implode("\n");

                $components = [
                    Placeholder::make('participant_info')
                        ->label('Participant')
                        ->content(sprintf(
                            '%s %s — %s ans — %s',
                            $record->prenom,
                            $record->nom,
                            $record->age,
                            $record->sexe ?: 'sexe non renseigné',
                        )),
                    Placeholder::make('proposal_summary')
                        ->label('Analyse du système')
                        ->content($proposal['summary']),
                ];

                if ($proposalLines !== '') {
                    $components[] = Placeholder::make('eligible_list')
                        ->label('Ateliers compatibles (★ = recommandé)')
                        ->content($proposalLines);
                }

                if ($creation !== null) {
                    $components[] = Placeholder::make('creation_hint')
                        ->label('Suggestion de création')
                        ->content($creation['reason']);
                }

                $components[] = Radio::make('decision_mode')
                    ->label('Décision admin')
                    ->options([
                        'existing' => 'Affecter à un atelier existant',
                        'create' => 'Créer un nouvel atelier puis affecter',
                    ])
                    ->default($proposal['recommended_atelier_id'] !== null ? 'existing' : 'create')
                    ->live()
                    ->required();

                $eligibleOptions = collect($proposal['eligible'])
                    ->mapWithKeys(fn (array $row): array => [
                        $row['atelier_id'] => ($row['recommended'] ? '★ ' : '').$row['label'],
                    ])
                    ->all();

                $components[] = Select::make('atelier_id')
                    ->label('Atelier retenu')
                    ->options($eligibleOptions)
                    ->default($proposal['recommended_atelier_id'])
                    ->searchable()
                    ->visible(fn (callable $get): bool => $get('decision_mode') === 'existing')
                    ->required(fn (callable $get): bool => $get('decision_mode') === 'existing')
                    ->helperText('Le système recommande l\'atelier le mieux équilibré (effectif, mixité).');

                $components[] = TextInput::make('new_numero')
                    ->label('Numéro du nouvel atelier')
                    ->numeric()
                    ->minValue(1)
                    ->default($creation['suggested_numero'] ?? null)
                    ->visible(fn (callable $get): bool => $get('decision_mode') === 'create')
                    ->required(fn (callable $get): bool => $get('decision_mode') === 'create');

                $components[] = TextInput::make('new_age_min')
                    ->label('Âge minimum')
                    ->numeric()
                    ->minValue(15)
                    ->maxValue(99)
                    ->default($creation['suggested_age_min'] ?? null)
                    ->visible(fn (callable $get): bool => $get('decision_mode') === 'create')
                    ->required(fn (callable $get): bool => $get('decision_mode') === 'create');

                $components[] = TextInput::make('new_age_max')
                    ->label('Âge maximum')
                    ->numeric()
                    ->minValue(15)
                    ->maxValue(99)
                    ->gte('new_age_min')
                    ->default($creation['suggested_age_max'] ?? null)
                    ->visible(fn (callable $get): bool => $get('decision_mode') === 'create')
                    ->required(fn (callable $get): bool => $get('decision_mode') === 'create');

                $components[] = UserSelect::make('responsable_user_id')
                    ->label('Responsable du nouvel atelier')
                    ->options(fn (): array => User::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->visible(fn (callable $get): bool => $get('decision_mode') === 'create')
                    ->required(fn (callable $get): bool => $get('decision_mode') === 'create');

                $components[] = Textarea::make('new_description')
                    ->label('Description (optionnel)')
                    ->rows(2)
                    ->visible(fn (callable $get): bool => $get('decision_mode') === 'create');

                return $components;
            })
            ->action(function (RetreatParticipant $record, array $data): void {
                $placement = app(RetreatPlacementAssignmentService::class);

                if (($data['decision_mode'] ?? 'existing') === 'create') {
                    $result = $placement->createAtelierAndAssignParticipant($record, [
                        'numero' => (int) $data['new_numero'],
                        'age_min' => (int) $data['new_age_min'],
                        'age_max' => (int) $data['new_age_max'],
                        'responsable_user_id' => (int) $data['responsable_user_id'],
                        'description' => $data['new_description'] ?? null,
                    ]);
                } else {
                    $result = $placement->assignParticipantToAtelierByAdmin(
                        $record,
                        (int) $data['atelier_id'],
                    );
                }

                $notification = Notification::make()
                    ->title($result['success'] ? 'Affectation validée' : 'Affectation impossible')
                    ->body($result['message']);

                if ($result['success']) {
                    $notification->success()->send();
                } else {
                    $notification->danger()->send();
                }
            });
    }
}

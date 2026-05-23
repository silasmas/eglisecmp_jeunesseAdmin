<?php

namespace App\Filament\Resources\RetreatParticipants\Pages;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Filament\Resources\RetreatParticipants\Widgets\RetreatParticipantsStats;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use App\Models\User;
use App\Notifications\ParticipantAssignmentMailNotification;
use App\Services\PanelNotificationDispatcher;
use App\Services\RetreatPlacementAssignmentService;
use App\Support\AvatarFallback;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Zvizvi\UserFields\Components\UserSelect;

class ListRetreatParticipants extends ListRecords
{
    protected static string $resource = RetreatParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('affectations')
                ->label('Affectations')
                ->icon('heroicon-o-arrows-right-left')
                ->modal()
                ->modalWidth(Width::SevenExtraLarge)
                ->modalAlignment(Alignment::Center)
                ->form([
                    Radio::make('operation')
                        ->label('Operation')
                        ->options([
                            'assign_chambre' => 'Affecter a une chambre',
                            'remove_chambre' => 'Retirer de la chambre',
                            'integrate_atelier' => 'Integrer dans un atelier',
                            'remove_atelier' => 'Desintegrer de l atelier',
                        ])
                        ->default('assign_chambre')
                        ->live()
                        ->required(),
                    UserSelect::make('participant_ids')
                        ->label('Participants')
                        ->options(fn (callable $get): array => $this->getParticipantsOptionsForOperation(
                            $get('operation'),
                            $get('chambre_id'),
                            $get('atelier_id'),
                        ))
                        ->allowHtml()
                        ->searchable()
                        ->multiple()
                        ->required()
                        ->helperText('Selectionne un ou plusieurs participants.'),
                    Select::make('chambre_id')
                        ->label('Chambre')
                        ->options(fn (): array => $this->getAvailableChambresOptions())
                        ->allowHtml()
                        ->searchable()
                        ->live()
                        ->visible(fn (callable $get): bool => $get('operation') === 'assign_chambre')
                        ->required(fn (callable $get): bool => $get('operation') === 'assign_chambre'),
                    Select::make('atelier_id')
                        ->label('Atelier')
                        ->options(fn (): array => $this->getAvailableAteliersOptions())
                        ->allowHtml()
                        ->searchable()
                        ->live()
                        ->visible(fn (callable $get): bool => $get('operation') === 'integrate_atelier')
                        ->required(fn (callable $get): bool => $get('operation') === 'integrate_atelier'),
                ])
                ->action(function (array $data): void {
                    $participants = RetreatParticipant::query()
                        ->whereIn('id', $data['participant_ids'] ?? [])
                        ->get();
                    $extraRecipients = collect();

                    if (($data['operation'] ?? null) === 'remove_chambre') {
                        $participants->loadMissing('chambre.responsable');
                        $participants->each(fn (RetreatParticipant $participant) => $extraRecipients->push($participant->chambre?->responsable));
                    }

                    if (($data['operation'] ?? null) === 'remove_atelier') {
                        $participants->loadMissing('atelier.responsable');
                        $participants->each(fn (RetreatParticipant $participant) => $extraRecipients->push($participant->atelier?->responsable));
                    }

                    match ($data['operation'] ?? null) {
                        'assign_chambre' => $participants->each(fn (RetreatParticipant $participant) => $participant->update([
                            'chambre_id' => $data['chambre_id'],
                        ])),
                        'remove_chambre' => $participants->each(fn (RetreatParticipant $participant) => $participant->update([
                            'chambre_id' => null,
                        ])),
                        'integrate_atelier' => $participants->each(fn (RetreatParticipant $participant) => $participant->update([
                            'atelier_id' => $data['atelier_id'],
                        ])),
                        'remove_atelier' => $participants->each(fn (RetreatParticipant $participant) => $participant->update([
                            'atelier_id' => null,
                        ])),
                        default => null,
                    };

                    [$title, $message] = $this->buildAssignmentMessage(
                        $data['operation'] ?? '',
                        $participants->count()
                    );

                    $this->notifyStakeholders($data['operation'] ?? '', $data, $title, $message, $extraRecipients);

                    FilamentNotification::make()
                        ->title($title)
                        ->body($message)
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RetreatParticipantsStats::class,
        ];
    }

    protected function getParticipantsOptionsForOperation(?string $operation, mixed $chambreId = null, mixed $atelierId = null): array
    {
        $query = RetreatParticipant::query()
            ->orderBy('prenom')
            ->orderBy('nom');

        match ($operation) {
            'assign_chambre' => app(RetreatPlacementAssignmentService::class)
                ->scopeEligibleForChambreAssignment($query->whereNull('chambre_id')),
            'remove_chambre' => $query->whereNotNull('chambre_id'),
            'integrate_atelier' => $query->whereNull('atelier_id'),
            'remove_atelier' => $query->whereNotNull('atelier_id'),
            default => $query,
        };

        $participants = $query->get();

        if ($operation === 'assign_chambre') {
            $placement = app(RetreatPlacementAssignmentService::class);
            $participants = $participants->filter(
                fn (RetreatParticipant $participant): bool => $placement->requiresChambrePlacement($participant)
            );

            if (filled($chambreId)) {
                $chambre = RetreatChambre::query()->find($chambreId);
                if ($chambre) {
                    $participants = $participants->filter(
                        fn (RetreatParticipant $participant): bool => $this->participantMatchesChambre($participant, $chambre)
                    );
                }
            }
        }

        if ($operation === 'integrate_atelier' && filled($atelierId)) {
            $atelier = RetreatAtelier::query()->find($atelierId);
            if ($atelier) {
                $participants = $participants->filter(fn (RetreatParticipant $participant): bool => $this->participantMatchesAtelier($participant, $atelier));
            }
        }

        return $participants
            ->mapWithKeys(fn (RetreatParticipant $participant): array => [
                $participant->id => view('user-fields::user-avatar-option', ['user' => $participant])->render(),
            ])
            ->all();
    }

    protected function getAvailableChambresOptions(): array
    {
        return RetreatChambre::query()
            ->where('is_active', true)
            ->with('responsable')
            ->orderBy('nom')
            ->get()
            ->filter(function (RetreatChambre $chambre): bool {
                $occupancy = RetreatParticipant::query()
                    ->where('chambre_id', $chambre->id)
                    ->count();

                return $occupancy < (int) $chambre->capacite;
            })
            ->mapWithKeys(fn (RetreatChambre $chambre): array => [
                $chambre->id => $this->assignmentOptionLabel(
                    $chambre->nom,
                    "{$chambre->capacite} places",
                    $chambre->responsable
                ),
            ])
            ->all();
    }

    protected function getAvailableAteliersOptions(): array
    {
        return RetreatAtelier::query()
            ->where('is_active', true)
            ->with('responsable')
            ->orderBy('numero')
            ->get()
            ->mapWithKeys(fn (RetreatAtelier $atelier): array => [
                $atelier->id => $this->assignmentOptionLabel(
                    "Atelier {$atelier->numero}",
                    null,
                    $atelier->responsable
                ),
            ])
            ->all();
    }

    protected function participantMatchesChambre(RetreatParticipant $participant, RetreatChambre $chambre): bool
    {
        $roomSexe = app(RetreatPlacementAssignmentService::class)->normalizeSexe($chambre->sexe);
        if ($roomSexe === '' || $roomSexe === 'mixte') {
            return true;
        }

        return app(RetreatPlacementAssignmentService::class)->normalizeSexe($participant->sexe) === $roomSexe;
    }

    protected function participantMatchesAtelier(RetreatParticipant $participant, RetreatAtelier $atelier): bool
    {
        $numbers = app(RetreatPlacementAssignmentService::class)->atelierNumbersForAge((int) $participant->age);

        return in_array((int) $atelier->numero, $numbers, true);
    }

    protected function assignmentOptionLabel(string $title, ?string $subtitle, ?User $responsable): string
    {
        $responsableName = $responsable?->name ?? 'Non defini';
        $subtitleHtml = filled($subtitle) ? '<span class="text-xs text-gray-500">'.e($subtitle).'</span>' : '';

        return sprintf(
            '<div class="flex items-center gap-2"><div class="min-w-0"><div class="font-medium">%s</div>%s</div><div class="ml-auto flex items-center gap-2 text-xs text-gray-500"><span>Responsable:</span>%s<span>%s</span></div></div>',
            e($title),
            $subtitleHtml,
            $this->avatarHtml($responsableName, $responsable?->profile_photo_path),
            e($responsableName)
        );
    }

    protected function personOptionLabel(string $name, ?string $photo): string
    {
        return sprintf(
            '<div class="flex items-center gap-2">%s<span>%s</span></div>',
            $this->avatarHtml($name, $photo),
            e($name)
        );
    }

    protected function avatarHtml(string $name, ?string $path): string
    {
        $url = $this->imageUrl($path);

        return sprintf(
            '<img src="%s" alt="%s" class="h-5 w-5 shrink-0 rounded-full object-cover" onerror="%s" />',
            e($url),
            e($name),
            htmlspecialchars(AvatarFallback::imgOnErrorAttribute(), ENT_COMPAT | ENT_HTML401, 'UTF-8'),
        );
    }

    protected function imageUrl(?string $path): string
    {
        if (filled($path)) {
            return Str::startsWith($path, ['http://', 'https://', '/'])
                ? $path
                : (app(\App\Services\PublicStorageUrl::class)->fromPath($path) ?? AvatarFallback::url());
        }

        return AvatarFallback::url();
    }

    protected function buildAssignmentMessage(string $operation, int $count): array
    {
        $action = match ($operation) {
            'assign_chambre' => 'affectes a une chambre',
            'remove_chambre' => 'retires de la chambre',
            'integrate_atelier' => 'integres dans un atelier',
            'remove_atelier' => "desintegres de l'atelier",
            default => 'traites',
        };

        return [
            'Affectation appliquee avec succes.',
            "{$count} participant(s) ont ete {$action}.",
        ];
    }

    protected function notifyStakeholders(string $operation, array $data, string $title, string $message, ?Collection $extraRecipients = null): void
    {
        $recipients = $extraRecipients ?? collect();

        if ($operation === 'assign_chambre' && filled($data['chambre_id'] ?? null)) {
            $recipients->push(
                RetreatChambre::query()->find($data['chambre_id'])?->responsable
            );
        }

        if ($operation === 'integrate_atelier' && filled($data['atelier_id'] ?? null)) {
            $recipients->push(
                RetreatAtelier::query()->find($data['atelier_id'])?->responsable
            );
        }

        $adminUsers = User::query()
            ->role(['super_admin', 'panel_user'])
            ->where('is_active', true)
            ->get();

        $users = $recipients
            ->merge($adminUsers)
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            return;
        }

        app(PanelNotificationDispatcher::class)->notify(
            $users,
            $title,
            $message,
            null,
            'participant'
        );

        NotificationFacade::send($users, new ParticipantAssignmentMailNotification($title, $message));
    }
}

<?php

namespace App\Filament\Resources\RetreatParticipants\Tables;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\RetreatParticipantRegistrationService;
use App\Services\RetreatPlacementAssignmentService;
use App\Support\AvatarFallback;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use TinusG\FilamentHoverImageColumn\HoverImageColumn;
use Wezlo\FilamentRecordWatcher\Actions\UnwatchAction;
use Wezlo\FilamentRecordWatcher\Actions\WatchAction;
use Zvizvi\UserFields\Components\UserColumn;
use Zvizvi\UserFields\Components\UserSelectFilter;

class RetreatParticipantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'retreatAccessGrantedBy',
                'badgeReceivedBy',
                'latestCashPayment.accessGrantedBy',
                'sponsorshipVoucher.donation',
            ]))
            ->columns([
                HoverImageColumn::make('photo')
                    ->label('Profil')
                    ->previewSize(320)
                    ->defaultImageUrl(fn (): string => AvatarFallback::url())
                    ->sticky(),
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sticky(),
                TextColumn::make('prenom')
                    ->label('Prenom')
                    ->searchable()
                    ->sticky(),
                TextColumn::make('age')
                    ->label('Age')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('participant_type')
                    ->label('Type participant')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'internal', 'interne' => 'success',
                        'external', 'externe' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'internal' => 'Interne',
                        'external' => 'Externe',
                        default => ucfirst((string) $state),
                    })
                    ->searchable(),
                TextColumn::make('registration_status')
                    ->label('Statut inscription')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'confirmed', 'valide' => 'success',
                        'pending', 'en_attente' => 'warning',
                        'rejected', 'annule' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'confirmed' => 'Confirme',
                        'pending' => 'En attente',
                        'rejected' => 'Rejete',
                        'cancelled' => 'Annule',
                        default => ucfirst((string) $state),
                    })
                    ->searchable(),
                TextColumn::make('sexe')
                    ->label('Sexe')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'homme' => 'info',
                        'femme' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'male' => 'Homme',
                        'female' => 'Femme',
                        default => ucfirst((string) $state),
                    })
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('telephone')
                    ->label('Telephone')
                    ->searchable(),
                TextColumn::make('chambre.nom')
                    ->label('Chambre')
                    ->searchable(),
                TextColumn::make('atelier.numero')
                    ->label('Atelier')
                    ->searchable(),
                TextColumn::make('badge_status')
                    ->label('Badge physique')
                    ->badge()
                    ->state(fn (RetreatParticipant $record): string => self::resolveBadgeStatus($record))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'received' => 'Remis',
                        'pending' => 'En attente',
                        default => '—',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->tooltip(fn (RetreatParticipant $record): ?string => $record->badge_received_at?->format('d/m/Y H:i')),
                TextColumn::make('billet_notification_status')
                    ->label('Billet notifié')
                    ->badge()
                    ->state(fn (RetreatParticipant $record): string => $record->billet_envoye ? 'sent' : 'pending')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sent' => 'Envoyé',
                        'pending' => 'Non envoyé',
                        default => '—',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->tooltip(fn (RetreatParticipant $record): ?string => $record->date_billet_envoye?->format('d/m/Y H:i')),
                TextColumn::make('sponsorshipVoucher.code')
                    ->label('Code prise en charge')
                    ->badge()
                    ->color('info')
                    ->copyable()
                    ->copyMessage('Code copié')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('sponsorshipVoucher.donation.donor_name')
                    ->label('Parrainé par')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('preuve_paiement')
                    ->label('Preuve paiement')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('paiement_valide')
                    ->label('Paiement valide')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('user')
                    ->label('Utilisateur')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('qr_code')
                    ->label('QR code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('adresse')
                    ->label('Adresse')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('telephone_urgence')
                    ->label('Telephone urgence')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('family_group_id')
                    ->label('Foyer / regroupement')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('family_group_name')
                    ->label('Nom regroupement')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('date_presence')
                    ->label('Date presence')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('retreatAccessGrantedBy')
                    ->label('Acces retraite par')
                    ->wrapped()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('badge_received_at')
                    ->label('Badge remis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('badgeReceivedBy')
                    ->label('Badge remis par')
                    ->wrapped()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cash_payment_validated_at')
                    ->label('Cash valide le')
                    ->state(fn (RetreatParticipant $record) => $record->latestCashPayment?->access_granted_at)
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('cash_payment_validator')
                    ->label('Cash valide par')
                    ->state(fn (RetreatParticipant $record) => $record->latestCashPayment?->accessGrantedBy)
                    ->wrapped()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('present')
                    ->label('Present')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                UserColumn::make('owner')
                    ->label('Owner')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('billet_envoye')
                    ->label('Billet envoye')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('date_billet_envoye')
                    ->label('Date envoi billet')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billet_pdf')
                    ->label('Billet PDF')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role_participant')
                    ->label('Role participant')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'participant' => 'Participant',
                        'staff' => 'Encadreur',
                        'responsable' => 'Responsable',
                        default => ucfirst((string) $state),
                    })
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('exit_allowed')
                    ->label('Sortie autorisee')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('curfew_time')
                    ->label('Heure limite')
                    ->time()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guardian_name')
                    ->label('Nom responsable')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('guardian_phone')
                    ->label('Telephone responsable')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_otp_code')
                    ->label('Code OTP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_otp_sent_at')
                    ->label('OTP envoye le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_otp_expires_at')
                    ->label('OTP expire le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_otp_verified_at')
                    ->label('OTP verifie le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registration_otp_attempts')
                    ->label('Tentatives OTP')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_verified')
                    ->label('Verifie')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('billet_envoye_email')
                    ->label('Billet email')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('billet_envoye_whatsapp')
                    ->label('Billet WhatsApp')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Mis a jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sexe')
                    ->label('Sexe')
                    ->options([
                        'homme' => 'Homme',
                        'femme' => 'Femme',
                        'male' => 'Homme',
                        'female' => 'Femme',
                    ]),
                SelectFilter::make('participant_type')
                    ->label('Type participant')
                    ->options([
                        'internal' => 'Interne',
                        'interne' => 'Interne',
                        'external' => 'Externe',
                        'externe' => 'Externe',
                    ]),
                SelectFilter::make('registration_status')
                    ->label('Statut inscription')
                    ->options([
                        'confirmed' => 'Confirme',
                        'pending' => 'En attente',
                        'rejected' => 'Rejete',
                        'cancelled' => 'Annule',
                        'en_attente' => 'En attente',
                        'valide' => 'Confirme',
                    ]),
                Filter::make('sponsored')
                    ->label('Inscrit via code prise en charge')
                    ->query(fn (Builder $query): Builder => $query->whereHas('sponsorshipVoucher')),
                SelectFilter::make('chambre_id')
                    ->label('Chambre')
                    ->relationship('chambre', 'nom'),
                SelectFilter::make('atelier_id')
                    ->label('Atelier')
                    ->relationship('atelier', 'numero'),
                UserSelectFilter::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                UserSelectFilter::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('badge_pending')
                    ->label('Badge en attente de remise')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('paiement_valide', true)
                        ->where('badge_received', false)),
                Filter::make('has_family_group')
                    ->label('Avec regroupement foyer')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('family_group_id')),
                Filter::make('age_range')
                    ->label("Tranche d'age")
                    ->form([
                        TextInput::make('age_min')
                            ->label('Age minimum')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('age_max')
                            ->label('Age maximum')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['age_min'] ?? null),
                                fn (Builder $query): Builder => $query->where('age', '>=', (int) $data['age_min'])
                            )
                            ->when(
                                filled($data['age_max'] ?? null),
                                fn (Builder $query): Builder => $query->where('age', '<=', (int) $data['age_max'])
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modal()
                        ->modalWidth(Width::SevenExtraLarge)
                        ->modalAlignment(Alignment::Center),
                    EditAction::make(),
                    WatchAction::make(),
                    UnwatchAction::make(),
                    Action::make('affecter_chambre')
                        ->label('Affecter chambre')
                        ->icon('heroicon-o-home-modern')
                        ->visible(fn (RetreatParticipant $record): bool => blank($record->chambre_id)
                            && app(RetreatPlacementAssignmentService::class)->requiresChambrePlacement($record))
                        ->requiresConfirmation()
                        ->modalHeading('Affectation automatique de chambre')
                        ->modalDescription('Le systeme choisit la chambre la moins remplie compatible (sexe, capacite).')
                        ->action(function (RetreatParticipant $record): void {
                            $result = app(RetreatPlacementAssignmentService::class)->assignChambreAutomatically($record);
                            self::notifyPlacementResult('Affectation chambre', $result);
                        }),
                    Action::make('retirer_chambre')
                        ->label('Retirer chambre')
                        ->icon('heroicon-o-home-modern')
                        ->visible(fn ($record): bool => filled($record->chambre_id))
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(fn ($record) => $record->update(['chambre_id' => null])),
                    Action::make('integrer_atelier')
                        ->label('Integrer atelier')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->visible(fn ($record): bool => blank($record->atelier_id))
                        ->requiresConfirmation()
                        ->modalHeading('Affectation automatique d\'atelier')
                        ->modalDescription('Le systeme choisit l\'atelier le plus equilibre selon l\'age et le sexe du participant.')
                        ->action(function (RetreatParticipant $record): void {
                            $result = app(RetreatPlacementAssignmentService::class)->assignAtelierAutomatically($record);
                            self::notifyPlacementResult('Affectation atelier', $result);
                        }),
                    Action::make('desintegrer_atelier')
                        ->label('Desintegrer atelier')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->visible(fn ($record): bool => filled($record->atelier_id))
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(fn ($record) => $record->update(['atelier_id' => null])),
                    Action::make('valider_inscription')
                        ->label('Valider inscription')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (RetreatParticipant $record): bool => ! $record->paiement_valide
                            || ! in_array($record->registration_status, ['completed', 'confirmed', 'valide'], true))
                        ->requiresConfirmation()
                        ->modalHeading('Valider l\'inscription ?')
                        ->modalDescription('Confirme le paiement et finalise l\'inscription du participant.')
                        ->action(function (RetreatParticipant $record): void {
                            $admin = Auth::user();
                            if (! $admin instanceof User) {
                                return;
                            }

                            app(RetreatParticipantRegistrationService::class)->validateRegistration($record, $admin);

                            Notification::make()
                                ->title('Inscription validée')
                                ->success()
                                ->send();
                        }),
                    Action::make('envoyer_billet')
                        ->label(fn (RetreatParticipant $record): string => $record->billet_envoye ? 'Renvoyer billet' : 'Envoyer billet')
                        ->icon('heroicon-o-ticket')
                        ->color(fn (RetreatParticipant $record): string => $record->billet_envoye ? 'gray' : 'primary')
                        ->visible(fn (RetreatParticipant $record): bool => (bool) $record->paiement_valide)
                        ->requiresConfirmation()
                        ->modalHeading(fn (RetreatParticipant $record): string => $record->billet_envoye
                            ? 'Renvoyer la notification billet ?'
                            : 'Envoyer la notification billet ?')
                        ->action(function (RetreatParticipant $record): void {
                            $result = app(RetreatParticipantRegistrationService::class)
                                ->sendBilletNotification($record, true);

                            $notification = Notification::make()
                                ->title($result['success'] ? 'Notification billet' : 'Échec envoi billet')
                                ->body($result['message']);

                            if ($result['success']) {
                                $notification->success()->send();
                            } else {
                                $notification->danger()->send();
                            }
                        }),
                    Action::make('marquer_badge_remis')
                        ->label('Marquer badge remis')
                        ->icon('heroicon-o-identification')
                        ->color('success')
                        ->visible(fn (RetreatParticipant $record): bool => (bool) $record->paiement_valide && ! $record->badge_received)
                        ->requiresConfirmation()
                        ->modalHeading('Confirmer la remise du badge')
                        ->modalDescription('Indique que le participant a recu son badge physique sur place.')
                        ->action(function (RetreatParticipant $record): void {
                            $admin = Auth::user();
                            if (! $admin instanceof User) {
                                return;
                            }

                            app(RetreatParticipantRegistrationService::class)->markBadgeReceived($record, $admin);

                            Notification::make()
                                ->title('Badge marqué comme remis')
                                ->success()
                                ->send();
                        }),
                    Action::make('open_in_new_tab')
                        ->label('Ouvrir dans un onglet')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn ($record): string => RetreatParticipantResource::getUrl('view', ['record' => $record]))
                        ->openUrlInNewTab(),
                ])
                    ->iconButton()
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->tooltip('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('affecter_chambre')
                        ->label('Affecter chambre (auto)')
                        ->icon('heroicon-o-home-modern')
                        ->requiresConfirmation()
                        ->modalHeading('Affectation automatique de chambre')
                        ->modalDescription('Chaque participant interne sans chambre recevra une chambre selon les regles d\'equilibrage.')
                        ->action(function ($records): void {
                            $placement = app(RetreatPlacementAssignmentService::class);
                            $success = 0;
                            $failures = [];

                            foreach ($records as $record) {
                                if (! $record instanceof RetreatParticipant) {
                                    continue;
                                }

                                $result = $placement->assignChambreAutomatically($record);

                                if ($result['success']) {
                                    $success++;

                                    continue;
                                }

                                $failures[] = $record->full_name.': '.$result['message'];
                            }

                            self::notifyBulkPlacementResult('Affectation chambre', $success, $failures);
                        }),
                    BulkAction::make('integrer_atelier')
                        ->label('Integrer atelier (auto)')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->requiresConfirmation()
                        ->modalHeading('Affectation automatique d\'atelier')
                        ->modalDescription('Chaque participant sans atelier recevra un atelier selon les regles d\'equilibrage.')
                        ->action(function ($records): void {
                            $placement = app(RetreatPlacementAssignmentService::class);
                            $success = 0;
                            $failures = [];

                            foreach ($records as $record) {
                                if (! $record instanceof RetreatParticipant) {
                                    continue;
                                }

                                $result = $placement->assignAtelierAutomatically($record);

                                if ($result['success']) {
                                    $success++;

                                    continue;
                                }

                                $failures[] = $record->full_name.': '.$result['message'];
                            }

                            self::notifyBulkPlacementResult('Affectation atelier', $success, $failures);
                        }),
                    BulkAction::make('desintegrer_atelier')
                        ->label('Desintegrer atelier (selection)')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['atelier_id' => null]))),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }


    /**
     * Affiche le resultat d'une affectation automatique (succes ou impossibilite).
     *
     * @param string $title Titre de la notification
     * @param array{success: bool, message: string} $result Resultat du service
     * @return void
     */
    protected static function notifyPlacementResult(string $title, array $result): void
    {
        $notification = Notification::make()
            ->title($title)
            ->body($result['message']);

        if ($result['success']) {
            $notification->success()->send();

            return;
        }

        $notification->warning()->send();
    }

    /**
     * Resume d'une affectation automatique en masse.
     *
     * @param string $title Titre de la notification
     * @param int $success Nombre de reussites
     * @param list<string> $failures Messages d'echec par participant
     * @return void
     */
    protected static function notifyBulkPlacementResult(string $title, int $success, array $failures): void
    {
        if ($success === 0 && $failures === []) {
            Notification::make()
                ->title($title)
                ->body('Aucun participant eligible dans la selection.')
                ->warning()
                ->send();

            return;
        }

        $body = $success > 0
            ? sprintf('%d participant(s) affecte(s).', $success)
            : 'Aucune affectation realisee.';

        if ($failures !== []) {
            $preview = array_slice($failures, 0, 5);
            $body .= "\n\n".implode("\n", $preview);

            if (count($failures) > 5) {
                $body .= sprintf("\n... et %d autre(s).", count($failures) - 5);
            }
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body);

        if ($success > 0 && $failures === []) {
            $notification->success()->send();

            return;
        }

        if ($success > 0) {
            $notification->warning()->send();

            return;
        }

        $notification->danger()->send();
    }

    /**
     * @param RetreatParticipant $record Participant
     * @return string Statut badge : received|pending|na
     */
    protected static function resolveBadgeStatus(RetreatParticipant $record): string
    {
        if (! $record->paiement_valide) {
            return 'na';
        }

        return $record->badge_received ? 'received' : 'pending';
    }
}

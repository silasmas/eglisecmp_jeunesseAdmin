<?php

namespace App\Filament\Resources\RetreatParticipants\Tables;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use App\Models\User;
use App\Services\RetreatPlacementAssignmentService;
use App\Support\AvatarFallback;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
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
                    ->dateTime()
                    ->sortable()
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
                        ->visible(fn ($record): bool => blank($record->chambre_id))
                        ->form([
                            Select::make('chambre_id')
                                ->label('Chambre')
                                ->options(fn (?RetreatParticipant $record = null): array => self::getChambreOptions($record))
                                ->allowHtml()
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn ($record, array $data) => $record->update(['chambre_id' => $data['chambre_id']])),
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
                        ->form([
                            Select::make('atelier_id')
                                ->label('Atelier')
                                ->options(fn (?RetreatParticipant $record = null): array => self::getAtelierOptions($record))
                                ->allowHtml()
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn ($record, array $data) => $record->update(['atelier_id' => $data['atelier_id']])),
                    Action::make('desintegrer_atelier')
                        ->label('Desintegrer atelier')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->visible(fn ($record): bool => filled($record->atelier_id))
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(fn ($record) => $record->update(['atelier_id' => null])),
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
                        ->label('Affecter chambre (selection)')
                        ->icon('heroicon-o-home-modern')
                        ->form([
                            Select::make('chambre_id')
                                ->label('Chambre')
                                ->options(fn (): array => self::getChambreOptions())
                                ->allowHtml()
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn ($records, array $data) => $records->each(fn ($record) => $record->update(['chambre_id' => $data['chambre_id']]))),
                    BulkAction::make('integrer_atelier')
                        ->label('Integrer atelier (selection)')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->form([
                            Select::make('atelier_id')
                                ->label('Atelier')
                                ->options(fn (): array => self::getAtelierOptions())
                                ->allowHtml()
                                ->searchable()
                                ->required(),
                        ])
                        ->action(fn ($records, array $data) => $records->each(fn ($record) => $record->update(['atelier_id' => $data['atelier_id']]))),
                    BulkAction::make('desintegrer_atelier')
                        ->label('Desintegrer atelier (selection)')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['atelier_id' => null]))),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getChambreOptions(?RetreatParticipant $participant = null): array
    {
        $query = RetreatChambre::query()
            ->with('responsable')
            ->withCount('participants')
            ->orderBy('nom');

        if ($participant) {
            $sexe = app(RetreatPlacementAssignmentService::class)->normalizeSexe($participant->sexe);
            $query->where(function ($query) use ($sexe): void {
                $query->whereNull('sexe')
                    ->orWhere('sexe', '')
                    ->orWhere('sexe', 'mixte')
                    ->orWhere('sexe', $sexe);
            });
        }

        return $query->get()
            ->filter(fn (RetreatChambre $chambre): bool => ($chambre->participants_count ?? 0) < (int) $chambre->capacite)
            ->mapWithKeys(fn (RetreatChambre $chambre): array => [
                $chambre->id => self::assignmentOptionLabel(
                    $chambre->nom.' · '.($chambre->sexe ?: 'mixte').' · '.($chambre->participants_count ?? 0).'/'.$chambre->capacite,
                    $chambre->responsable
                ),
            ])
            ->all();
    }

    protected static function getAtelierOptions(?RetreatParticipant $participant = null): array
    {
        $query = RetreatAtelier::query()
            ->with('responsable')
            ->withCount('participants')
            ->orderBy('numero');

        if ($participant) {
            $numbers = app(RetreatPlacementAssignmentService::class)->atelierNumbersForAge((int) $participant->age);
            $query->whereIn('numero', $numbers);
        }

        $ateliers = $query->get();

        if ($participant && $ateliers->isEmpty()) {
            $ateliers = RetreatAtelier::query()
                ->with('responsable')
                ->withCount('participants')
                ->orderBy('numero')
                ->get();
        }

        return $ateliers
            ->mapWithKeys(fn (RetreatAtelier $atelier): array => [
                $atelier->id => self::assignmentOptionLabel(
                    "Atelier {$atelier->numero} · ".($atelier->participants_count ?? 0).' inscrit(s)',
                    $atelier->responsable
                ),
            ])
            ->all();
    }

    protected static function assignmentOptionLabel(string $title, ?User $responsable): string
    {
        $responsableName = $responsable?->name ?? 'Non defini';

        return sprintf(
            '<div class="flex items-center gap-2"><span class="font-medium">%s</span><div class="ml-auto flex items-center gap-2 text-xs text-gray-500"><span>Responsable:</span>%s<span>%s</span></div></div>',
            e($title),
            self::avatarHtml($responsableName, $responsable?->profile_photo_path),
            e($responsableName)
        );
    }

    protected static function avatarHtml(string $name, ?string $path): string
    {
        return sprintf(
            '<img src="%s" alt="%s" class="h-5 w-5 shrink-0 rounded-full object-cover" onerror="%s" />',
            e(self::imageUrl($path)),
            e($name),
            htmlspecialchars(AvatarFallback::imgOnErrorAttribute(), ENT_COMPAT | ENT_HTML401, 'UTF-8'),
        );
    }

    protected static function imageUrl(?string $path): string
    {
        if (filled($path)) {
            return Str::startsWith($path, ['http://', 'https://', '/'])
                ? $path
                : asset('storage/'.$path);
        }

        return AvatarFallback::url();
    }
}

<?php

namespace App\Filament\Pages;

use App\Models\RetreatPayment;
use App\Models\User;
use App\Services\PublicStorageUrl;
use App\Services\RetreatParticipantRegistrationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * File d'attente des paiements cash — validation / rejet réservés au super_admin.
 */
class ManageRetreatCashPayments extends Page implements HasTable
{
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Paiements cash';

    protected static ?string $title = 'Paiements cash';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'paiements-cash';

    /**
     * @param array<string, mixed> $parameters Paramètres de route
     * @return bool Accès réservé au super_admin
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    /**
     * @param Table $table Table Filament
     * @return Table Configuration de la table cash
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetreatPayment::query()
                    ->where('channel', 'cash')
                    ->where('is_active', true)
                    ->with(['participant.event', 'event'])
            )
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('participant.full_name')
                    ->label('Participant')
                    ->searchable(['nom', 'prenom', 'postnom']),
                TextColumn::make('event.name')
                    ->label('Événement')
                    ->toggleable(),
                TextColumn::make('amount_expected')
                    ->label('Montant attendu')
                    ->money(fn (RetreatPayment $record): string => $record->currency ?: 'USD'),
                TextColumn::make('amount_paid')
                    ->label('Montant payé')
                    ->state(fn (RetreatPayment $record): float => $record->resolveReceivedAmount())
                    ->money(fn (RetreatPayment $record): string => $record->currency ?: 'USD'),
                TextColumn::make('etat')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'payee' => 'success',
                        'en_cours' => 'warning',
                        'annulee', 'echouee' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'payee' => 'Payé',
                        'en_cours' => 'En attente',
                        'annulee' => 'Rejeté',
                        'echouee' => 'Échoué',
                        default => ucfirst($state),
                    }),
                TextColumn::make('participant.preuve_paiement')
                    ->label('Preuve')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Voir' : '—')
                    ->url(fn (RetreatPayment $record): ?string => app(PublicStorageUrl::class)->fromPath($record->participant?->preuve_paiement))
                    ->openUrlInNewTab()
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('etat')
                    ->label('Statut')
                    ->options([
                        'en_cours' => 'En attente',
                        'payee' => 'Validé',
                        'annulee' => 'Rejeté',
                    ]),
            ])
            ->recordActions([
                Action::make('voir_preuve')
                    ->label('Preuve')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->url(fn (RetreatPayment $record): ?string => app(PublicStorageUrl::class)->fromPath($record->participant?->preuve_paiement))
                    ->openUrlInNewTab()
                    ->visible(fn (RetreatPayment $record): bool => filled($record->participant?->preuve_paiement)),
                Action::make('valider_cash')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider le paiement cash ?')
                    ->modalDescription('Le participant sera marqué payé et recevra son billet selon le canal de l\'événement.')
                    ->visible(fn (RetreatPayment $record): bool => $record->etat === 'en_cours')
                    ->action(function (RetreatPayment $record): void {
                        $admin = Auth::user();
                        if (! $admin instanceof User) {
                            return;
                        }

                        $result = app(RetreatParticipantRegistrationService::class)->approveCashPayment($record, $admin);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Paiement cash validé')
                                ->body($result['message'])
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Paiement validé — billet non envoyé')
                            ->body($result['message'])
                            ->warning()
                            ->send();
                    }),
                Action::make('rejeter_cash')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter le paiement cash ?')
                    ->form([
                        Textarea::make('reason')
                            ->label('Motif (optionnel)')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->visible(fn (RetreatPayment $record): bool => $record->etat === 'en_cours')
                    ->action(function (RetreatPayment $record, array $data): void {
                        $admin = Auth::user();
                        if (! $admin instanceof User) {
                            return;
                        }

                        app(RetreatParticipantRegistrationService::class)->rejectCashPayment(
                            $record,
                            $admin,
                            $data['reason'] ?? null,
                        );

                        Notification::make()
                            ->title('Paiement cash rejeté')
                            ->warning()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Aucun paiement cash')
            ->emptyStateDescription('Les preuves de paiement cash soumises par les participants apparaîtront ici.');
    }
}


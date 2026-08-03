<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RetreatPayments\RetreatPaymentResource;
use App\Models\RetreatPaymentFailureAlert;
use App\Models\User;
use App\Filament\Support\RetreatPaymentFlexPayFilamentActions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Support\RetreatPaymentFailureAlertsSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Liste des échecs de paiement d'inscription avec suivi et accusé de traitement.
 */
class RetreatPaymentFailureMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Échecs paiement';

    protected static ?string $title = 'Échecs de paiement inscription';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'echecs-paiement';

    /** @var bool Indique si la table SQL des alertes est disponible */
    public bool $alertsTableReady = false;

    /**
     * Vérifie la table avant d'afficher la liste.
     */
    public function mount(): void
    {
        $this->alertsTableReady = RetreatPaymentFailureAlertsSchema::isReady();
    }

    /**
     * @param array<string, mixed> $parameters Paramètres de route
     * @return bool Accès réservé au super_admin
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    /**
     * Badge : alertes non traitées.
     */
    public static function getNavigationBadge(): ?string
    {
        if (! RetreatPaymentFailureAlertsSchema::isReady()) {
            return null;
        }

        $count = RetreatPaymentFailureAlert::query()
            ->whereNull('acknowledged_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * @return string|null Couleur du badge navigation
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * @param Schema $schema Schéma Filament
     * @return Schema Contenu de la page
     */
    public function content(Schema $schema): Schema
    {
        if (! $this->alertsTableReady) {
            return $schema->components([
                View::make('filament.pages.payment-failure-alerts-migration-required'),
            ]);
        }

        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    /**
     * @param Table $table Table Filament
     * @return Table Configuration des échecs de paiement
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetreatPaymentFailureAlert::query()
                    ->with(['participant', 'payment', 'event', 'acknowledgedBy'])
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('participant.full_name')
                    ->label('Participant')
                    ->searchable(['nom', 'prenom', 'postnom'])
                    ->placeholder('—'),
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('failure_reason')
                    ->label('Cause')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'mobile_init_failed' => 'Init. mobile échouée',
                        'card_init_failed' => 'Init. carte échouée',
                        'check_api_error' => 'Erreur API check',
                        'polling_unknown_status' => 'Statut inconnu',
                        'payment_cancelled' => 'Annulé',
                        'payment_failed' => 'Échoué',
                        default => $state,
                    }),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->tooltip(fn (RetreatPaymentFailureAlert $record): string => $record->message),
                IconColumn::make('email_sent_at')
                    ->label('E-mail')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope')
                    ->falseIcon('heroicon-o-x-mark')
                    ->getStateUsing(fn (RetreatPaymentFailureAlert $record): bool => $record->email_sent_at !== null),
                IconColumn::make('acknowledged_at')
                    ->label('Traité')
                    ->boolean()
                    ->getStateUsing(fn (RetreatPaymentFailureAlert $record): bool => $record->isAcknowledged()),
            ])
            ->filters([
                TernaryFilter::make('acknowledged')
                    ->label('Traitement')
                    ->nullable()
                    ->trueLabel('Traités')
                    ->falseLabel('Non traités')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('acknowledged_at'),
                        false: fn ($query) => $query->whereNull('acknowledged_at'),
                    ),
                SelectFilter::make('failure_reason')
                    ->label('Cause')
                    ->options([
                        'mobile_init_failed' => 'Init. mobile échouée',
                        'card_init_failed' => 'Init. carte échouée',
                        'check_api_error' => 'Erreur API check',
                        'polling_unknown_status' => 'Statut inconnu',
                        'payment_cancelled' => 'Annulé',
                        'payment_failed' => 'Échoué',
                    ]),
            ])
            ->recordActions([
                Action::make('resumePaymentLink')
                    ->label('Lien reprise')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->visible(fn (RetreatPaymentFailureAlert $record): bool => $record->payment !== null
                        && \App\Support\RetreatInscriptionResumeUrl::canResumeForPayment($record->payment))
                    ->url(fn (RetreatPaymentFailureAlert $record): ?string => $record->payment_id
                        ? RetreatPaymentResource::getUrl('view', ['record' => $record->payment_id])
                        : null)
                    ->openUrlInNewTab()
                    ->tooltip('Ouvrir le paiement pour copier le lien de reprise'),
                Action::make('voir_paiement')
                    ->label('Paiement')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn (RetreatPaymentFailureAlert $record): ?string => $record->payment_id
                        ? RetreatPaymentResource::getUrl('view', ['record' => $record->payment_id])
                        : null)
                    ->visible(fn (RetreatPaymentFailureAlert $record): bool => $record->payment_id !== null)
                    ->openUrlInNewTab(),
                Action::make('recheckFlexPay')
                    ->label('Vérifier FlexPay')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (RetreatPaymentFailureAlert $record): bool => $record->payment !== null
                        && app(\App\Services\RetreatPaymentFlexPayService::class)->canRecheck($record->payment))
                    ->action(function (RetreatPaymentFailureAlert $record): void {
                        if ($record->payment === null) {
                            return;
                        }

                        RetreatPaymentFlexPayFilamentActions::handleRecheck($record->payment);
                    }),
                Action::make('relaunchFlexPay')
                    ->label('Relancer FlexPay')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (RetreatPaymentFailureAlert $record): bool => $record->payment !== null
                        && app(\App\Services\RetreatPaymentFlexPayService::class)->canRelaunch($record->payment))
                    ->form(function (RetreatPaymentFailureAlert $record): array {
                        if ($record->payment === null || $record->payment->channel !== 'mobile_money') {
                            return [];
                        }

                        return [
                            \Filament\Forms\Components\TextInput::make('phone')
                                ->label('Téléphone Mobile Money')
                                ->placeholder('24389XXXXXXX')
                                ->default($record->payment->phone)
                                ->required(),
                        ];
                    })
                    ->action(function (RetreatPaymentFailureAlert $record, array $data): void {
                        if ($record->payment === null) {
                            return;
                        }

                        RetreatPaymentFlexPayFilamentActions::handleRelaunch(
                            $record->payment,
                            isset($data['phone']) ? (string) $data['phone'] : null,
                        );
                    }),
                Action::make('voir_participant')
                    ->label('Participant')
                    ->icon('heroicon-o-user')
                    ->url(fn (RetreatPaymentFailureAlert $record): ?string => $record->participant_id
                        ? \App\Filament\Resources\RetreatParticipants\RetreatParticipantResource::getUrl('view', ['record' => $record->participant_id])
                        : null)
                    ->visible(fn (RetreatPaymentFailureAlert $record): bool => $record->participant_id !== null)
                    ->openUrlInNewTab(),
                Action::make('marquer_traite')
                    ->label('Marquer traité')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (RetreatPaymentFailureAlert $record): bool => ! $record->isAcknowledged())
                    ->action(function (RetreatPaymentFailureAlert $record): void {
                        $admin = Auth::user();
                        if (! $admin instanceof User) {
                            return;
                        }

                        $record->update([
                            'acknowledged_at' => now(),
                            'acknowledged_by' => $admin->id,
                        ]);

                        Notification::make()
                            ->title('Alerte marquée comme traitée')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}

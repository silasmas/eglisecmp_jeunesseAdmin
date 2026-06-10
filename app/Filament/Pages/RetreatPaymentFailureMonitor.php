<?php

namespace App\Filament\Pages;

use App\Models\RetreatPaymentFailureAlert;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;
use UnitEnum;

/**
 * Liste des échecs de paiement d'inscription avec suivi et accusé de traitement.
 */
class RetreatPaymentFailureMonitor extends Page implements HasTable
{
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Échecs paiement';

    protected static ?string $title = 'Échecs de paiement inscription';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'echecs-paiement';

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
        if (! DbSchema::hasTable('retreat_payment_failure_alerts')) {
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

<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RetreatParticipants\RetreatParticipantResource;
use App\Models\RetreatParticipant;
use App\Services\RetreatInscriptionFunnelService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Tableau de bord des inscriptions bloquées ou incomplètes (surtout au paiement).
 */
class RetreatInscriptionSurveillance extends Page implements HasTable
{
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Surveillance inscriptions';

    protected static ?string $title = 'Surveillance des inscriptions';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'surveillance-inscriptions';

  /**
   * @param array<string, mixed> $parameters Paramètres de route
   * @return bool Accès si l’utilisateur peut voir les participants
   */
    public static function canAccess(array $parameters = []): bool
    {
        return Auth::check();
    }

  /**
   * Badge : nombre de parcours non finalisés.
   */
    public static function getNavigationBadge(): ?string
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('retreat_participant', 'inscription_funnel_stage')) {
            return null;
        }

        $count = RetreatParticipant::query()
            ->where('is_active', true)
            ->where('paiement_valide', false)
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('inscription_funnel_stage', RetreatInscriptionFunnelService::paymentProblemStages())
                    ->orWhereHas('latestPayment', fn (Builder $p): Builder => $p->whereIn('etat', ['init', 'en_cours']));
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

  /**
   * @param Table $table Table Filament
   * @return Table Configuration surveillance
   */
    public function table(Table $table): Table
    {
        $funnel = app(RetreatInscriptionFunnelService::class);

        return $table
            ->query(
                RetreatParticipant::query()
                    ->where('is_active', true)
                    ->where('paiement_valide', false)
                    ->where(function (Builder $query): void {
                        $query
                            ->whereIn('inscription_funnel_stage', RetreatInscriptionFunnelService::paymentProblemStages())
                            ->orWhereHas('latestPayment', fn (Builder $p): Builder => $p->whereIn('etat', ['init', 'en_cours']))
                            ->orWhere(function (Builder $q): void {
                                $q->whereNull('inscription_funnel_stage')
                                    ->where('registration_status', 'pending');
                            });
                    })
                    ->with(['latestPayment', 'event'])
            )
            ->defaultSort('inscription_funnel_at', 'desc')
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom')
                    ->formatStateUsing(fn (RetreatParticipant $record): string => trim($record->prenom.' '.$record->nom))
                    ->searchable(['nom', 'prenom']),
                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('inscription_funnel_stage')
                    ->label('Étape bloquante')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $funnel->labelFor($state))
                    ->color(fn (?string $state): string => match ($state) {
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_POLL_EXHAUSTED,
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_CANCELLED,
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_VERIFY_FAILED => 'danger',
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_POLL_TIMEOUT => 'warning',
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_CASH_PROOF => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('inscription_funnel_detail')
                    ->label('Détail')
                    ->limit(80)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('latestPayment.channel')
                    ->label('Canal')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : '—'),
                TextColumn::make('latestPayment.etat')
                    ->label('État paiement')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'payee' => 'success',
                        'en_cours' => 'warning',
                        'annulee', 'echouee' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('latestPayment.reference')
                    ->label('Référence')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('inscription_funnel_at')
                    ->label('Dernière activité')
                    ->since()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('inscription_funnel_stage')
                    ->label('Étape')
                    ->options(RetreatInscriptionFunnelService::stageLabels()),
                SelectFilter::make('payment_channel')
                    ->label('Canal paiement')
                    ->options([
                        'mobile_money' => 'Mobile Money',
                        'card' => 'Carte',
                        'cash' => 'Espèces',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas(
                            'latestPayment',
                            fn (Builder $p): Builder => $p->where('channel', $data['value'])
                        );
                    }),
            ])
            ->recordActions([
                Action::make('view_participant')
                    ->label('Fiche')
                    ->icon('heroicon-o-user')
                    ->url(fn (RetreatParticipant $record): string => RetreatParticipantResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Aucun parcours bloqué')
            ->emptyStateDescription('Les participants ayant commencé un paiement sans le terminer apparaîtront ici.');
    }
}

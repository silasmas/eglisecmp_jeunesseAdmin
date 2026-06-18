<?php

namespace App\Filament\Pages;

use App\Filament\Resources\RetreatVoluntaryDonations\RetreatVoluntaryDonationResource;
use App\Models\RetreatVoluntaryDonation;
use App\Models\User;
use App\Services\PublicStorageUrl;
use App\Services\RetreatDonation\RetreatVoluntaryDonationService;
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
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * File d'attente des dons cash — validation avant génération des codes parrainage.
 */
class ManageRetreatDonationCashPayments extends Page implements HasTable
{
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Dons cash';

    protected static ?string $title = 'Dons cash à valider';

    protected static string|UnitEnum|null $navigationGroup = 'Gestion pastorale';

    protected static ?int $navigationSort = 46;

    protected static ?string $slug = 'dons-cash';

    /**
     * @param array<string, mixed> $parameters Paramètres de route
     * @return bool
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    /**
     * @param Schema $schema Schéma Filament
     * @return Schema
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    /**
     * @param Table $table Table Filament
     * @return Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetreatVoluntaryDonation::query()
                    ->where('donation_kind', RetreatVoluntaryDonation::KIND_CASH)
                    ->where('status', RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED)
                    ->with(['event'])
            )
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('donor_name')
                    ->label('Donateur')
                    ->searchable(),
                TextColumn::make('cash_purpose')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        RetreatVoluntaryDonation::PURPOSE_SPONSOR_YOUTH => 'Sponsor jeunes',
                        default => 'Fonctionnement',
                    }),
                TextColumn::make('youth_slots_count')
                    ->label('Places')
                    ->toggleable(),
                TextColumn::make('amount_expected')
                    ->label('Montant')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('currency')
                    ->label('Devise'),
                TextColumn::make('cash_proof_path')
                    ->label('Preuve')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Voir' : '—')
                    ->url(fn (RetreatVoluntaryDonation $record): ?string => app(PublicStorageUrl::class)->fromPath($record->cash_proof_path))
                    ->openUrlInNewTab()
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('voir')
                    ->label('Détail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (RetreatVoluntaryDonation $record): string => RetreatVoluntaryDonationResource::getUrl('view', ['record' => $record])),
                Action::make('valider')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider ce don cash ?')
                    ->modalDescription('Les codes parrainage seront générés si le don sponsorise des jeunes.')
                    ->action(function (RetreatVoluntaryDonation $record): void {
                        $admin = Auth::user();
                        if (! $admin instanceof User) {
                            return;
                        }

                        try {
                            app(RetreatVoluntaryDonationService::class)->approveCashPayment($record, $admin);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Validation impossible')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Don cash validé')
                            ->success()
                            ->send();
                    }),
                Action::make('rejeter')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('Motif (optionnel)')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (RetreatVoluntaryDonation $record, array $data): void {
                        $admin = Auth::user();
                        if (! $admin instanceof User) {
                            return;
                        }

                        app(RetreatVoluntaryDonationService::class)->rejectCashPayment(
                            $record,
                            $admin,
                            $data['reason'] ?? null
                        );

                        Notification::make()
                            ->title('Don cash rejeté')
                            ->warning()
                            ->send();
                    }),
            ]);
    }
}

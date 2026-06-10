<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\RetreatPaymentFailureMonitor;
use App\Models\RetreatPaymentFailureAlert;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as DbSchema;

/**
 * Widget tableau de bord : derniers échecs de paiement d'inscription non traités.
 */
class RetreatPaymentFailuresWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return bool Visibilité réservée au super_admin
     */
    public static function canView(): bool
    {
        if (! DbSchema::hasTable('retreat_payment_failure_alerts')) {
            return false;
        }

        $user = Auth::user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    /**
     * @return string|null Titre du widget
     */
    public function getTableHeading(): ?string
    {
        $pending = RetreatPaymentFailureAlert::query()
            ->whereNull('acknowledged_at')
            ->count();

        return 'Échecs paiement inscription'.($pending > 0 ? " ({$pending} en attente)" : '');
    }

    /**
     * @param Table $table Table Filament
     * @return Table Derniers échecs non traités
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                RetreatPaymentFailureAlert::query()
                    ->whereNull('acknowledged_at')
                    ->with(['participant', 'payment'])
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->since(),
                TextColumn::make('participant.full_name')
                    ->label('Participant')
                    ->placeholder('—'),
                TextColumn::make('reference')
                    ->label('Référence')
                    ->copyable(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(50),
            ])
            ->recordUrl(fn (): string => RetreatPaymentFailureMonitor::getUrl())
            ->paginated(false)
            ->emptyStateHeading('Aucun échec de paiement en attente')
            ->emptyStateDescription('Les échecs FlexPay ou annulations apparaîtront ici et déclencheront un e-mail.');
    }
}

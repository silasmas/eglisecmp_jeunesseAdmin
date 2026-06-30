<?php

namespace App\Filament\Resources\RetreatParticipants\Widgets;

use App\Models\RetreatParticipant;
use App\Support\RetreatActiveEventScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetreatParticipantsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $base = RetreatActiveEventScope::applyToParticipants(RetreatParticipant::query());

        return [
            Stat::make('Total participants', (string) (clone $base)->count()),
            Stat::make('Presents', (string) (clone $base)->where('present', true)->count()),
            Stat::make('Paiement valide', (string) (clone $base)->where('paiement_valide', true)->count()),
            Stat::make('Prise en charge (code)', (string) (clone $base)->whereHas('sponsorshipVoucher')->count()),
            Stat::make('Actifs', (string) (clone $base)->where('is_active', true)->count()),
            Stat::make('Hommes', (string) (clone $base)->where('sexe', 'homme')->count()),
            Stat::make('Femmes', (string) (clone $base)->where('sexe', 'femme')->count()),
            Stat::make('Inscription confirmee', (string) (clone $base)->where('registration_status', 'confirmed')->count()),
            Stat::make('Inscription en attente', (string) (clone $base)->where('registration_status', 'pending')->count()),
            Stat::make('Affectes a une chambre', (string) (clone $base)->whereNotNull('chambre_id')->count()),
            Stat::make('Integres a un atelier', (string) (clone $base)->whereNotNull('atelier_id')->count()),
        ];
    }
}

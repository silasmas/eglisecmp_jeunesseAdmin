<?php

namespace App\Filament\Resources\RetreatParticipants\Widgets;

use App\Models\RetreatParticipant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RetreatParticipantsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total participants', (string) RetreatParticipant::query()->count()),
            Stat::make('Presents', (string) RetreatParticipant::query()->where('present', true)->count()),
            Stat::make('Paiement valide', (string) RetreatParticipant::query()->where('paiement_valide', true)->count()),
            Stat::make('Actifs', (string) RetreatParticipant::query()->where('is_active', true)->count()),
            Stat::make('Hommes', (string) RetreatParticipant::query()->where('sexe', 'homme')->count()),
            Stat::make('Femmes', (string) RetreatParticipant::query()->where('sexe', 'femme')->count()),
            Stat::make('Inscription confirmee', (string) RetreatParticipant::query()->where('registration_status', 'confirmed')->count()),
            Stat::make('Inscription en attente', (string) RetreatParticipant::query()->where('registration_status', 'pending')->count()),
            Stat::make('Affectes a une chambre', (string) RetreatParticipant::query()->whereNotNull('chambre_id')->count()),
            Stat::make('Integres a un atelier', (string) RetreatParticipant::query()->whereNotNull('atelier_id')->count()),
        ];
    }
}

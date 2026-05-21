<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total utilisateurs', (string) User::query()->count()),
            Stat::make('Comptes actifs', (string) User::query()->where('is_active', true)->count()),
            Stat::make('Admins', (string) User::role('super_admin')->count()),
            Stat::make('Avec photo', (string) User::query()->whereNotNull('profile_photo_path')->count()),
        ];
    }
}

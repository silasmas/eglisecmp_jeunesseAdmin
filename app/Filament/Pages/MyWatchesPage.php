<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Wezlo\FilamentRecordWatcher\Pages\MyWatchesPage as BaseMyWatchesPage;

/**
 * Page « Mes suivis » avec contrôle d'accès Shield (View:MyWatchesPage).
 */
class MyWatchesPage extends BaseMyWatchesPage
{
    use HasPageShield;
}

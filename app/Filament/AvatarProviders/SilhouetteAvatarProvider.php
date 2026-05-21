<?php

namespace App\Filament\AvatarProviders;

use App\Support\AvatarFallback;
use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class SilhouetteAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        return AvatarFallback::url();
    }
}

<?php

namespace App\Filament\Infolists\Components;

use App\Support\AvatarFallback;
use Illuminate\Database\Eloquent\Model;
use Zvizvi\UserFields\Components\UserStackedEntry as ZvizviUserStackedEntry;

class UserStackedEntry extends ZvizviUserStackedEntry
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultImageUrl(AvatarFallback::url());
    }

    public function getImageUrl($userData = null): ?string
    {
        if ($userData instanceof Model) {
            return filament()->getUserAvatarUrl($userData);
        }

        if (blank($userData)) {
            return AvatarFallback::url();
        }

        return parent::getImageUrl(is_string($userData) ? $userData : null);
    }
}

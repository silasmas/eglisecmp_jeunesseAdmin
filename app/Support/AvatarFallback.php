<?php

namespace App\Support;

final class AvatarFallback
{
    public static function url(): string
    {
        return asset('images/default-person-avatar.svg');
    }

    /**
     * Handler pour attribut HTML img `onerror` (basculer vers la silhouette locale).
     */
    public static function imgOnErrorAttribute(): string
    {
        return "this.onerror=null;this.src='".self::url()."'";
    }
}

<?php

namespace App\Enums;

enum EventAccessAuthMode: string
{
    case Password = 'password';
    case Otp = 'otp';

    public function label(): string
    {
        return match ($this) {
            self::Password => 'Mot de passe',
            self::Otp => 'Code OTP',
        };
    }
}

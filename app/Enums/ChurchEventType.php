<?php

namespace App\Enums;

/**
 * Types d'événements administrables (liste déroulante admin).
 */
enum ChurchEventType: string
{
    case Retraite = 'retraite';
    case Culte = 'culte';
    case Conference = 'conference';
    case Seminar = 'seminaire';
    case Evangelisation = 'evangelisation';
    case Concert = 'concert';
    case Formation = 'formation';
    case Autre = 'autre';

    /**
     * @return string Libellé affiché dans Filament
     */
    public function label(): string
    {
        return match ($this) {
            self::Retraite => 'Retraite',
            self::Culte => 'Culte',
            self::Conference => 'Conférence',
            self::Seminar => 'Séminaire',
            self::Evangelisation => 'Évangélisation',
            self::Concert => 'Concert',
            self::Formation => 'Formation',
            self::Autre => 'Autre',
        };
    }

    /**
     * @return array<string, string> Options pour Select Filament
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

<?php

namespace App\Enums;

/**
 * Largeur d'un champ dans la grille du formulaire public (1 colonne ou pleine largeur).
 */
enum RegistrationFormColumnSpan: string
{
    case Default = 'default';
    case Full = 'full';

    /**
     * Libellé pour l'interface d'administration.
     */
    public function label(): string
    {
        return match ($this) {
            self::Default => '1 colonne',
            self::Full => 'Pleine largeur',
        };
    }
}

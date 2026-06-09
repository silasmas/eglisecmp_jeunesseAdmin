<?php

namespace App\Enums;

/**
 * Type fonctionnel d'un champ du registre (non modifiable par l'admin en phase 1).
 */
enum RegistrationFormFieldType: string
{
    case Text = 'text';
    case Email = 'email';
    case Tel = 'tel';
    case Select = 'select';
    case Date = 'date';
    case File = 'file';
    case Textarea = 'textarea';
    case RadioGroup = 'radio_group';
    case YesNoTextarea = 'yes_no_textarea';

    /**
     * Libellé lisible pour l'administration.
     */
    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texte',
            self::Email => 'E-mail',
            self::Tel => 'Téléphone',
            self::Select => 'Liste déroulante',
            self::Date => 'Date',
            self::File => 'Fichier (image)',
            self::Textarea => 'Zone de texte',
            self::RadioGroup => 'Boutons radio',
            self::YesNoTextarea => 'Oui/Non puis texte',
        };
    }
}

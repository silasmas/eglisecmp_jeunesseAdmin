<?php

namespace App\Enums;

/**
 * Clés stables des champs configurables du formulaire d'inscription publique.
 */
enum RegistrationFormFieldKey: string
{
    case Nom = 'nom';
    case Prenom = 'prenom';
    case Sexe = 'sexe';
    case DateNaissance = 'date_naissance';
    case Telephone = 'telephone';
    case Email = 'email';
    case Photo = 'photo';
    case TelUrgence = 'tel_urgence';
    case GuardianName = 'guardian_name';
    case GuardianPhone = 'guardian_phone';
    case Adresse = 'adresse';
    case Commune = 'commune';
    case Ville = 'ville';
    case Eglise = 'eglise';
    case Departement = 'departement';
    case Hebergement = 'hebergement';
    case Observations = 'observations';

    /**
     * Libellé affiché dans l'administration Filament.
     */
    public function label(): string
    {
        return match ($this) {
            self::Nom => 'Nom',
            self::Prenom => 'Prénom',
            self::Sexe => 'Sexe',
            self::DateNaissance => 'Date de naissance',
            self::Telephone => 'Téléphone principal (WhatsApp)',
            self::Email => 'E-mail',
            self::Photo => 'Photo de profil',
            self::TelUrgence => 'Téléphone d\'urgence',
            self::GuardianName => 'Nom du parent ou tuteur',
            self::GuardianPhone => 'Téléphone du parent ou tuteur',
            self::Adresse => 'Adresse',
            self::Commune => 'Commune',
            self::Ville => 'Ville',
            self::Eglise => 'Église / Assemblée',
            self::Departement => 'Département / Cellule',
            self::Hebergement => 'Type d\'hébergement',
            self::Observations => 'Observations / Besoins particuliers',
        };
    }
}

<?php

namespace App\Support;

use App\Enums\RegistrationFormColumnSpan;
use App\Enums\RegistrationFormFieldKey;
use App\Enums\RegistrationFormFieldType;

/**
 * Registre central des champs du formulaire d'inscription publique retraite.
 */
class RegistrationFieldRegistry
{
    /**
     * @return array<int, RegistrationFieldDefinition>
     */
    public static function all(): array
    {
        static $definitions = null;

        if ($definitions !== null) {
            return $definitions;
        }

        $definitions = [
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Nom,
                apiKey: 'nom',
                step: 0,
                stepLabel: 'Identité',
                type: RegistrationFormFieldType::Text,
                isLocked: true,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 10,
                validationRules: ['string', 'max:100'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Prenom,
                apiKey: 'prenom',
                step: 0,
                stepLabel: 'Identité',
                type: RegistrationFormFieldType::Text,
                isLocked: true,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 20,
                validationRules: ['string', 'max:100'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Sexe,
                apiKey: 'sexe',
                step: 0,
                stepLabel: 'Identité',
                type: RegistrationFormFieldType::Select,
                isLocked: true,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 30,
                validationRules: ['string', 'max:10'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::DateNaissance,
                apiKey: 'date_naissance',
                step: 0,
                stepLabel: 'Identité',
                type: RegistrationFormFieldType::Date,
                isLocked: true,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 40,
                validationRules: ['date'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Telephone,
                apiKey: 'telephone',
                step: 0,
                stepLabel: 'Identité',
                type: RegistrationFormFieldType::Tel,
                isLocked: true,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 50,
                validationRules: ['string', 'max:30'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Email,
                apiKey: 'email',
                step: 0,
                stepLabel: 'Identité',
                type: RegistrationFormFieldType::Email,
                isLocked: true,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 60,
                validationRules: ['email', 'max:254'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Photo,
                apiKey: 'photo',
                step: 0,
                stepLabel: 'Identité',
                type: RegistrationFormFieldType::File,
                isLocked: true,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Full,
                defaultSortOrder: 70,
                validationRules: ['image', 'max:6144'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::TelUrgence,
                apiKey: 'tel_urgence',
                step: 1,
                stepLabel: 'Coordonnées',
                type: RegistrationFormFieldType::Tel,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: false,
                defaultColumnSpan: RegistrationFormColumnSpan::Full,
                defaultSortOrder: 110,
                validationRules: ['string', 'max:30'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::GuardianName,
                apiKey: 'guardian_name',
                step: 1,
                stepLabel: 'Coordonnées',
                type: RegistrationFormFieldType::Text,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: false,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 120,
                validationRules: ['string', 'max:150'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::GuardianPhone,
                apiKey: 'guardian_phone',
                step: 1,
                stepLabel: 'Coordonnées',
                type: RegistrationFormFieldType::Tel,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: false,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 130,
                validationRules: ['string', 'max:30'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Adresse,
                apiKey: 'adresse',
                step: 1,
                stepLabel: 'Coordonnées',
                type: RegistrationFormFieldType::Text,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Full,
                defaultSortOrder: 140,
                validationRules: ['string', 'max:255'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Commune,
                apiKey: 'commune',
                step: 1,
                stepLabel: 'Coordonnées',
                type: RegistrationFormFieldType::Text,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 150,
                validationRules: ['string', 'max:120'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Ville,
                apiKey: 'ville',
                step: 1,
                stepLabel: 'Coordonnées',
                type: RegistrationFormFieldType::Text,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 160,
                validationRules: ['string', 'max:120'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Eglise,
                apiKey: 'eglise',
                step: 2,
                stepLabel: 'Participation',
                type: RegistrationFormFieldType::Text,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: true,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 210,
                validationRules: ['string', 'max:200'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Departement,
                apiKey: 'departement',
                step: 2,
                stepLabel: 'Participation',
                type: RegistrationFormFieldType::Text,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: false,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 220,
                validationRules: ['string', 'max:150'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Hebergement,
                apiKey: 'hebergement',
                step: 2,
                stepLabel: 'Participation',
                type: RegistrationFormFieldType::RadioGroup,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: false,
                defaultColumnSpan: RegistrationFormColumnSpan::Default,
                defaultSortOrder: 230,
                validationRules: ['string', 'in:interne,externe'],
            ),
            new RegistrationFieldDefinition(
                key: RegistrationFormFieldKey::Observations,
                apiKey: 'observations',
                step: 2,
                stepLabel: 'Participation',
                type: RegistrationFormFieldType::YesNoTextarea,
                isLocked: false,
                defaultVisible: true,
                defaultRequired: false,
                defaultColumnSpan: RegistrationFormColumnSpan::Full,
                defaultSortOrder: 240,
                validationRules: ['string', 'max:5000'],
            ),
        ];

        return $definitions;
    }

    /**
     * @return array<string, RegistrationFieldDefinition>
     */
    public static function keyed(): array
    {
        $keyed = [];

        foreach (self::all() as $definition) {
            $keyed[$definition->key->value] = $definition;
        }

        return $keyed;
    }

    /**
     * Recherche une définition par clé.
     */
    public static function find(RegistrationFormFieldKey|string $key): ?RegistrationFieldDefinition
    {
        $value = $key instanceof RegistrationFormFieldKey ? $key->value : $key;

        return self::keyed()[$value] ?? null;
    }

    /**
     * Regroupe les champs par étape pour l'interface d'administration.
     *
     * @return array<int, array{label: string, fields: array<int, RegistrationFieldDefinition>}>
     */
    public static function groupedByStep(): array
    {
        $groups = [];

        foreach (self::all() as $definition) {
            if (! isset($groups[$definition->step])) {
                $groups[$definition->step] = [
                    'label' => $definition->stepLabel,
                    'fields' => [],
                ];
            }

            $groups[$definition->step]['fields'][] = $definition;
        }

        ksort($groups);

        return $groups;
    }
}

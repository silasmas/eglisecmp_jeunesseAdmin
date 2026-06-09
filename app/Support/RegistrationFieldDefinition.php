<?php

namespace App\Support;

use App\Enums\RegistrationFormColumnSpan;
use App\Enums\RegistrationFormFieldKey;
use App\Enums\RegistrationFormFieldType;

/**
 * Définition immuable d'un champ du formulaire d'inscription (registre métier).
 */
readonly class RegistrationFieldDefinition
{
    /**
     * @param  array<int, string>  $validationRules  Règles Laravel sans contrainte required/nullable
     */
    public function __construct(
        public RegistrationFormFieldKey $key,
        public string $apiKey,
        public int $step,
        public string $stepLabel,
        public RegistrationFormFieldType $type,
        public bool $isLocked,
        public bool $defaultVisible,
        public bool $defaultRequired,
        public RegistrationFormColumnSpan $defaultColumnSpan,
        public int $defaultSortOrder,
        public array $validationRules = [],
    ) {}

    /**
     * Libellé affiché dans l'administration.
     */
    public function label(): string
    {
        return $this->key->label();
    }
}

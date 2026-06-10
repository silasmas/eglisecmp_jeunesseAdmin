<?php

namespace App\Services;

use App\Enums\RegistrationFormColumnSpan;
use App\Models\ChurchEvent;
use App\Models\RegistrationFormConfigSet;
use App\Models\RegistrationFormFieldItem;
use App\Support\RegistrationFieldDefinition;
use App\Support\RegistrationFieldRegistry;
use App\Support\RegistrationFormUiSettings;
use Illuminate\Support\Facades\DB;

/**
 * Source de vérité pour la configuration du formulaire d'inscription publique.
 */
class RegistrationFormConfigService
{
    /**
     * Initialise les lignes de champs à partir du registre métier.
     */
    public function seedFieldItems(RegistrationFormConfigSet $configSet, ?RegistrationFormConfigSet $sourceSet = null): void
    {
        $sourceItems = $sourceSet
            ? $sourceSet->fieldItems()->get()->keyBy(fn (RegistrationFormFieldItem $item): string => $item->field_key->value)
            : collect();

        foreach (RegistrationFieldRegistry::all() as $definition) {
            $sourceItem = $sourceItems->get($definition->key->value);

            RegistrationFormFieldItem::query()->updateOrCreate(
                [
                    'reg_form_config_set_id' => $configSet->id,
                    'field_key' => $definition->key->value,
                ],
                [
                    'is_visible' => $sourceItem?->is_visible ?? $definition->defaultVisible,
                    'is_required' => $sourceItem?->is_required ?? $definition->defaultRequired,
                    'column_span' => $sourceItem?->column_span ?? $definition->defaultColumnSpan,
                    'is_admin_unlocked' => $sourceItem?->is_admin_unlocked ?? false,
                    'label_override' => $sourceItem?->label_override,
                    'helper_text_override' => $sourceItem?->helper_text_override,
                    'sort_order' => $sourceItem?->sort_order ?? $definition->defaultSortOrder,
                ]
            );
        }
    }

    /**
     * Résout le jeu source pour une création (reconduction ou modèle par défaut publié).
     */
    public function resolveSourceConfigSetForCreate(?int $sourceConfigSetId): ?RegistrationFormConfigSet
    {
        if ($sourceConfigSetId) {
            $source = RegistrationFormConfigSet::query()
                ->with('fieldItems')
                ->find($sourceConfigSetId);

            if ($source) {
                return $source;
            }
        }

        return $this->resolvePublishedConfigSet(null);
    }

    /**
     * Crée un jeu de configuration pour un événement en copiant le modèle publié.
     */
    public function createConfigSetForEvent(ChurchEvent $event): RegistrationFormConfigSet
    {
        return DB::transaction(function () use ($event): RegistrationFormConfigSet {
            $configSet = RegistrationFormConfigSet::query()->create([
                'church_event_id' => $event->id,
                'name' => "Formulaire — {$event->name}",
                'is_published' => false,
            ]);

            $this->seedFieldItems($configSet, $this->resolvePublishedConfigSet(null));

            return $configSet->fresh(['fieldItems']);
        });
    }

    /**
     * Publie un jeu de configuration (le rend effectif côté formulaire public).
     */
    public function publish(RegistrationFormConfigSet $configSet): RegistrationFormConfigSet
    {
        $configSet->forceFill([
            'is_published' => true,
            'published_at' => now(),
        ])->save();

        return $configSet->fresh(['fieldItems', 'event']);
    }

    /**
     * Résout le jeu publié pour un événement (surcharge événement, sinon modèle par défaut).
     */
    public function resolvePublishedConfigSet(?ChurchEvent $event): ?RegistrationFormConfigSet
    {
        if ($event) {
            $eventSet = RegistrationFormConfigSet::query()
                ->where('church_event_id', $event->id)
                ->where('is_published', true)
                ->with('fieldItems')
                ->first();

            if ($eventSet) {
                return $eventSet;
            }
        }

        return RegistrationFormConfigSet::query()
            ->whereNull('church_event_id')
            ->where('is_published', true)
            ->with('fieldItems')
            ->first();
    }

    /**
     * Retourne la configuration effective (registre + surcharges publiées).
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolvedFieldsForEvent(?ChurchEvent $event): array
    {
        $publishedSet = $this->resolvePublishedConfigSet($event);
        $items = $publishedSet
            ? $publishedSet->fieldItems->keyBy(fn (RegistrationFormFieldItem $item): string => $item->field_key->value)
            : collect();

        $fields = [];

        foreach (RegistrationFieldRegistry::all() as $definition) {
            $fields[] = $this->resolveFieldPayload($definition, $items->get($definition->key->value));
        }

        return $this->sortResolvedFields($fields);
    }

    /**
     * Construit l'état Filament `field_order` (glisser-déposer) à partir des lignes persistées.
     *
     * @param  \Illuminate\Support\Collection<string, RegistrationFormFieldItem>  $itemsByKey
     * @return array<int, array<int, array{field_key: string}>>
     */
    public function buildFieldOrderState(\Illuminate\Support\Collection $itemsByKey): array
    {
        $order = [];

        foreach (RegistrationFieldRegistry::groupedByStep() as $step => $group) {
            $stepIndex = (int) $step;
            $order[$stepIndex] = collect($group['fields'])
                ->map(function (RegistrationFieldDefinition $definition) use ($itemsByKey): array {
                    $item = $itemsByKey->get($definition->key->value);

                    return [
                        'field_key' => $definition->key->value,
                        'sort_order' => $item?->sort_order ?? $definition->defaultSortOrder,
                    ];
                })
                ->sortBy('sort_order')
                ->values()
                ->map(fn (array $row): array => ['field_key' => $row['field_key']])
                ->all();
        }

        return $order;
    }

    /**
     * Déduit les valeurs `sort_order` à partir de l'ordre glissé-déposé dans l'admin.
     *
     * @param  array<int, array<int, array{field_key?: string}>>  $fieldOrder
     * @return array<string, int>
     */
    public function sortOrdersFromFieldOrder(array $fieldOrder): array
    {
        $map = [];

        foreach ($fieldOrder as $step => $rows) {
            if (! is_array($rows)) {
                continue;
            }

            $stepIndex = (int) $step;

            foreach (array_values($rows) as $index => $row) {
                $fieldKey = $row['field_key'] ?? null;

                if (! is_string($fieldKey) || $fieldKey === '') {
                    continue;
                }

                $map[$fieldKey] = ($stepIndex * 1000) + (($index + 1) * 10);
            }
        }

        return $map;
    }

    /**
     * Trie les champs résolus par `sort_order` (étape puis position).
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function sortResolvedFields(array $fields): array
    {
        usort($fields, fn (array $a, array $b): int => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        return $fields;
    }

    /**
     * Payload public consommé par le formulaire d'inscription (API + front).
     *
     * @return array<string, mixed>
     */
    public function toPublicPayload(?ChurchEvent $event): array
    {
        $publishedSet = $this->resolvePublishedConfigSet($event);

        return [
            'fields' => $this->resolvedFieldsForEvent($event),
            'ui_settings' => $this->resolvedUiSettingsForEvent($event),
            'source' => $publishedSet?->isDefaultTemplate() ? 'default' : 'event',
        ];
    }

    /**
     * Paramètres d'interface publiés (blocs ouvrier, parent, moyens de paiement).
     *
     * @return array<string, mixed>
     */
    public function resolvedUiSettingsForEvent(?ChurchEvent $event): array
    {
        $publishedSet = $this->resolvePublishedConfigSet($event);

        return RegistrationFormUiSettings::merge($publishedSet?->ui_settings);
    }

    /**
     * Opérateurs Mobile Money visibles pour l'événement (M-Pesa, Orange, Airtel, Afri…).
     *
     * @return list<array<string, mixed>>
     */
    public function resolvedMobileProvidersForEvent(?ChurchEvent $event): array
    {
        return RegistrationFormUiSettings::filterMobileProviders(
            config('retraite.flexpay_mobile_providers', []),
            $this->resolvedUiSettingsForEvent($event)
        );
    }

    /**
     * Construit les règles de validation Laravel pour l'inscription publique.
     *
     * @return array<string, array<int, mixed>>
     */
    public function buildValidationRules(?ChurchEvent $event): array
    {
        $fields = $this->resolvedFieldsForEvent($event);
        $fieldsByApiKey = collect($fields)->keyBy('api_key');
        $rules = [];

        foreach ($fields as $field) {
            $apiKey = (string) $field['api_key'];
            $fieldRules = $field['is_required'] ? ['required'] : ['nullable'];

            foreach ($field['validation_rules'] as $rule) {
                $fieldRules[] = $rule;
            }

            if ($apiKey === 'date_naissance' && $field['is_required']) {
                $fieldRules[] = 'before_or_equal:'.now()->subYears(15)->toDateString();
            }

            $rules[$apiKey] = $fieldRules;
        }

        $telephoneField = $fieldsByApiKey->get('telephone');
        $telephoneRequired = $telephoneField && $telephoneField['is_required'];

        $rules['indicatif'] = $telephoneRequired
            ? ['required', 'string', 'max:10']
            : ['nullable', 'string', 'max:10'];
        $rules['postnom'] = ['nullable', 'string', 'max:100'];
        $rules['role'] = ['required', 'string', 'max:80'];
        $rules['role_autre'] = ['nullable', 'string', 'max:255'];
        $rules['no_departement'] = ['nullable', 'boolean'];
        $rules['event_id'] = ['nullable', 'exists:events_event,id'];
        $rules['accepted_policy_ids'] = ['nullable', 'array'];
        $rules['accepted_policy_ids.*'] = ['integer', 'exists:retreat_policies,id'];
        $rules['parent_group_mode'] = ['nullable', 'boolean'];
        $rules['parent_contact_email'] = ['nullable', 'email', 'max:254'];
        $rules['parent_contact_phone'] = ['nullable', 'string', 'max:30'];
        $rules['parent_full_name'] = ['nullable', 'string', 'max:150'];
        $rules['parent_verified_token'] = ['nullable', 'string', 'max:120'];
        $rules['same_family_emergency_confirm'] = ['sometimes', 'boolean'];

        return $rules;
    }

    /**
     * Messages de validation personnalisés pour l'inscription publique.
     *
     * @return array<string, string>
     */
    public function validationMessages(): array
    {
        return [
            'date_naissance.before_or_equal' => 'Âge minimum requis : 15 ans.',
            'commune.required' => 'Le champ commune est obligatoire.',
            'adresse.required' => 'Le champ adresse est obligatoire.',
            'photo.required' => 'La photo est obligatoire pour poursuivre.',
            'photo.image' => 'Le fichier photo doit être une image valide.',
            'parent_contact_email.email' => 'Adresse e-mail parent/tuteur invalide.',
        ];
    }

    /**
     * Indique si un champ verrouillé est configurable (déverrouillé par un admin).
     */
    public function isFieldConfigurable(RegistrationFieldDefinition $definition, ?RegistrationFormFieldItem $item): bool
    {
        if (! $definition->isLocked) {
            return true;
        }

        return (bool) ($item?->is_admin_unlocked ?? false);
    }

    /**
     * Résout un champ à partir du brouillon du formulaire Filament (aperçu temps réel).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function resolveDraftFieldPayload(
        RegistrationFieldDefinition $definition,
        array $payload,
        bool $canUnlock,
        ?int $sortOrder = null,
    ): array {
        $isAdminUnlocked = false;

        if ($definition->isLocked) {
            $isAdminUnlocked = $canUnlock && (bool) ($payload['is_admin_unlocked'] ?? false);
        }

        if ($definition->isLocked && ! $isAdminUnlocked) {
            $isVisible = true;
            $isRequired = true;
            $columnSpan = $definition->defaultColumnSpan;
        } else {
            $isVisible = (bool) ($payload['is_visible'] ?? $definition->defaultVisible);
            $isRequired = (bool) ($payload['is_required'] ?? $definition->defaultRequired);

            if (! $isVisible) {
                $isRequired = false;
            }

            $columnSpanValue = (string) ($payload['column_span'] ?? $definition->defaultColumnSpan->value);
            $columnSpan = RegistrationFormColumnSpan::tryFrom($columnSpanValue) ?? $definition->defaultColumnSpan;
        }

        $labelOverride = isset($payload['label_override']) ? trim((string) $payload['label_override']) : null;
        $labelOverride = $labelOverride !== '' ? $labelOverride : null;

        $helperOverride = isset($payload['helper_text_override']) ? trim((string) $payload['helper_text_override']) : null;
        $helperOverride = $helperOverride !== '' ? $helperOverride : null;

        $draftItem = new RegistrationFormFieldItem([
            'field_key' => $definition->key->value,
            'is_visible' => $isVisible,
            'is_required' => $isRequired,
            'column_span' => $columnSpan,
            'is_admin_unlocked' => $isAdminUnlocked,
            'label_override' => $labelOverride,
            'helper_text_override' => $helperOverride,
            'sort_order' => $sortOrder ?? $definition->defaultSortOrder,
        ]);

        return $this->resolveFieldPayload($definition, $draftItem);
    }

    /**
     * Fusionne registre et ligne persistée pour un champ.
     *
     * @return array<string, mixed>
     */
    public function resolveFieldPayload(
        RegistrationFieldDefinition $definition,
        ?RegistrationFormFieldItem $item,
    ): array {
        $isConfigurable = $this->isFieldConfigurable($definition, $item);

        $isVisible = $isConfigurable
            ? ($item?->is_visible ?? $definition->defaultVisible)
            : true;

        $isRequired = $isConfigurable
            ? ($item?->is_required ?? $definition->defaultRequired)
            : true;

        if (! $isVisible) {
            $isRequired = false;
        }

        $columnSpan = $isConfigurable
            ? ($item?->column_span ?? $definition->defaultColumnSpan)
            : ($item?->column_span ?? $definition->defaultColumnSpan);

        $defaultLabel = $definition->label();
        $labelOverride = filled($item?->label_override) ? trim((string) $item->label_override) : null;

        return [
            'key' => $definition->key->value,
            'api_key' => $definition->apiKey,
            'label' => $labelOverride ?? $defaultLabel,
            'default_label' => $defaultLabel,
            'label_override' => $labelOverride,
            'helper_text' => filled($item?->helper_text_override) ? trim((string) $item->helper_text_override) : null,
            'step' => $definition->step,
            'step_label' => $definition->stepLabel,
            'type' => $definition->type->value,
            'type_label' => $definition->type->label(),
            'is_locked' => $definition->isLocked,
            'is_admin_unlocked' => (bool) ($item?->is_admin_unlocked ?? false),
            'is_configurable' => $isConfigurable,
            'is_visible' => $isVisible,
            'is_required' => $isRequired,
            'column_span' => $columnSpan->value,
            'sort_order' => $item?->sort_order ?? $definition->defaultSortOrder,
            'validation_rules' => $definition->validationRules,
        ];
    }
}

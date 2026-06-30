<?php

namespace App\Support;

use App\Enums\RegistrationFormFieldType;
use App\Services\RegistrationFormConfigService;

/**
 * Construit les données d'aperçu du formulaire public à partir de l'état du formulaire Filament.
 */
class RegistrationFormPreviewBuilder
{
    /**
     * Libellés des étapes du formulaire public.
     *
     * @return array<int, string>
     */
    public static function stepOptions(): array
    {
        return [
            0 => 'Identité du participant',
            1 => 'Vos coordonnées',
            2 => 'Informations de participation',
            4 => 'Paiement sécurisé',
        ];
    }

    /**
     * Résout les champs visibles pour une étape à partir du brouillon admin (sans enregistrement).
     *
     * @param  array<string, array<string, mixed>>  $items
     * @param  array<int, array<int, array{field_key?: string}>>  $fieldOrder
     * @return array<int, array<string, mixed>>
     */
    public static function fieldsForStep(
        array $items,
        int $step,
        bool $canUnlock,
        array $fieldOrder = [],
        ?array $uiSettings = null,
    ): array {
        $service = app(RegistrationFormConfigService::class);
        $sortMap = $service->sortOrdersFromFieldOrder($fieldOrder);
        $resolved = [];

        foreach (RegistrationFieldRegistry::all() as $definition) {
            if ($definition->step !== $step) {
                continue;
            }

            $payload = $items[$definition->key->value] ?? [];
            $sortOrder = $sortMap[$definition->key->value] ?? null;
            $resolved[] = $service->resolveDraftFieldPayload($definition, $payload, $canUnlock, $sortOrder);
        }

        $resolved = $service->sortResolvedFields($resolved);

        if ($step === 0 && $uiSettings !== null) {
            $resolved = RegistrationFormUiSettings::applyContactCoordinationToFields(
                $resolved,
                RegistrationFormUiSettings::merge($uiSettings)
            );
        }

        return $resolved;
    }

    /**
     * Données d'aperçu des blocs spéciaux (ouvrier, parent, paiement) pour une étape.
     *
     * @param  array<string, mixed>  $uiSettings
     * @return array<string, mixed>
     */
    public static function uiBlocksForStep(int $step, array $uiSettings): array
    {
        $ui = RegistrationFormUiSettings::merge($uiSettings);

        if ($step === 0) {
            return [
                'worker' => [
                    'is_visible' => (bool) ($ui['worker_prefill']['is_visible'] ?? true),
                    'position' => $ui['worker_prefill']['position'] ?? RegistrationFormUiSettings::POSITION_BEFORE_FIELDS,
                ],
            ];
        }

        if ($step === 1) {
            return [
                'parent' => [
                    'is_visible' => (bool) ($ui['parent_multi_child']['is_visible'] ?? true),
                    'position' => $ui['parent_multi_child']['position'] ?? RegistrationFormUiSettings::POSITION_BEFORE_FIELDS,
                ],
            ];
        }

        if ($step === 4) {
            $modes = [];

            foreach ($ui['payment_modes_order'] as $mode) {
                $modes[] = [
                    'key' => $mode,
                    'label' => RegistrationFormUiSettings::PAYMENT_MODE_LABELS[$mode] ?? $mode,
                    'is_visible' => (bool) ($ui['payment_modes'][$mode]['is_visible'] ?? true),
                ];
            }

            $mobileProviders = [];

            foreach ($ui['mobile_money_providers_order'] as $code) {
                $mobileProviders[] = [
                    'code' => $code,
                    'label' => RegistrationFormUiSettings::mobileProviderLabels()[$code] ?? $code,
                    'is_visible' => RegistrationFormUiSettings::isMobileProviderVisible($ui, $code),
                ];
            }

            return [
                'payment_modes' => $modes,
                'mobile_providers' => $mobileProviders,
                'mobile_money_visible' => RegistrationFormUiSettings::isPaymentModeVisible($ui, 'mobile_money'),
            ];
        }

        return [];
    }

    /**
     * Libellé court du type de champ pour l'aperçu.
     */
    public static function inputPlaceholder(RegistrationFormFieldType $type): string
    {
        return match ($type) {
            RegistrationFormFieldType::Email => 'exemple@domaine.com',
            RegistrationFormFieldType::Tel => '+243 …',
            RegistrationFormFieldType::Date => 'jj/mm/aaaa',
            RegistrationFormFieldType::File => 'Zone photo / fichier',
            RegistrationFormFieldType::Textarea => 'Texte libre…',
            RegistrationFormFieldType::Select => 'Sélection…',
            RegistrationFormFieldType::RadioGroup => 'Option A · Option B',
            RegistrationFormFieldType::YesNoTextarea => 'Précisez si Oui…',
            default => 'Saisie texte…',
        };
    }
}

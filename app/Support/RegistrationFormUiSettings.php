<?php

namespace App\Support;

/**
 * Paramètres d'interface du formulaire d'inscription (blocs ouvrier, parent, paiement).
 */
class RegistrationFormUiSettings
{
    public const POSITION_BEFORE_FIELDS = 'before_fields';

    public const POSITION_AFTER_FIELDS = 'after_fields';

    /** @var list<string> */
    public const PAYMENT_MODE_KEYS = ['mobile_money', 'card', 'cash'];

    /** @var array<string, string> */
    public const PAYMENT_MODE_LABELS = [
        'mobile_money' => 'Mobile money',
        'card' => 'Carte bancaire',
        'cash' => 'Espèces (cash)',
    ];

    /**
     * Opérateurs Mobile Money déclarés dans la configuration FlexPay.
     *
     * @return list<array{type: string, code: string, label: string, msisdn_regex?: string}>
     */
    public static function configuredMobileProviders(): array
    {
        $providers = [];

        foreach (config('retraite.flexpay_mobile_providers', []) as $provider) {
            if (! is_array($provider)) {
                continue;
            }

            $code = self::providerCode($provider);

            if ($code === '') {
                continue;
            }

            $providers[] = [
                'type' => (string) ($provider['type'] ?? ''),
                'code' => $code,
                'label' => (string) ($provider['label'] ?? $code),
                'msisdn_regex' => isset($provider['msisdn_regex']) ? (string) $provider['msisdn_regex'] : '',
            ];
        }

        return $providers;
    }

    /**
     * @param  array<string, mixed>  $provider Définition opérateur
     * @return string Code stable (mpesa, airtel, orange, afri…)
     */
    public static function providerCode(array $provider): string
    {
        $code = trim((string) ($provider['code'] ?? ''));

        if ($code !== '') {
            return $code;
        }

        return trim((string) ($provider['type'] ?? ''));
    }

    /**
     * @return list<string> Codes opérateurs dans l'ordre de configuration .env
     */
    public static function defaultMobileProviderCodes(): array
    {
        return array_map(
            fn (array $provider): string => self::providerCode($provider),
            self::configuredMobileProviders()
        );
    }

    /**
     * @return array<string, array{is_visible: bool}>
     */
    public static function defaultMobileProviderVisibility(): array
    {
        $visibility = [];

        foreach (self::defaultMobileProviderCodes() as $code) {
            $visibility[$code] = ['is_visible' => true];
        }

        return $visibility;
    }

    /**
     * Valeurs par défaut des blocs configurables hors registre de champs.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'worker_prefill' => [
                'is_visible' => true,
                'position' => self::POSITION_BEFORE_FIELDS,
            ],
            'parent_multi_child' => [
                'is_visible' => true,
                'position' => self::POSITION_BEFORE_FIELDS,
            ],
            'payment_modes' => [
                'mobile_money' => ['is_visible' => true],
                'card' => ['is_visible' => true],
                'cash' => ['is_visible' => true],
            ],
            'payment_modes_order' => self::PAYMENT_MODE_KEYS,
            'mobile_money_providers' => self::defaultMobileProviderVisibility(),
            'mobile_money_providers_order' => self::defaultMobileProviderCodes(),
        ];
    }

    /**
     * Fusionne les paramètres stockés avec les valeurs par défaut.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array<string, mixed>
     */
    public static function merge(?array $stored): array
    {
        $defaults = self::defaults();

        if (! is_array($stored)) {
            return $defaults;
        }

        return [
            'worker_prefill' => [
                'is_visible' => (bool) ($stored['worker_prefill']['is_visible'] ?? $defaults['worker_prefill']['is_visible']),
                'position' => self::normalizeBlockPosition($stored['worker_prefill']['position'] ?? null),
            ],
            'parent_multi_child' => [
                'is_visible' => (bool) ($stored['parent_multi_child']['is_visible'] ?? $defaults['parent_multi_child']['is_visible']),
                'position' => self::normalizeBlockPosition($stored['parent_multi_child']['position'] ?? null),
            ],
            'payment_modes' => [
                'mobile_money' => [
                    'is_visible' => (bool) ($stored['payment_modes']['mobile_money']['is_visible'] ?? $defaults['payment_modes']['mobile_money']['is_visible']),
                ],
                'card' => [
                    'is_visible' => (bool) ($stored['payment_modes']['card']['is_visible'] ?? $defaults['payment_modes']['card']['is_visible']),
                ],
                'cash' => [
                    'is_visible' => (bool) ($stored['payment_modes']['cash']['is_visible'] ?? $defaults['payment_modes']['cash']['is_visible']),
                ],
            ],
            'payment_modes_order' => self::normalizePaymentModesOrder($stored['payment_modes_order'] ?? null),
            'mobile_money_providers' => self::mergeMobileProviderVisibility($stored['mobile_money_providers'] ?? null),
            'mobile_money_providers_order' => self::normalizeMobileProvidersOrder($stored['mobile_money_providers_order'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return array<string, array{is_visible: bool}>
     */
    public static function mergeMobileProviderVisibility(?array $stored): array
    {
        $defaults = self::defaultMobileProviderVisibility();
        $merged = [];

        foreach (self::defaultMobileProviderCodes() as $code) {
            $merged[$code] = [
                'is_visible' => (bool) ($stored[$code]['is_visible'] ?? $defaults[$code]['is_visible'] ?? true),
            ];
        }

        return $merged;
    }

    /**
     * État Filament pour le repeater d'ordre des moyens de paiement.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array<int, array{mode: string}>
     */
    public static function paymentModesOrderState(?array $stored): array
    {
        $order = self::normalizePaymentModesOrder($stored['payment_modes_order'] ?? null);

        return array_map(
            fn (string $mode): array => ['mode' => $mode],
            $order
        );
    }

    /**
     * Extrait l'ordre des moyens de paiement depuis le repeater Filament.
     *
     * @param  array<int, array{mode?: string}>|null  $rows
     * @return list<string>
     */
    public static function paymentModesOrderFromRepeater(?array $rows): array
    {
        if (! is_array($rows)) {
            return self::PAYMENT_MODE_KEYS;
        }

        $order = [];

        foreach ($rows as $row) {
            $mode = $row['mode'] ?? null;

            if (is_string($mode) && in_array($mode, self::PAYMENT_MODE_KEYS, true)) {
                $order[] = $mode;
            }
        }

        return self::normalizePaymentModesOrder($order);
    }

    /**
     * @return list<string>
     */
    public static function normalizePaymentModesOrder(mixed $order): array
    {
        if (! is_array($order)) {
            return self::PAYMENT_MODE_KEYS;
        }

        $normalized = [];

        foreach ($order as $mode) {
            if (is_string($mode) && in_array($mode, self::PAYMENT_MODE_KEYS, true) && ! in_array($mode, $normalized, true)) {
                $normalized[] = $mode;
            }
        }

        foreach (self::PAYMENT_MODE_KEYS as $mode) {
            if (! in_array($mode, $normalized, true)) {
                $normalized[] = $mode;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    public static function blockPositionOptions(): array
    {
        return [
            self::POSITION_BEFORE_FIELDS => 'Avant les champs de l\'étape',
            self::POSITION_AFTER_FIELDS => 'Après les champs de l\'étape',
        ];
    }

    /**
     * @param  mixed  $position
     */
    public static function normalizeBlockPosition(mixed $position): string
    {
        return $position === self::POSITION_AFTER_FIELDS
            ? self::POSITION_AFTER_FIELDS
            : self::POSITION_BEFORE_FIELDS;
    }

    /**
     * Indique si un moyen de paiement est affiché sur le formulaire public.
     *
     * @param  array<string, mixed>|null  $uiSettings Paramètres fusionnés
     * @param  string  $mode Clé du mode (mobile_money, card, cash)
     * @return bool Vrai si le mode est visible
     */
    public static function isPaymentModeVisible(?array $uiSettings, string $mode): bool
    {
        if (! in_array($mode, self::PAYMENT_MODE_KEYS, true)) {
            return false;
        }

        $ui = self::merge($uiSettings);

        return (bool) ($ui['payment_modes'][$mode]['is_visible'] ?? true);
    }

    /**
     * @param  array<string, mixed>|null  $uiSettings Paramètres fusionnés
     * @return list<string> Modes visibles dans l'ordre configuré
     */
    public static function visiblePaymentModes(?array $uiSettings): array
    {
        $ui = self::merge($uiSettings);
        $visible = [];

        foreach ($ui['payment_modes_order'] as $mode) {
            if (self::isPaymentModeVisible($ui, $mode)) {
                $visible[] = $mode;
            }
        }

        return $visible;
    }

    /**
     * @param  array<string, mixed>|null  $uiSettings Paramètres fusionnés
     * @return bool Au moins un moyen de paiement reste visible
     */
    public static function hasVisiblePaymentMode(?array $uiSettings): bool
    {
        return self::visiblePaymentModes($uiSettings) !== [];
    }

    /**
     * État Filament pour le repeater d'ordre des opérateurs Mobile Money.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array<int, array{code: string}>
     */
    public static function mobileProvidersOrderState(?array $uiSettings): array
    {
        $ui = self::merge($uiSettings);

        return array_map(
            fn (string $code): array => ['code' => $code],
            $ui['mobile_money_providers_order']
        );
    }

    /**
     * @param  array<int, array{code?: string}>|null  $rows
     * @return list<string>
     */
    public static function mobileProvidersOrderFromRepeater(?array $rows): array
    {
        if (! is_array($rows)) {
            return self::defaultMobileProviderCodes();
        }

        $order = [];

        foreach ($rows as $row) {
            $code = $row['code'] ?? null;

            if (is_string($code) && in_array($code, self::defaultMobileProviderCodes(), true)) {
                $order[] = $code;
            }
        }

        return self::normalizeMobileProvidersOrder($order);
    }

    /**
     * @return list<string>
     */
    public static function normalizeMobileProvidersOrder(mixed $order): array
    {
        $known = self::defaultMobileProviderCodes();

        if (! is_array($order)) {
            return $known;
        }

        $normalized = [];

        foreach ($order as $code) {
            if (is_string($code) && in_array($code, $known, true) && ! in_array($code, $normalized, true)) {
                $normalized[] = $code;
            }
        }

        foreach ($known as $code) {
            if (! in_array($code, $normalized, true)) {
                $normalized[] = $code;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $uiSettings Paramètres fusionnés
     * @param  string  $code Code opérateur (mpesa, airtel…)
     * @return bool Vrai si l'opérateur est affiché
     */
    public static function isMobileProviderVisible(?array $uiSettings, string $code): bool
    {
        $ui = self::merge($uiSettings);

        return (bool) ($ui['mobile_money_providers'][$code]['is_visible'] ?? true);
    }

    /**
     * @param  array<string, mixed>|null  $uiSettings Paramètres fusionnés
     * @return list<string> Codes opérateurs visibles dans l'ordre configuré
     */
    public static function visibleMobileProviderCodes(?array $uiSettings): array
    {
        $ui = self::merge($uiSettings);
        $visible = [];

        foreach ($ui['mobile_money_providers_order'] as $code) {
            if (self::isMobileProviderVisible($ui, $code)) {
                $visible[] = $code;
            }
        }

        return $visible;
    }

    /**
     * @param  array<string, mixed>|null  $uiSettings Paramètres fusionnés
     * @return bool Au moins un opérateur Mobile Money reste visible
     */
    public static function hasVisibleMobileProvider(?array $uiSettings): bool
    {
        return self::visibleMobileProviderCodes($uiSettings) !== [];
    }

    /**
     * Filtre et ordonne les opérateurs FlexPay selon la configuration publiée.
     *
     * @param  list<array<string, mixed>>  $providers Opérateurs bruts (.env / config)
     * @param  array<string, mixed>|null  $uiSettings Paramètres fusionnés
     * @return list<array<string, mixed>>
     */
    public static function filterMobileProviders(array $providers, ?array $uiSettings): array
    {
        $ui = self::merge($uiSettings);
        $byCode = [];

        foreach ($providers as $provider) {
            if (! is_array($provider)) {
                continue;
            }

            $code = self::providerCode($provider);

            if ($code !== '') {
                $byCode[$code] = $provider;
            }
        }

        $filtered = [];

        foreach ($ui['mobile_money_providers_order'] as $code) {
            if (! self::isMobileProviderVisible($ui, $code)) {
                continue;
            }

            if (isset($byCode[$code])) {
                $filtered[] = $byCode[$code];
            }
        }

        return $filtered;
    }

    /**
     * @return array<string, string> Libellés opérateurs pour l'admin
     */
    public static function mobileProviderLabels(): array
    {
        $labels = [];

        foreach (self::configuredMobileProviders() as $provider) {
            $labels[$provider['code']] = $provider['label'];
        }

        return $labels;
    }
}

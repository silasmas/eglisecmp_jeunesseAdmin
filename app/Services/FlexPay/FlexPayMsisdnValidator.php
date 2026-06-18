<?php

namespace App\Services\FlexPay;

use App\Models\ChurchEvent;
use App\Services\RegistrationFormConfigService;

/**
 * Normalisation MSISDN RDC et validation par opérateur FlexPay.
 */
class FlexPayMsisdnValidator
{
    public function __construct(
        protected RegistrationFormConfigService $formConfigService,
    ) {}

    /**
     * Mobile Money RDC : uniquement chiffres, préfixe 243 (12 caractères).
     *
     * @param string $raw Numéro saisi
     * @return string MSISDN normalisé
     */
    public function normalizeCdMobileMoneyMsisdn(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', trim($raw));
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '243'.substr($digits, 1);
        }
        if (! str_starts_with($digits, '243')) {
            $digits = '243'.ltrim($digits, '0');
        }

        return $digits;
    }

    /**
     * Vérifie que le MSISDN correspond au réseau FlexPay sélectionné.
     *
     * @param ChurchEvent $event Événement retraite
     * @param string $flexpayType Type ou code opérateur
     * @param string $normalizedMsisdn MSISDN normalisé
     * @return bool
     */
    public function msisdnMatchesFlexpayMobileType(ChurchEvent $event, string $flexpayType, string $normalizedMsisdn): bool
    {
        $providers = $this->formConfigService->resolvedMobileProvidersForEvent($event);
        foreach ($providers as $provider) {
            if (! is_array($provider)) {
                continue;
            }
            $type = isset($provider['type']) ? (string) $provider['type'] : '';
            $code = isset($provider['code']) ? (string) $provider['code'] : '';
            if ($type !== (string) $flexpayType && $code !== (string) $flexpayType) {
                continue;
            }
            $regex = isset($provider['msisdn_regex']) ? trim((string) $provider['msisdn_regex']) : '';
            if ($regex === '') {
                return preg_match('/^243\d{9}$/', $normalizedMsisdn) === 1;
            }

            return preg_match('#'.$regex.'#', $normalizedMsisdn) === 1;
        }

        return preg_match('/^243\d{9}$/', $normalizedMsisdn) === 1;
    }

    /**
     * Vérifie que l'opérateur est autorisé pour l'événement.
     *
     * @param ChurchEvent $event Événement retraite
     * @param string $flexpayType Type ou code opérateur
     * @return bool
     */
    public function isMobileProviderAllowed(ChurchEvent $event, string $flexpayType): bool
    {
        foreach ($this->formConfigService->resolvedMobileProvidersForEvent($event) as $provider) {
            if (! is_array($provider)) {
                continue;
            }
            $type = isset($provider['type']) ? (string) $provider['type'] : '';
            $code = isset($provider['code']) ? (string) $provider['code'] : '';
            if ($type === (string) $flexpayType || $code === (string) $flexpayType) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Support;

/**
 * URLs absolues pour les e-mails retraite (portail, admin, billets).
 */
class RetreatMailUrl
{
    /**
     * URL publique de base (doit inclure le sous-dossier /public si l'app y est déployée).
     */
    public static function base(): string
    {
        $configured = trim((string) config('retraite.public_base_url', ''));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Portail d'accueil / vérification ouvrier.
     */
    public static function portal(): string
    {
        return self::base().'/';
    }

    /**
     * Administration Filament.
     */
    public static function admin(): string
    {
        return self::base().'/admin';
    }

    /**
     * Portail d'inscription retraite.
     */
    public static function inscription(): string
    {
        return self::base().'/inscription-retraite';
    }

    /**
     * Lien court portail inscription (SMS).
     */
    public static function shortInscription(): string
    {
        return self::base().'/i';
    }

    /**
     * Lien court billet SMS (/b/{token}).
     *
     * @param  string  $token  download_token 32 car.
     */
    public static function shortBillet(string $token): string
    {
        return self::base().'/b/'.rawurlencode($token);
    }

    /**
     * Lien court accès SMS (/a/{token}).
     *
     * @param  string  $token  download_token 32 car.
     */
    public static function shortAcces(string $token): string
    {
        return self::base().'/a/'.rawurlencode($token);
    }

    /**
     * Lien court justificatif SMS (/j/{token}).
     *
     * @param  string  $token  download_token 32 car.
     */
    public static function shortJustificatif(string $token): string
    {
        return self::base().'/j/'.rawurlencode($token);
    }

    /**
     * Génère une URL nommée absolue avec la base publique configurée.
     *
     * @param  string  $name  Nom de route Laravel
     * @param  array<string, mixed>  $parameters  Paramètres de route
     */
    public static function route(string $name, array $parameters = []): string
    {
        $path = route($name, $parameters, false);

        return self::base().'/'.ltrim($path, '/');
    }

    /**
     * URL absolue d'une route API publique (webhooks FlexPay, etc.).
     *
     * @param  string  $path  Chemin relatif après /api/ (ex. v1/retreat/inscription/webhooks/flexpay-callback)
     */
    public static function api(string $path): string
    {
        return self::base().'/api/'.ltrim($path, '/');
    }

    /**
     * Webhook FlexPay pour les paiements inscription retraite.
     */
    public static function flexpayInscriptionWebhook(): string
    {
        return self::api('v1/retreat/inscription/webhooks/flexpay-callback');
    }
}

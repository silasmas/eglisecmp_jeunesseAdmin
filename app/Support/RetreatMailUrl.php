<?php

namespace App\Support;

/**
 * URLs absolues pour les e-mails retraite (portail, admin, billets).
 */
class RetreatMailUrl
{
    /**
     * URL publique de base (doit inclure le sous-dossier /public si l'app y est déployée).
     *
     * @return string
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
     *
     * @return string
     */
    public static function portal(): string
    {
        return self::base().'/';
    }

    /**
     * Administration Filament.
     *
     * @return string
     */
    public static function admin(): string
    {
        return self::base().'/admin';
    }

    /**
     * Portail d'inscription retraite.
     *
     * @return string
     */
    public static function inscription(): string
    {
        return self::base().'/inscription-retraite';
    }

    /**
     * Génère une URL nommée absolue avec la base publique configurée.
     *
     * @param string $name Nom de route Laravel
     * @param array<string, mixed> $parameters Paramètres de route
     * @return string
     */
    public static function route(string $name, array $parameters = []): string
    {
        $path = route($name, $parameters, false);

        return self::base().'/'.ltrim($path, '/');
    }
}

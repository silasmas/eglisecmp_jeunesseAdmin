<?php

namespace App\Support;

/**
 * Logos disponibles au centre des QR codes générés.
 */
final class QrCodeLogoCatalog
{
    public const KEY_JEUNESSE = 'jeunesse';

    public const KEY_CMP = 'cmp';

    /**
     * @return array<string, array{label: string, path: string}>
     */
    public static function options(): array
    {
        return [
            self::KEY_JEUNESSE => [
                'label' => 'Département de la Jeunesse',
                'path' => 'assets/qr-logos/logo-jeunesse.png',
            ],
            self::KEY_CMP => [
                'label' => 'Centre Missionnaire Philadelphie (CMP)',
                'path' => 'assets/qr-logos/logo-cmp.png',
            ],
        ];
    }

    /**
     * @param string|null $key Clé logo (jeunesse, cmp)
     * @return string|null Chemin absolu du fichier ou null
     */
    public static function resolveAbsolutePath(?string $key): ?string
    {
        $options = self::options();
        $resolvedKey = $key && isset($options[$key]) ? $key : self::KEY_JEUNESSE;
        $relative = $options[$resolvedKey]['path'];
        $absolute = public_path($relative);

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * @return array<string, string> Options pour Select Filament [key => label]
     */
    public static function selectOptions(): array
    {
        return collect(self::options())
            ->mapWithKeys(fn (array $row, string $key): array => [$key => $row['label']])
            ->all();
    }
}

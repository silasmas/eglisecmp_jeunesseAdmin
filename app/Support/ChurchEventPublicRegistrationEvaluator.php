<?php

namespace App\Support;

use App\Enums\ChurchEventType;
use App\Models\ChurchEvent;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\HtmlString;

/**
 * Évalue si le formulaire d'inscription retraite est ouvert et produit l'indicateur admin.
 */
class ChurchEventPublicRegistrationEvaluator
{
    /**
     * @param ChurchEvent $event Événement évalué
     * @return bool Formulaire public ouvert
     */
    public static function isOpen(ChurchEvent $event): bool
    {
        return self::evaluate($event)['open'];
    }

    /**
     * @param ChurchEvent $event Événement
     * @return array{open: bool, label: string, color: string, checks: list<array{key: string, label: string, ok: bool, detail: string}>}
     */
    public static function evaluate(ChurchEvent $event): array
    {
        $now = now();
        $typeOk = strtolower(trim((string) $event->type)) === ChurchEventType::Retraite->value;
        $activeOk = (bool) $event->is_active;
        $portalOpenOk = ! (bool) $event->is_publicly_closed;

        $opensAt = self::parseDate($event->public_registration_opens_at);
        $closesAt = self::resolveRegistrationClosesAt($event);

        $opensOk = $opensAt === null || $now->gte($opensAt);
        $closesOk = $closesAt === null || $now->lte($closesAt);

        $checks = [
            [
                'key' => 'type',
                'label' => 'Type retraite',
                'ok' => $typeOk,
                'detail' => $typeOk ? 'Type = retraite' : 'Type actuel : '.($event->type ?: '—'),
            ],
            [
                'key' => 'active',
                'label' => 'Événement actif',
                'ok' => $activeOk,
                'detail' => $activeOk ? 'Événement courant activé' : 'Activez « Actif (événement courant) »',
            ],
            [
                'key' => 'portal',
                'label' => 'Accès public ouvert',
                'ok' => $portalOpenOk,
                'detail' => $portalOpenOk ? 'Fermeture manuelle désactivée' : '« Fermer l\'accès public » est activé',
            ],
            [
                'key' => 'opens',
                'label' => 'Début inscriptions',
                'ok' => $opensOk,
                'detail' => $opensAt
                    ? ($opensOk ? 'Ouvert depuis le '.$opensAt->format('d/m/Y H:i') : 'Ouverture prévue le '.$opensAt->format('d/m/Y H:i'))
                    : 'Pas de date de début (ouvert immédiatement si le reste est OK)',
            ],
            [
                'key' => 'closes',
                'label' => 'Fin inscriptions',
                'ok' => $closesOk,
                'detail' => $closesAt
                    ? ($closesOk ? 'Fermeture le '.$closesAt->format('d/m/Y H:i') : 'Inscriptions closes depuis le '.$closesAt->format('d/m/Y H:i'))
                    : 'Pas de date de fin d\'inscription',
            ],
        ];

        $open = $typeOk && $activeOk && $portalOpenOk && $opensOk && $closesOk;

        return [
            'open' => $open,
            'label' => $open ? 'Formulaire public ouvert' : 'Formulaire public fermé',
            'color' => $open ? 'success' : 'gray',
            'checks' => $checks,
        ];
    }

    /**
     * Évalue l'état à partir des champs du formulaire Filament (aperçu instantané).
     *
     * @param array<string, mixed> $attributes Attributs du formulaire
     * @return array{open: bool, label: string, color: string, checks: list<array{key: string, label: string, ok: bool, detail: string}>}
     */
    public static function evaluateFromAttributes(array $attributes): array
    {
        $event = new ChurchEvent();
        $event->forceFill([
            'type' => $attributes['type'] ?? null,
            'is_active' => (bool) ($attributes['is_active'] ?? false),
            'is_publicly_closed' => (bool) ($attributes['is_publicly_closed'] ?? false),
            'public_registration_opens_at' => $attributes['public_registration_opens_at'] ?? null,
            'public_registration_closes_at' => $attributes['public_registration_closes_at'] ?? null,
            'end_at' => $attributes['end_at'] ?? null,
        ]);

        return self::evaluate($event);
    }

    /**
     * @param mixed $value Date brute formulaire ou modèle
     * @return CarbonInterface|null
     */
    protected static function parseDate(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param ChurchEvent $event Événement
     * @return CarbonInterface|null Date effective de fermeture des inscriptions
     */
    public static function resolveRegistrationClosesAt(ChurchEvent $event): ?CarbonInterface
    {
        return self::parseDate($event->public_registration_closes_at)
            ?? self::parseDate($event->end_at);
    }

    /**
     * @param array{open: bool, label: string, color: string, checks: list<array{key: string, label: string, ok: bool, detail: string}>} $status Résultat evaluate
     * @return HtmlString Indicateur HTML admin
     */
    public static function renderAdminIndicatorHtml(array $status): HtmlString
    {
        $open = $status['open'];
        $border = $open ? '#146c43' : '#9ca3af';
        $bg = $open ? '#ecfdf3' : '#f3f4f6';
        $icon = $open ? '&#9679;' : '&#9675;';
        $title = e($status['label']);

        $rows = collect($status['checks'])
            ->map(function (array $check): string {
                $mark = $check['ok']
                    ? '<span style="color:#146c43;font-weight:700;">&#10003;</span>'
                    : '<span style="color:#b42318;font-weight:700;">&#10007;</span>';

                return '<li style="margin:0.35rem 0;line-height:1.45;">'
                    .$mark.' <strong>'.e($check['label']).'</strong> — '.e($check['detail'])
                    .'</li>';
            })
            ->implode('');

        return new HtmlString(
            '<div style="border:2px solid '.$border.';background:'.$bg.';border-radius:12px;padding:1rem 1.15rem;">'
            .'<div style="display:flex;align-items:center;gap:0.5rem;font-weight:800;font-size:1rem;color:'.$border.';">'
            .'<span style="font-size:1.1rem;line-height:1;">'.$icon.'</span>'
            .'<span>'.$title.'</span>'
            .'</div>'
            .'<ul style="margin:0.75rem 0 0;padding-left:0;list-style:none;font-size:0.88rem;color:#374151;">'
            .$rows
            .'</ul>'
            .'</div>'
        );
    }
}

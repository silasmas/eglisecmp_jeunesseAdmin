<?php

namespace App\Support;

use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;

/**
 * Libellés et métadonnées pour les e-mails d'affectation encadreur (chambre / atelier).
 */
final class RetreatStaffAssignmentPresentation
{
    private function __construct()
    {
    }

    /**
     * Année de la retraite liée à la chambre ou à l'atelier.
     *
     * @param RetreatAtelier|RetreatChambre $assignment Affectation logistique
     * @return int Année (ex. 2026)
     */
    public static function retreatYear(RetreatAtelier|RetreatChambre $assignment): int
    {
        $assignment->loadMissing('event');

        $startAt = $assignment->event?->start_at;

        if ($startAt !== null) {
            return (int) $startAt->format('Y');
        }

        return (int) now()->format('Y');
    }

    /**
     * Titre complet de l'événement pour l'e-mail d'affectation.
     *
     * @param RetreatAtelier|RetreatChambre $assignment Affectation logistique
     * @return string Ex. « Grande retraite de la jeunesse 2026 »
     */
    public static function retreatTitle(RetreatAtelier|RetreatChambre $assignment): string
    {
        return __('retraite.mail_staff_assignment_retreat_title', [
            'year' => self::retreatYear($assignment),
        ]);
    }

    /**
     * Libellé lisible d'un rôle dashboard Spatie.
     *
     * @param string $roleName Nom du rôle (ouvrier, panel_user, …)
     * @return string Libellé traduit
     */
    public static function dashboardRoleLabel(string $roleName): string
    {
        return match ($roleName) {
            'super_admin' => __('retraite.mail_staff_access_role_super_admin'),
            'panel_user' => __('retraite.mail_staff_access_role_panel_user'),
            'ouvrier' => __('retraite.mail_staff_access_role_ouvrier'),
            default => $roleName,
        };
    }
}

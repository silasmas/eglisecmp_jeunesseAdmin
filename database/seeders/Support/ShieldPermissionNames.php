<?php

namespace Database\Seeders\Support;

/**
 * Liste des permissions Spatie / Filament Shield alignée sur le dump production `cmp_jeunesse`.
 */
final class ShieldPermissionNames
{
    /**
     * @return list<string>
     */
    public static function policyMethods(): array
    {
        return [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'DeleteAny',
            'Restore',
            'ForceDelete',
            'ForceDeleteAny',
            'RestoreAny',
            'Replicate',
            'Reorder',
        ];
    }

    /**
     * @return list<string>
     */
    public static function filamentResources(): array
    {
        return [
            'Role',
            'ChurchEvent',
            'RetreatParticipant',
            'RetreatPayment',
            'RetreatNotification',
            'User',
            'RetreatChambre',
            'RetreatParticipantMovement',
            'RetreatRetreatDetail',
            'RetreatPolicy',
            'RetreatPolicyAcknowledgement',
            'RetreatSession',
            'RetreatAtelier',
            'RetreatActivityPlan',
            'RetreatActivityAttendance',
            'SmsOperator',
            'SmsMessageLog',
            'SmsTemplate',
            'RetreatVoluntaryDonation',
            'ApiDocs',
        ];
    }

    /**
     * Pages Filament contrôlées par Shield (préfixe View:).
     *
     * @return list<string>
     */
    public static function filamentPages(): array
    {
        return [
            'SmsOtpTest',
            'SendSmsCampaign',
            'RetreatInscriptionSurveillance',
            'MyWatchesPage',
        ];
    }

    /**
     * Permissions personnalisées (hors CRUD ressource).
     *
     * @return list<string>
     */
    public static function customPermissions(): array
    {
        return [
            'View:MediaManager',
            'View:BadgeStudio',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $names = self::customPermissions();

        foreach (self::filamentResources() as $resource) {
            foreach (self::policyMethods() as $method) {
                $names[] = "{$method}:{$resource}";
            }
        }

        foreach (self::filamentPages() as $page) {
            $names[] = "View:{$page}";
        }

        return $names;
    }
}

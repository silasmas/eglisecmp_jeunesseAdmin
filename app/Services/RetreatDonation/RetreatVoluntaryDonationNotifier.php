<?php

namespace App\Services\RetreatDonation;

use App\Mail\RetreatVoluntaryDonationDonorMail;
use App\Mail\RetreatVoluntaryDonationMail;
use App\Models\RetreatVoluntaryDonation;
use App\Support\SuperAdminRecipientResolver;
use Illuminate\Support\Facades\Mail;

/**
 * Notifie les super_admin et le donateur par e-mail.
 */
class RetreatVoluntaryDonationNotifier
{
    public function __construct(
        protected SuperAdminRecipientResolver $superAdminRecipients,
    ) {}

    /**
     * Envoie un e-mail à tous les super_admin actifs (une seule fois par don).
     *
     * @param RetreatVoluntaryDonation $donation Don enregistré
     * @return void
     */
    public function notifySuperAdmins(RetreatVoluntaryDonation $donation): void
    {
        if ($donation->admin_notified) {
            return;
        }

        $donation->loadMissing(['event', 'vouchers']);

        foreach ($this->superAdminRecipients->resolveEmailAddresses() as $email) {
            Mail::to($email)->send(new RetreatVoluntaryDonationMail($donation));
        }

        $donation->update(['admin_notified' => true]);
    }

    /**
     * Envoie la confirmation finale au donateur (nature ou espèces payées), avec codes parrainage si applicable.
     *
     * @param RetreatVoluntaryDonation $donation Don enregistré ou payé
     * @return void
     */
    public function notifyDonor(RetreatVoluntaryDonation $donation): void
    {
        if ($donation->donor_notified) {
            return;
        }

        if (
            $donation->donation_kind === RetreatVoluntaryDonation::KIND_CASH
            && $donation->status !== RetreatVoluntaryDonation::STATUS_PAID
        ) {
            return;
        }

        $email = trim((string) ($donation->donor_email ?? ''));
        if ($email === '') {
            return;
        }

        $donation->loadMissing(['event']);

        Mail::to($email)->send(new RetreatVoluntaryDonationDonorMail($donation->fresh(['event'])));

        $donation->update(['donor_notified' => true]);
    }

    /**
     * Prévient les super_admin qu'un don cash attend validation (sans marquer admin_notified).
     *
     * @param RetreatVoluntaryDonation $donation Don avec preuve
     * @return void
     */
    public function notifyCashPendingAdmins(RetreatVoluntaryDonation $donation): void
    {
        $donation->loadMissing(['event']);

        foreach ($this->superAdminRecipients->resolveEmailAddresses() as $email) {
            Mail::to($email)->send(new RetreatVoluntaryDonationMail($donation));
        }
    }

    /**
     * Accusé au donateur : preuve cash reçue, en attente de validation admin (sans codes parrainage).
     *
     * @param RetreatVoluntaryDonation $donation Don concerné
     * @return void
     */
    public function notifyDonorCashSubmitted(RetreatVoluntaryDonation $donation): void
    {
        $email = trim((string) ($donation->donor_email ?? ''));
        if ($email === '') {
            return;
        }

        $donation->loadMissing(['event']);

        Mail::to($email)->send(new RetreatVoluntaryDonationDonorMail($donation->fresh(['event'])));

        // Ne pas marquer donor_notified : un second e-mail part après validation admin.
    }
}

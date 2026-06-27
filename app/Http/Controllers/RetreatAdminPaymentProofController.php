<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert les preuves de paiement cash aux administrateurs autorisés (évite les 403 stockage public).
 */
class RetreatAdminPaymentProofController extends Controller
{
    /**
     * Affiche ou télécharge la preuve de paiement d'un participant.
     *
     * @param Request $request Requête HTTP
     * @param RetreatParticipant $participant Participant concerné
     * @return Response
     */
    public function show(Request $request, RetreatParticipant $participant): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'Authentification requise pour consulter une preuve de paiement.');
        }

        if (! $this->canViewPaymentProof($user, $participant)) {
            abort(403, 'Vous n\'avez pas l\'autorisation de consulter cette preuve de paiement.');
        }

        $path = (string) $participant->preuve_paiement;

        if (blank($path)) {
            abort(404, 'Aucune preuve de paiement enregistrée pour ce participant.');
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return redirect()->away($path);
        }

        $disk = (string) config('cmp.upload_disk', config('filesystems.default'));

        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Fichier de preuve introuvable sur le serveur.');
        }

        return Storage::disk($disk)->response($path);
    }

    /**
     * @param User $user Utilisateur connecté
     * @param RetreatParticipant $participant Participant
     * @return bool Super admin ou droit de consultation participant
     */
    protected function canViewPaymentProof(User $user, RetreatParticipant $participant): bool
    {
        $superAdminRole = (string) config('filament-shield.super_admin.name', 'super_admin');

        if ($user->hasRole($superAdminRole)) {
            return true;
        }

        if ($user->can('View:RetreatParticipant') || $user->can('ViewAny:RetreatParticipant')) {
            return true;
        }

        return false;
    }
}

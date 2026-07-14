<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use App\Models\User;
use App\Support\RetreatBilletPageBuilder;
use App\Support\RetreatPublicPortalGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Prévisualisation admin du billet participant (même rendu que le lien public).
 */
class RetreatAdminParticipantBilletController extends Controller
{
  /**
   * @param Request $request Requête HTTP
   * @param RetreatParticipant $participant Participant
   * @return View
   */
  public function show(Request $request, RetreatParticipant $participant): View
  {
    $user = $request->user();

    if (! $user instanceof User || ! $this->canPreviewBillet($user)) {
      throw new AccessDeniedHttpException('Accès réservé aux administrateurs.');
    }

    $participant->loadMissing(['event', 'payments']);

    if (RetreatPublicPortalGate::isEventPubliclyClosed($participant->event)) {
      return RetreatPublicPortalGate::participantEventClosedView($participant);
    }

    return RetreatBilletPageBuilder::render($participant);
  }

  /**
   * @param User $user Utilisateur connecté
   * @return bool
   */
  protected function canPreviewBillet(User $user): bool
  {
    $superAdminRole = (string) config('filament-shield.super_admin.name', 'super_admin');

    if ($user->hasRole($superAdminRole)) {
      return true;
    }

    return $user->can('View:RetreatParticipant') || $user->can('ViewAny:RetreatParticipant');
  }
}

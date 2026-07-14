<?php

namespace App\Support;

use App\Models\RetreatParticipant;
use Illuminate\Contracts\View\View;

/**
 * Prépare les données communes de la page billet participant.
 */
final class RetreatBilletPageBuilder
{
  /**
   * @param RetreatParticipant $participant Participant payé
   * @return array<string, mixed> Données passées à la vue billet
   */
  public static function viewData(RetreatParticipant $participant): array
  {
    $participant->loadMissing(['event', 'payments.event', 'chambre', 'atelier']);

    $payment = $participant->payments->sortByDesc('id')->first();
    $showPlacements = RetreatPlacementVisibility::shouldReveal($participant);
    $paymentReference = $payment?->reference;

    return [
      'participant' => $participant,
      'payment' => $payment,
      'accessUrl' => route('retraite.inscription.acces', ['token' => $participant->download_token], absolute: true),
      'showPlacements' => $showPlacements,
      'placementsPendingMessage' => $showPlacements ? null : RetreatPlacementVisibility::pendingMessage($participant),
      'participantDocuments' => ChurchEventParticipantDocuments::entries($participant->event),
      'ticketName' => RetreatBilletPresentation::displayName($participant),
      'ticketStatus' => RetreatBilletPresentation::statusLabel($participant),
      'ticketHebergement' => RetreatBilletPresentation::hebergementLabel($participant),
      'ticketCode' => RetreatBilletPresentation::ticketCode($participant, $paymentReference),
      'rulesDocument' => config('retraite_billet_documents.rules', []),
      'itemsDocument' => config('retraite_billet_documents.items', []),
    ];
  }

  /**
   * @param RetreatParticipant $participant Participant
   * @return View Vue billet rendue
   */
  public static function render(RetreatParticipant $participant): View
  {
    return view('retraite-inscription.billet', self::viewData($participant));
  }

  /**
   * URL publique du billet (null si token ou paiement manquant).
   *
   * @param RetreatParticipant|null $participant Participant
   * @return string|null
   */
  public static function publicUrl(?RetreatParticipant $participant): ?string
  {
    if ($participant === null || ! $participant->paiement_valide || blank($participant->download_token)) {
      return null;
    }

    return RetreatMailUrl::route('retraite.inscription.billet', [
      'token' => $participant->download_token,
    ]);
  }

  /**
   * URL admin de prévisualisation (même rendu que le billet public).
   *
   * @param RetreatParticipant|null $participant Participant
   * @return string|null
   */
  public static function adminPreviewUrl(?RetreatParticipant $participant): ?string
  {
    if ($participant === null || blank($participant->download_token)) {
      return null;
    }

    return route('retreat.admin.billet-preview', ['participant' => $participant->id]);
  }
}

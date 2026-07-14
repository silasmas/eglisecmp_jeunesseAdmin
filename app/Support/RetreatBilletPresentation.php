<?php

namespace App\Support;

use App\Models\RetreatParticipant;

/**
 * Libellés affichés sur le billet visuel (nom, statut, hébergement, code).
 */
final class RetreatBilletPresentation
{
  /**
   * Nom affiché sur le billet.
   *
   * @param RetreatParticipant $participant Participant
   * @return string
   */
  public static function displayName(RetreatParticipant $participant): string
  {
    $full = trim($participant->full_name);

    if ($full !== '') {
      return $full;
    }

    return trim(($participant->prenom ?? '').' '.($participant->nom ?? '')) ?: 'Participant';
  }

  /**
   * Statut / rôle affiché sur le billet.
   *
   * @param RetreatParticipant $participant Participant
   * @return string
   */
  public static function statusLabel(RetreatParticipant $participant): string
  {
    if (filled($participant->role_participant)) {
      return (string) $participant->role_participant;
    }

    $role = strtolower(trim((string) $participant->role));

    if (filled($role) && ! in_array($role, ['participant', 'autre', 'other'], true)) {
      return ucfirst($role);
    }

    $type = strtolower(trim((string) $participant->participant_type));

    return match (true) {
      in_array($type, ['worker', 'ouvrier'], true) => 'Ouvrier',
      in_array($type, ['external', 'externe'], true) => 'Externe',
      in_array($type, ['encadrant', 'staff'], true) => 'Encadrant(e)',
      default => 'Participant(e)',
    };
  }

  /**
   * Libellé hébergement pour le billet.
   *
   * @param RetreatParticipant $participant Participant
   * @return string
   */
  public static function hebergementLabel(RetreatParticipant $participant): string
  {
    $choice = strtolower(trim((string) $participant->hebergement_choice));

    return match (true) {
      str_starts_with($choice, 'ext') => 'Externe',
      str_starts_with($choice, 'int') => 'Interne',
      filled($participant->hebergement_choice) => ucfirst($choice),
      default => '—',
    };
  }

  /**
   * Code court affiché sous le QR du billet.
   *
   * @param RetreatParticipant $participant Participant
   * @param string|null $paymentReference Référence paiement
   * @return string
   */
  public static function ticketCode(RetreatParticipant $participant, ?string $paymentReference = null): string
  {
    if (filled($paymentReference)) {
      return (string) $paymentReference;
    }

    return 'CMP-'.str_pad((string) $participant->id, 4, '0', STR_PAD_LEFT);
  }
}

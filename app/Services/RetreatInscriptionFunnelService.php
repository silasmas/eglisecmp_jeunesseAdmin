<?php

namespace App\Services;

use App\Models\RetreatParticipant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Enregistre la progression du parcours d’inscription publique (formulaire → paiement → billet).
 */
class RetreatInscriptionFunnelService
{
  public const STAGE_FORM_IDENTITY = 'form_identity';

  public const STAGE_FORM_CONTACT = 'form_contact';

  public const STAGE_FORM_PARTICIPATION = 'form_participation';

  public const STAGE_RECAP = 'recap';

  public const STAGE_REGISTERED = 'registered_pending_payment';

  public const STAGE_PAYMENT_ENTERED = 'payment_entered';

  public const STAGE_PAYMENT_MOBILE_INITIATED = 'payment_mobile_initiated';

  public const STAGE_PAYMENT_MOBILE_POLLING = 'payment_mobile_polling';

  public const STAGE_PAYMENT_MOBILE_POLL_TIMEOUT = 'payment_mobile_poll_timeout';

  public const STAGE_PAYMENT_MOBILE_POLL_EXHAUSTED = 'payment_mobile_poll_exhausted';

  public const STAGE_PAYMENT_MOBILE_CANCELLED = 'payment_mobile_cancelled';

  public const STAGE_PAYMENT_MOBILE_CONFIRMED = 'payment_mobile_confirmed';

  public const STAGE_PAYMENT_CARD_INITIATED = 'payment_card_initiated';

  public const STAGE_PAYMENT_CARD_RETURN_UNPAID = 'payment_card_return_unpaid';

  public const STAGE_PAYMENT_CASH_PROOF = 'payment_cash_proof_submitted';

  public const STAGE_PAYMENT_VERIFY_FAILED = 'payment_server_verify_failed';

  public const STAGE_BADGE_REACHED = 'badge_reached';

  public const STAGE_COMPLETED = 'completed';

  /**
   * Libellés affichés dans l’admin.
   *
   * @return array<string, string>
   */
  public static function stageLabels(): array
  {
    return [
      self::STAGE_FORM_IDENTITY => 'Formulaire — identité',
      self::STAGE_FORM_CONTACT => 'Formulaire — coordonnées',
      self::STAGE_FORM_PARTICIPATION => 'Formulaire — participation',
      self::STAGE_RECAP => 'Récapitulatif',
      self::STAGE_REGISTERED => 'Inscrit — en attente de paiement',
      self::STAGE_PAYMENT_ENTERED => 'Étape paiement ouverte',
      self::STAGE_PAYMENT_MOBILE_INITIATED => 'Mobile Money — demande envoyée',
      self::STAGE_PAYMENT_MOBILE_POLLING => 'Mobile Money — attente opérateur',
      self::STAGE_PAYMENT_MOBILE_POLL_TIMEOUT => 'Mobile Money — délai de vérification dépassé',
      self::STAGE_PAYMENT_MOBILE_POLL_EXHAUSTED => 'Mobile Money — vérifications épuisées',
      self::STAGE_PAYMENT_MOBILE_CANCELLED => 'Mobile Money — annulé',
      self::STAGE_PAYMENT_MOBILE_CONFIRMED => 'Mobile Money — confirmé',
      self::STAGE_PAYMENT_CARD_INITIATED => 'Carte — redirection',
      self::STAGE_PAYMENT_CARD_RETURN_UNPAID => 'Carte — retour sans encaissement',
      self::STAGE_PAYMENT_CASH_PROOF => 'Espèces — preuve envoyée',
      self::STAGE_PAYMENT_VERIFY_FAILED => 'Paiement non confirmé côté serveur',
      self::STAGE_BADGE_REACHED => 'Billet affiché',
      self::STAGE_COMPLETED => 'Parcours terminé',
    ];
  }

  /**
   * @param string $stage Code d’étape (constantes ci-dessus)
   * @param string|null $detail Message optionnel pour l’admin
   * @param array<string, mixed> $meta Données techniques (référence, canal…)
   */
  public function record(RetreatParticipant $participant, string $stage, ?string $detail = null, array $meta = []): void
  {
    if (! Schema::hasColumn($participant->getTable(), 'inscription_funnel_stage')) {
      return;
    }

    $detailText = $detail;
    if ($meta !== []) {
      $suffix = json_encode($meta, JSON_UNESCAPED_UNICODE);
      $detailText = trim(($detailText ?? '').($detailText ? ' · ' : '').$suffix);
    }

    $participant->forceFill([
      'inscription_funnel_stage' => $stage,
      'inscription_funnel_detail' => $detailText ? Str::limit($detailText, 2000) : null,
      'inscription_funnel_at' => now(),
    ])->saveQuietly();

    if ($stage === self::STAGE_COMPLETED || $participant->paiement_valide) {
      $participant->forceFill([
        'registration_status' => 'completed',
      ])->saveQuietly();
    }
  }

  /**
   * Libellé lisible pour une étape.
   */
  public function labelFor(?string $stage): string
  {
    if ($stage === null || $stage === '') {
      return 'Non renseigné';
    }

    return self::stageLabels()[$stage] ?? $stage;
  }

  /**
   * Étapes considérées comme « bloquées en paiement » pour la surveillance.
   *
   * @return list<string>
   */
  public static function paymentProblemStages(): array
  {
    return [
      self::STAGE_REGISTERED,
      self::STAGE_PAYMENT_ENTERED,
      self::STAGE_PAYMENT_MOBILE_INITIATED,
      self::STAGE_PAYMENT_MOBILE_POLLING,
      self::STAGE_PAYMENT_MOBILE_POLL_TIMEOUT,
      self::STAGE_PAYMENT_MOBILE_POLL_EXHAUSTED,
      self::STAGE_PAYMENT_MOBILE_CANCELLED,
      self::STAGE_PAYMENT_CARD_INITIATED,
      self::STAGE_PAYMENT_CARD_RETURN_UNPAID,
      self::STAGE_PAYMENT_CASH_PROOF,
      self::STAGE_PAYMENT_VERIFY_FAILED,
    ];
  }
}

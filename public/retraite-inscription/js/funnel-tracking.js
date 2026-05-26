/* ═══════════════════════════════════════════
   SUIVI PARCOURS INSCRIPTION (API funnel)
═══════════════════════════════════════════ */
'use strict';

const RETRAITE_FUNNEL_STAGES = {
  form_identity: 'form_identity',
  form_contact: 'form_contact',
  form_participation: 'form_participation',
  recap: 'recap',
  payment_entered: 'payment_entered',
  payment_mobile_initiated: 'payment_mobile_initiated',
  payment_mobile_polling: 'payment_mobile_polling',
  payment_mobile_poll_timeout: 'payment_mobile_poll_timeout',
  payment_mobile_poll_exhausted: 'payment_mobile_poll_exhausted',
  payment_mobile_cancelled: 'payment_mobile_cancelled',
  payment_mobile_confirmed: 'payment_mobile_confirmed',
  payment_card_initiated: 'payment_card_initiated',
  payment_card_return_unpaid: 'payment_card_return_unpaid',
  payment_cash_proof_submitted: 'payment_cash_proof_submitted',
  payment_server_verify_failed: 'payment_server_verify_failed',
  badge_reached: 'badge_reached',
};

const RETRAITE_FUNNEL_STEP_MAP = [
  RETRAITE_FUNNEL_STAGES.form_identity,
  RETRAITE_FUNNEL_STAGES.form_contact,
  RETRAITE_FUNNEL_STAGES.form_participation,
  RETRAITE_FUNNEL_STAGES.recap,
  RETRAITE_FUNNEL_STAGES.payment_entered,
];

/**
 * Envoie l’étape du parcours au serveur (sans bloquer l’UI).
 *
 * @param {string} stage Code d’étape
 * @param {string|null} detail Message optionnel
 * @param {object} [extra] Référence paiement, canal…
 * @return {void}
 */
function trackRetraiteFunnel(stage, detail, extra) {
  if (!App.participantId || !stage) {
    return;
  }
  const base = typeof getRetraiteApiBase === 'function' ? getRetraiteApiBase() : '';
  if (!base) {
    return;
  }
  const body = {
    participant_id: App.participantId,
    stage: stage,
    detail: detail || null,
    payment_reference: (extra && extra.payment_reference) || App.paymentReference || null,
    channel: (extra && extra.channel) || null,
  };
  fetch(`${base}/funnel`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(body),
  }).catch(() => {
    /* non bloquant */
  });
}

/**
 * Enregistre l’étape formulaire selon l’index du stepper (0–4).
 *
 * @param {number} stepIndex Index 0-based
 * @return {void}
 */
function trackRetraiteFunnelForFormStep(stepIndex) {
  const stage = RETRAITE_FUNNEL_STEP_MAP[stepIndex];
  if (!stage) {
    return;
  }
  trackRetraiteFunnel(stage, null, null);
}

function persistRetraitePaymentPollState(active) {
  try {
    if (active && App.paymentReference) {
      sessionStorage.setItem('retraite_payment_ref', String(App.paymentReference));
      sessionStorage.setItem('retraite_payment_poll', '1');
    } else {
      sessionStorage.removeItem('retraite_payment_ref');
      sessionStorage.removeItem('retraite_payment_poll');
    }
  } catch (e) {
    /* ignore */
  }
}

window.trackRetraiteFunnel = trackRetraiteFunnel;
window.trackRetraiteFunnelForFormStep = trackRetraiteFunnelForFormStep;
window.RETRAITE_FUNNEL_STAGES = RETRAITE_FUNNEL_STAGES;
window.persistRetraitePaymentPollState = persistRetraitePaymentPollState;

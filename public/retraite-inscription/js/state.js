/* ═══════════════════════════════════════════
   SHARED STATE & HELPERS
═══════════════════════════════════════════ */
'use strict';

window.App = {
  currentStep: 0,
  totalSteps: 6,
  stepLabels: [
    'Identité du participant',
    'Vos coordonnées',
    'Informations de participation',
    'Récapitulatif',
    'Paiement',
    'Votre billet'
  ],
  photoDataURL: null,
  proofFile: null,
  proofDataURL: null,
  participantId: null,
  paymentReference: null,
  paymentModeCompleted: null,
  paymentPollActive: false,
  activeEvent: null,
  selectedFlexpayType: null,
  badgeView: null,
  acceptedPolicyIds: [],
  policyListRendered: [],
  policiesGateRequired: false,
  policiesModalAccepted: false,
  registrationOpen: false,
  mainPhoneDuplicateRegistered: false,
  emailDuplicateRegistered: false,
  retreatVerificationUrl: null,
  retreatDownloadToken: null,
  parentOtpVerificationId: null,
  parentVerifiedToken: null,
  parentContactVerified: false,
  formFields: {},
};

/* ─── HELPER: Get field value ─── */
function val(id) {
  const el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

/* ─── HELPER: Get selected text for select ─── */
function selectText(id) {
  const el = document.getElementById(id);
  if (!el || el.selectedIndex < 0) return '';
  return el.options[el.selectedIndex].text;
}

/* ─── HELPER: Get hébergement radio value ─── */
function getHebergementValue() {
  const select = document.getElementById('hebergement');
  if (select) return select.value;
  const checked = document.querySelector('input[name="hebergement"]:checked');
  return checked ? checked.value : '';
}

/* ═══════════════════════════════════════════
   URL & LOADER HELPERS
═══════════════════════════════════════════ */

/**
 * Supprime les paramètres d’URL liés au retour paiement / reprise d’inscription.
 */
function resetRetraiteUrlParams() {
  try {
    const url = new URL(window.location.href);
    const paramsToRemove = ['resume_payment_ref', 'ref', 'status', 'payment_ref'];
    let changed = false;
    paramsToRemove.forEach((param) => {
      if (url.searchParams.has(param)) {
        url.searchParams.delete(param);
        changed = true;
      }
    });
    if (changed) {
      const next = url.pathname + (url.search ? url.search : '') + url.hash;
      window.history.replaceState({}, '', next);
    }
  } catch (e) {
    /* ignore */
  }
}

window.resetRetraiteUrlParams = resetRetraiteUrlParams;

/**
 * Affiche le loader de génération du billet (dernière étape).
 *
 * @param {string} [message]
 */
function showBilletCreationLoader(message) {
  const loader = document.getElementById('billetCreationLoader');
  const text = document.getElementById('billetCreationLoaderText');
  if (text && message) text.textContent = message;
  if (loader) {
    loader.classList.remove('hidden');
    loader.setAttribute('aria-busy', 'true');
  }
}

/**
 * Masque le loader de génération du billet.
 */
function hideBilletCreationLoader() {
  const loader = document.getElementById('billetCreationLoader');
  if (loader) {
    loader.classList.add('hidden');
    loader.setAttribute('aria-busy', 'false');
  }
}

window.showBilletCreationLoader = showBilletCreationLoader;
window.hideBilletCreationLoader = hideBilletCreationLoader;

function getParentOtpChannelFromEvent() {
  const notifications = App.activeEvent && App.activeEvent.participant_notifications;
  if (
    notifications &&
    notifications.access_auth_mode === 'otp' &&
    notifications.access_otp_channel === 'sms'
  ) {
    return 'sms';
  }
  return 'email';
}

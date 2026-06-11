/* ═══════════════════════════════════════════
   API INSCRIPTION RETRAITE + FLEX
═══════════════════════════════════════════ */
'use strict';

function getRetraiteApiBase() {
  const m = document.querySelector('meta[name="retraite-api-base"]');
  return (m && m.content) ? m.content.replace(/\/$/, '') : '';
}

function formatRetraiteMoney(amount, currency) {
  const n = Number(amount);
  const c = (currency || 'USD').toString().toUpperCase();
  try {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: c }).format(n);
  } catch (e) {
    return `${n} ${c}`.trim();
  }
}

function dataURLtoBlobFile(dataUrl, filename) {
  const arr = dataUrl.split(',');
  const mime = (arr[0].match(/:(.*?);/) || [])[1] || 'image/jpeg';
  const bstr = atob(arr[1]);
  let n = bstr.length;
  const u8 = new Uint8Array(n);
  while (n--) u8[n] = bstr.charCodeAt(n);
  return new File([u8], filename, { type: mime });
}

async function fetchRetraiteEvent() {
  const base = getRetraiteApiBase();
  if (!base) return null;
  const res = await fetch(`${base}/event`, { headers: { Accept: 'application/json' } });
  if (!res.ok) return null;
  const json = await res.json();
  return json.data || null;
}

function applyStepContext(ev) {
  const ctx = ev.step_context || {};
  const map = [
    ['step-0', 'identity'],
    ['step-1', 'contact'],
    ['step-2', 'participation'],
    ['step-3', 'recap'],
    ['step-4', 'payment']
  ];
  map.forEach(([sid, key]) => {
    const sec = document.getElementById(sid);
    if (!sec || !ctx[key]) return;
    const desc = sec.querySelector('.step-description');
    if (desc) desc.textContent = ctx[key];
  });

  const paySub = document.getElementById('paymentStepSubtitle');
  if (paySub && ctx.payment) paySub.textContent = ctx.payment;
}

function applyHeroFromEvent(ev) {
  App.activeEvent = ev;
  const hero = document.getElementById('retraiteHero');
  if (!hero) return;

  hero.classList.toggle('hero--has-poster', !!(ev && ev.affiche_url));

  if (ev && ev.affiche_url) {
    const urlPart = JSON.stringify(ev.affiche_url);
    hero.style.backgroundImage = `linear-gradient(120deg, rgba(26,16,24,0.82), rgba(26,16,24,0.55)), url(${urlPart})`;
    hero.style.backgroundSize = 'cover';
    hero.style.backgroundPosition = 'center';
    hero.style.backgroundRepeat = 'no-repeat';
  } else {
    hero.style.backgroundImage = '';
    hero.style.backgroundSize = '';
    hero.style.backgroundPosition = '';
    hero.style.backgroundRepeat = '';
  }

  const subStrong = hero.querySelector('.hero-sub strong');
  if (subStrong && ev && ev.name) {
    subStrong.textContent = ev.name;
  }

  const themeEl = document.getElementById('heroThemeLine');
  if (themeEl) {
    const rd = ev && ev.retreat_detail;
    const theme = rd && rd.theme ? String(rd.theme) : '';
    const speaker = rd && rd.speaker ? String(rd.speaker) : '';
    if (theme || speaker) {
      themeEl.classList.remove('hidden');
      themeEl.textContent =
        speaker && theme ? `${theme} · ${speaker}` : theme || `${speaker}`;
    } else {
      themeEl.classList.add('hidden');
      themeEl.textContent = '';
    }
  }

  const places = document.getElementById('heroPlacesLine');
  if (places) {
    if (ev && ev.places_message) {
      places.classList.remove('hidden');
      places.textContent = ev.places_message;
    } else {
      places.classList.add('hidden');
      places.textContent = '';
    }
  }

  const soldBar = document.getElementById('heroSoldOutBar');
  if (soldBar) {
    if (ev && ev.is_sold_out) {
      soldBar.classList.remove('hidden');
    } else {
      soldBar.classList.add('hidden');
    }
  }

  const authHint = document.getElementById('heroAuthPortalLine');
  if (authHint) {
    authHint.classList.add('hidden');
    authHint.innerHTML = '';
  }

  const bel = document.getElementById('badgeExportEventTitle');
  const belS = document.getElementById('badgeExportMeta');
  if (ev && bel) bel.textContent = ev.name || bel.textContent;
  const locHint = ev && ev.location ? ev.location : '';
  if (ev && belS && locHint) belS.textContent = locHint;

  applyStepContext(ev);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function buildPoliciesMarkup(list) {
  return list
    .map(
      policy => `
      <article class="policy-card" data-id="${policy.id}">
        <h4>${escapeHtml(policy.title || 'Politique')}${
          policy.is_mandatory
            ? ' <span class="badge-required">Obligatoire</span>'
            : ''
        }</h4>
        <div class="policy-body">${policy.content ? String(policy.content) : ''}</div>
      </article>`
    )
    .join('');
}

async function loadPoliciesForRecap() {
  const block = document.getElementById('policiesBlock');
  const scrollEl = document.getElementById('policiesModalScroll');
  const btnOpen = document.getElementById('btnOpenPoliciesModal');
  const badge = document.getElementById('policiesAcceptedBadge');

  App.policyListRendered = [];
  App.acceptedPolicyIds = [];
  App.policiesModalAccepted = false;
  App.policiesGateRequired = false;

  if (badge) badge.classList.add('hidden');
  if (btnOpen) btnOpen.disabled = false;

  if (!block || !scrollEl) return;

  scrollEl.innerHTML = '<p class="field-hint">Chargement des politiques…</p>';

  const base = getRetraiteApiBase();
  if (!base) {
    block.classList.add('hidden');
    scrollEl.innerHTML = '';
    if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();
    return;
  }

  let res;
  try {
    res = await fetch(`${base}/policies`, { headers: { Accept: 'application/json' } });
  } catch (e) {
    res = null;
  }

  if (!res || !res.ok) {
    block.classList.add('hidden');
    scrollEl.innerHTML = '';
    if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();
    return;
  }

  const json = await res.json().catch(() => ({}));
  const list = (json.data && json.data.policies) || [];

  App.policyListRendered = list;

  if (!list.length) {
    block.classList.add('hidden');
    scrollEl.innerHTML = '';
    if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();
    return;
  }

  scrollEl.innerHTML = buildPoliciesMarkup(list);
  App.policiesGateRequired = true;
  block.classList.remove('hidden');
  if (btnOpen) btnOpen.disabled = false;

  if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();

  /* Ouverture proactive du règlement : évite que l’étape passe inaperçue */
  if (App.policiesGateRequired && !App.policiesModalAccepted) {
    setTimeout(() => {
      if (typeof window.openMandatoryPoliciesModal === 'function') {
        window.openMandatoryPoliciesModal();
      }
    }, 200);
  }
}

window.loadPoliciesForRecap = loadPoliciesForRecap;

function wireMandatoryPoliciesModal() {
  if (window.__mandatoryPoliciesModalWired) return;
  window.__mandatoryPoliciesModalWired = true;

  const modal = document.getElementById('mandatoryPoliciesModal');
  const scrollEl = document.getElementById('policiesModalScroll');
  const chk = document.getElementById('policiesModalAcceptCheck');
  const btnConfirm = document.getElementById('btnPoliciesModalConfirm');
  const btnOpen = document.getElementById('btnOpenPoliciesModal');
  const backdrop = document.getElementById('mandatoryPoliciesModalBackdrop');
  const btnClose = document.getElementById('mandatoryPoliciesModalClose');
  const btnDismiss = document.getElementById('btnPoliciesModalDismiss');

  if (!modal || !scrollEl || !chk || !btnConfirm) return;

  function closeMandatoryPoliciesModal() {
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('retraite-modal-open');
    if (scrollEl._polScrollUnlock) {
      scrollEl.removeEventListener('scroll', scrollEl._polScrollUnlock);
      scrollEl._polScrollUnlock = null;
    }
  }

  function openMandatoryPoliciesModal() {
    if (!App.policyListRendered || App.policyListRendered.length === 0) return;

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('retraite-modal-open');

    chk.checked = false;
    chk.disabled = true;
    btnConfirm.disabled = true;
    scrollEl.scrollTop = 0;

    if (scrollEl._polScrollUnlock) {
      scrollEl.removeEventListener('scroll', scrollEl._polScrollUnlock);
    }

    const reachedBottom = () =>
      scrollEl.scrollTop + scrollEl.clientHeight >= scrollEl.scrollHeight - 28;

    const onScrollUnlock = () => {
      if (reachedBottom()) {
        chk.disabled = false;
      }
    };

    scrollEl._polScrollUnlock = onScrollUnlock;
    scrollEl.addEventListener('scroll', onScrollUnlock);
    requestAnimationFrame(() => {
      if (reachedBottom()) {
        chk.disabled = false;
      }
      onScrollUnlock();
    });
  }

  [backdrop, btnClose, btnDismiss].forEach(el => {
    if (el) el.addEventListener('click', closeMandatoryPoliciesModal);
  });

  chk.addEventListener('change', () => {
    btnConfirm.disabled = !chk.checked;
  });

  btnConfirm.addEventListener('click', () => {
    if (!chk.checked) return;
    App.acceptedPolicyIds = App.policyListRendered.map(p => p.id);
    App.policiesModalAccepted = true;
    const badgeEl = document.getElementById('policiesAcceptedBadge');
    if (badgeEl) badgeEl.classList.remove('hidden');
    if (btnOpen) btnOpen.disabled = true;
    closeMandatoryPoliciesModal();
    if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();
  });

  if (btnOpen) {
    btnOpen.addEventListener('click', () => openMandatoryPoliciesModal());
  }

  window.openMandatoryPoliciesModal = openMandatoryPoliciesModal;
  window.closeMandatoryPoliciesModal = closeMandatoryPoliciesModal;
}

window.wireMandatoryPoliciesModal = wireMandatoryPoliciesModal;

function normalizeFlexpayCdMsisdn(raw) {
  let digits = String(raw || '').replace(/\D/g, '');
  if (digits.startsWith('0')) {
    digits = `243${digits.slice(1)}`;
  }
  if (!digits.startsWith('243')) {
    digits = `243${digits.replace(/^0+/, '')}`;
  }
  return digits;
}

function flexpayMsisdnMatchesSelectedType(normalized, flexpayType) {
  const list = (App.activeEvent && App.activeEvent.flexpay_mobile_providers) || [];
  const p = list.find(x => String(x.type) === String(flexpayType));
  if (!p || !p.msisdn_regex) {
    return /^243\d{9}$/.test(normalized);
  }
  try {
    return new RegExp(p.msisdn_regex).test(normalized);
  } catch (e) {
    return /^243\d{9}$/.test(normalized);
  }
}

function syncFlexpayMsisdnFormatHint() {
  const hint = document.getElementById('flexpayPhoneFormatHint');
  if (!hint) return;
  const p = (App.activeEvent && App.activeEvent.flexpay_mobile_providers || []).find(
    x => String(x.type) === String(App.selectedFlexpayType)
  );
  if (!p) {
    hint.innerHTML =
      'Sans le signe « + » : 12 chiffres au total, en commençant par <strong>243</strong> (format international RDC sans préfixe plus).';
    return;
  }
  hint.innerHTML = `Réseau <strong>${escapeHtml(p.label || p.code || '')}</strong> — saisissez 12 chiffres commençant par <strong>243</strong>, cohérents avec cette ligne.`;
}

function setPaymentProgressStep(activeStep, detailText) {
  const panel = document.getElementById('paymentProgressPanel');
  if (!panel) return;
  panel.classList.remove('hidden');
  panel.querySelectorAll('.payment-progress-step').forEach(el => {
    const n = Number(el.dataset.step);
    el.classList.remove('active', 'done');
    if (n < activeStep) el.classList.add('done');
    if (n === activeStep) el.classList.add('active');
  });
  const d = document.getElementById('paymentProgressDetail');
  if (d) d.textContent = detailText || '';
}

function hidePaymentPollRelaunchUi() {
  const wrap = document.getElementById('paymentPollRelaunchWrap');
  const relBtn = document.getElementById('btnRelaunchPaymentPoll');
  if (wrap) wrap.classList.add('hidden');
  if (relBtn) relBtn.disabled = false;
}

function hidePaymentProgressPanel() {
  const panel = document.getElementById('paymentProgressPanel');
  if (panel) {
    panel.classList.add('hidden');
  }
  const d = document.getElementById('paymentProgressDetail');
  if (d) d.textContent = '';
  hidePaymentPollRelaunchUi();
}

function mountFlexpayProviders(providers) {
  const mount = document.getElementById('flexpayProvidersMount');
  if (!mount) return;
  mount.innerHTML = '';
  App.selectedFlexpayType = providers && providers[0] ? String(providers[0].type) : null;

  (providers || []).forEach((p, i) => {
    const card = document.createElement('button');
    card.type = 'button';
    card.className = 'payment-card' + (i === 0 ? ' flexpay-prov-active' : '');
    card.dataset.flexpayType = String(p.type);
    const code = (p.code || '').toLowerCase();
    const iconKind = code.includes('airtel') || code.includes('afri')
      ? 'airtel'
      : code.includes('orange')
        ? 'bank'
        : 'mpesa';
    card.innerHTML = `
      <div class="payment-card-icon ${iconKind}"><i class="bi bi-phone"></i></div>
      <div class="payment-card-name">${escapeHtml(p.label || p.code || '')}</div>
      <div class="payment-card-number">Paiement mobile · réseau ${escapeHtml(p.label || 'sélectionné')}</div>
    `;
    card.addEventListener('click', () => {
      mount.querySelectorAll('.payment-card').forEach(c => c.classList.remove('flexpay-prov-active'));
      card.classList.add('flexpay-prov-active');
      App.selectedFlexpayType = card.dataset.flexpayType;
      syncFlexpayMsisdnFormatHint();
    });
    mount.appendChild(card);
  });
  syncFlexpayMsisdnFormatHint();
}

function togglePaymentSections(mode) {
  const mm = document.getElementById('mobileMoneyBlock');
  const card = document.getElementById('cardBlock');
  const cash = document.getElementById('cashBlock');
  [mm, card, cash].forEach(el => el && el.classList.add('hidden'));
  if (mode === 'mobile_money' && mm) mm.classList.remove('hidden');
  if (mode === 'card' && card) card.classList.remove('hidden');
  if (mode === 'cash' && cash) cash.classList.remove('hidden');

  if (mode === 'mobile_money') {
    syncFlexpayMsisdnFormatHint();
  }

  const ext = App.activeEvent && App.activeEvent.card_payment && App.activeEvent.card_payment.external_form_url;
  const txt = document.getElementById('cardExplainerText');
  if (txt) {
    txt.textContent = ext
      ? 'Vous allez être redirigé vers le formulaire carte défini pour cet événement.'
      : 'Vous allez être redirigé vers le portail sécurisé de l’intermédiaire de paiement pour saisir votre carte.';
  }
}

/**
 * Présélectionne le mode de paiement s'il n'en reste qu'un de visible.
 *
 * @return {void}
 */
function autoSelectSinglePaymentMode() {
  const ui = App.uiSettings || {};
  const paymentModes = ui.payment_modes || {};
  const order = ui.payment_modes_order || ['mobile_money', 'card', 'cash'];
  const visibleModes = order.filter((mode) => paymentModes[mode]?.is_visible !== false);

  if (visibleModes.length !== 1) {
    return;
  }

  const radio = document.querySelector(`input[name="paymentMode"][value="${visibleModes[0]}"]`);
  if (!radio || radio.checked) {
    return;
  }

  radio.checked = true;
  if (typeof togglePaymentSections === 'function') {
    togglePaymentSections(visibleModes[0]);
  }
}

function onEnterPaymentStep() {
  const ev = App.activeEvent;
  const labelEl = document.getElementById('paymentAmountLabel');
  if (!ev || !labelEl) {
    if (labelEl) labelEl.textContent = 'Événement non chargé.';
    return;
  }
  labelEl.textContent = `${formatRetraiteMoney(ev.price_to_pay, ev.currency)} · frais d’inscription`;
  mountFlexpayProviders(ev.flexpay_mobile_providers || []);

  const preservePollUi = App.paymentPollActive === true && !!App.paymentReference;
  if (!preservePollUi) {
    hidePaymentProgressPanel();
    const phoneIn = document.getElementById('flexpayPhoneInput');
    if (phoneIn && !phoneIn.value.trim()) {
      phoneIn.value = '';
    }
    document.querySelectorAll('input[name="paymentMode"]').forEach(r => {
      r.checked = false;
    });
    togglePaymentSections(null);
    autoSelectSinglePaymentMode();
  }
}

function wirePaymentModes() {
  document.querySelectorAll('input[name="paymentMode"]').forEach(radio => {
    radio.addEventListener('change', () => {
      if (radio.checked) togglePaymentSections(radio.value);
    });
  });

  const btnM = document.getElementById('btnTriggerMobilePay');
  if (btnM) {
    btnM.addEventListener('click', () => triggerMobilePayment());
  }
  const btnC = document.getElementById('btnTriggerCardPay');
  if (btnC) {
    btnC.addEventListener('click', () => triggerCardPayment());
  }
  const btnCash = document.getElementById('btnSubmitCashProof');
  if (btnCash) {
    btnCash.addEventListener('click', () => submitCashFlow());
  }
  wireRelaunchPaymentPoll();
}

async function registerParticipantOnServer() {
  const base = getRetraiteApiBase();
  const fd = new FormData();
  fd.append('nom', val('nom'));
  fd.append('prenom', val('prenom'));
  fd.append('sexe', val('sexe'));
  fd.append('date_naissance', val('dateNaissance'));
  fd.append('role', val('role') || 'Participant');
  fd.append('indicatif', val('indicatif'));
  fd.append('telephone', val('telephone'));
  fd.append('tel_urgence', val('telUrgence'));
  fd.append('guardian_name', val('guardianName'));
  fd.append('guardian_phone', val('guardianPhone'));
  const familyMultiChildCheck = document.getElementById('familyMultiChildCheck');
  const parentGroupMode = !!(familyMultiChildCheck && familyMultiChildCheck.checked);
  fd.append('parent_group_mode', parentGroupMode ? '1' : '0');
  if (parentGroupMode) {
    fd.append('parent_contact_email', val('parentContactEmail'));
    fd.append('parent_contact_phone', val('parentContactPhone'));
    fd.append('parent_full_name', val('parentFullName'));
    if (App.parentVerifiedToken) fd.append('parent_verified_token', App.parentVerifiedToken);
  }
  const tutorFam = document.getElementById('tutorSameFamilyCheck');
  fd.append('same_family_emergency_confirm', tutorFam && tutorFam.checked ? '1' : '0');
  fd.append('email', val('email'));
  fd.append('adresse', val('adresse'));
  fd.append('commune', val('commune'));
  fd.append('ville', val('ville'));
  fd.append('eglise', val('eglise'));
  fd.append('departement', val('departement'));
  const noDept = document.getElementById('noDepartement');
  if (noDept && noDept.checked) fd.append('no_departement', '1');
  const h = getHebergementValue();
  if (h) fd.append('hebergement', h);
  const observationsChoice = document.querySelector('input[name="hasObservations"]:checked')?.value;
  if (observationsChoice === 'yes') {
    fd.append('observations', val('observations'));
  }
  if (App.activeEvent && App.activeEvent.id) fd.append('event_id', String(App.activeEvent.id));

  if (App.photoDataURL) {
    const file = dataURLtoBlobFile(App.photoDataURL, `photo_${Date.now()}.jpg`);
    fd.append('photo', file);
  }

  (App.acceptedPolicyIds || []).forEach(id => {
    fd.append('accepted_policy_ids[]', String(id));
  });

  const res = await fetch(`${base}/register`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: fd,
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    const err = new Error(json.message || 'Inscription refusée par le serveur.');
    err.httpStatus = res.status;
    err.responseBody = json;
    throw err;
  }
  return json;
}

async function confirmRecapAndProceed() {
  const confirmCheck = document.getElementById('confirmCheck');
  if (!confirmCheck || !confirmCheck.checked) {
    retraiteNotifyToast('Cochez la confirmation : les informations ci-dessus sont exactes.', 'warning');
    return;
  }

  if (App.policiesGateRequired && !App.policiesModalAccepted) {
    retraiteNotifyError({
      title: 'Règlement obligatoire',
      text:
        'Lisez le règlement dans la fenêtre qui s’affiche, faites défiler jusqu’en bas puis validez votre acceptation.',
      persistent: true,
    });
    if (typeof window.openMandatoryPoliciesModal === 'function') {
      window.openMandatoryPoliciesModal();
    }
    return;
  }

  if (
    App.activeEvent &&
    App.activeEvent.is_sold_out &&
    typeof isOuvrierRegistrationRole === 'function' &&
    !isOuvrierRegistrationRole()
  ) {
    retraiteNotifyToast('Les inscriptions en ligne sont closes : nombre de places maximal atteint.', 'warning');
    return;
  }

  const btn = document.getElementById('submitBtn');
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Enregistrement…';
  }
  try {
    const json = await registerParticipantOnServer();
    App.participantId = json.data && json.data.participant_id;
    if (json.data && json.data.verification_url) {
      App.retreatVerificationUrl = json.data.verification_url;
      App.retreatDownloadToken = json.data.download_token || App.retreatDownloadToken;
    }
    if (App.participantId) {
      sessionStorage.setItem('retraite_participant_id', String(App.participantId));
    }
    if (json.data && json.data.event) {
      App.activeEvent = json.data.event;
    }
    goToStep(4);
    retraiteNotifyToast('Inscription enregistrée. Vous pouvez maintenant régler vos frais d’inscription.', 'success');
  } catch (e) {
    console.error(e);
    const body = e.responseBody || {};
    let text = e.message || 'Erreur réseau ou serveur.';
    if (body.errors && typeof body.errors === 'object') {
      const flat = Object.values(body.errors).flat();
      if (flat.length && typeof flat[0] === 'string') {
        text = flat[0];
      }
    }
    const debug = body.debug ? String(body.debug) : '';
    retraiteNotifyError({
      title: 'Inscription',
      text,
      footer: debug || undefined,
      persistent: true,
    });
  } finally {
    if (btn) {
      btn.innerHTML = '<i class="bi bi-arrow-right-circle"></i> Continuer vers le paiement';
      if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();
      else btn.disabled = !confirmCheck.checked;
    }
  }
}

function formatFlexPayInitError(message, flexpayType) {
  const raw = String(message || '').toLowerCase();
  if (raw.includes('type') && (raw.includes('ne correspond') || raw.includes('correspond pas'))) {
    const providers = (App.activeEvent && App.activeEvent.flexpay_mobile_providers) || [];
    const selected = providers.find((p) => String(p.type) === String(flexpayType));
    const label = selected ? selected.label : 'opérateur sélectionné';
    return `FlexPay a refusé le code opérateur « ${flexpayType} » (${label}). Ce code ne correspond pas à votre contrat marchand : vérifiez la configuration RETRAITE_FLEXPAY_MOBILE_PROVIDERS côté administration ou contactez FlexPay pour obtenir le bon type pour Orange / Airtel / M-Pesa.`;
  }
  return message || 'Impossible de lancer le paiement. Vérifiez le numéro et le réseau choisi.';
}

async function triggerMobilePayment() {
  if (!App.participantId) {
    retraiteNotifyToast('Participant introuvable. Revenez au récapitulatif.', 'warning');
    return;
  }
  const rawPhone =
    document.getElementById('flexpayPhoneInput') && document.getElementById('flexpayPhoneInput').value.trim();
  if (!rawPhone) {
    retraiteNotifyToast('Indiquez le numéro Mobile Money.', 'warning');
    return;
  }
  if (!App.selectedFlexpayType) {
    retraiteNotifyToast('Choisissez un opérateur.', 'warning');
    return;
  }

  const normalized = normalizeFlexpayCdMsisdn(rawPhone);
  if (normalized.length !== 12 || !normalized.startsWith('243')) {
    retraiteNotifyToast(
      'Le numéro doit comporter 12 chiffres et commencer par 243 (sans « + »). Exemple : 243891234567.',
      'warning'
    );
    return;
  }
  if (!flexpayMsisdnMatchesSelectedType(normalized, App.selectedFlexpayType)) {
    retraiteNotifyToast(
      'Ce numéro ne semble pas correspondre au réseau choisi. Vérifiez l’opérateur ou le numéro saisi.',
      'warning'
    );
    return;
  }

  const btn = document.getElementById('btnTriggerMobilePay');
  const originalBtnHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Préparation du paiement…';
  }

  stopMobilePaymentPollTimer();
  hidePaymentPollRelaunchUi();
  mobilePayManualSessionsLeft = null;

  setPaymentProgressStep(1, 'Nous préparons la demande auprès de votre opérateur mobile…');
  showPaymentBanner('Transmission de la demande de paiement à votre opérateur…', 'info');

  const base = getRetraiteApiBase();
  let json = {};
  let res;
  try {
    res = await fetch(`${base}/participants/${App.participantId}/payments/mobile`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ phone: normalized, flexpay_type: App.selectedFlexpayType }),
    });
    json = await res.json().catch(() => ({}));
  } catch (err) {
    json = {};
    res = { ok: false };
  }

  if (!res.ok) {
    hidePaymentProgressPanel();
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalBtnHtml;
    }
    retraiteNotifyError({
      title: 'Paiement Mobile Money',
      text: formatFlexPayInitError(json.message, App.selectedFlexpayType),
      persistent: true,
    });
    return;
  }

  App.paymentReference = (json.data && json.data.reference) || App.paymentReference;
  App.paymentPollActive = true;
  if (typeof persistRetraitePaymentPollState === 'function') {
    persistRetraitePaymentPollState(true);
  }
  setPaymentProgressStep(
    2,
    'Une alerte a été envoyée sur votre téléphone — validez avec votre code secret ou selon les instructions de votre opérateur.'
  );
  showPaymentBanner(
    json.message ||
      'Demande transmise à votre opérateur. Confirmez le paiement sur votre téléphone puis patientez ici.',
    'info'
  );
  App.mobilePayPollOrigBtnHtml = originalBtnHtml;
  pollPaymentStatus(App.paymentReference, 'mobile_money', originalBtnHtml);
}

function retraitePaymentPollIndicatesSuccess(d) {
  if (!d || typeof d !== 'object') return false;
  const code = d.statut_code;
  if (code === 0 || code === '0') return true;
  if (d.payee === true || d.payment_etat === 'payee') return true;
  if (d.paiement_valide === true && (d.payee === true || d.payment_etat === 'payee')) return true;
  return false;
}

async function triggerCardPayment() {
  if (!App.participantId) return;
  const btn = document.getElementById('btnTriggerCardPay');
  const originalBtnHtml = btn ? btn.innerHTML : '';
  let leavingPage = false;
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Préparation du paiement…';
  }
  const base = getRetraiteApiBase();
  let json = {};
  let res;
  try {
    res = await fetch(`${base}/participants/${App.participantId}/payments/card`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    json = await res.json().catch(() => ({}));
  } catch (e) {
    json = {};
    res = { ok: false };
  }
  if (!res.ok || !json.data) {
    retraiteNotifyError({
      title: 'Paiement carte',
      text: json.message || 'Impossible de préparer le paiement par carte.',
      persistent: true,
    });
    if (btn && !leavingPage) {
      btn.disabled = false;
      btn.innerHTML = originalBtnHtml;
    }
    return;
  }
  if (json.data.mode === 'external_form' && json.data.redirect_url) {
    leavingPage = true;
    window.location.href = json.data.redirect_url;
    return;
  }
  if (json.data.redirect_url) {
    leavingPage = true;
    App.paymentReference = json.data.reference;
    window.location.href = json.data.redirect_url;
    return;
  }
  retraiteNotifyToast('Aucune URL de redirection reçue du serveur de paiement.', 'warning');
  if (btn && !leavingPage) {
    btn.disabled = false;
    btn.innerHTML = originalBtnHtml;
  }
}

async function submitCashFlow() {
  if (!App.proofFile || !App.participantId) {
    retraiteNotifyToast('Joignez une preuve de paiement.', 'warning');
    return;
  }
  const btn = document.getElementById('btnSubmitCashProof');
  const originalBtnHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Envoi de la preuve…';
  }
  const base = getRetraiteApiBase();
  const fd = new FormData();
  fd.append('proof', App.proofFile);
  let res;
  let json = {};
  try {
    res = await fetch(`${base}/participants/${App.participantId}/payments/cash`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: fd,
    });
    json = await res.json().catch(() => ({}));
  } catch (e) {
    json = {};
    res = { ok: false };
  }
  if (!res.ok) {
    retraiteNotifyError({
      title: 'Preuve de paiement',
      text: json.message || 'Erreur lors de l’envoi de la preuve.',
      persistent: true,
    });
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalBtnHtml;
    }
    return;
  }
  App.paymentModeCompleted = 'cash';
  if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES) {
    trackRetraiteFunnel(
      RETRAITE_FUNNEL_STAGES.payment_cash_proof_submitted,
      'Preuve espèces envoyée.',
      { channel: 'cash' }
    );
  }
  retraiteNotifyToast(
    'Preuve reçue. Vous recevrez un e-mail après validation par l’équipe.',
    'success'
  );
  if (typeof finalizeBadgeUi === 'function') finalizeBadgeUi('cash_pending');
  if (btn) {
    btn.disabled = false;
    btn.innerHTML = originalBtnHtml;
  }
}

function showPaymentBanner(html, variant) {
  const el = document.getElementById('paymentStatusBanner');
  if (!el) return;
  el.innerHTML = `<i class="bi bi-info-circle"></i> <span>${html}</span>`;
  el.classList.remove('hidden', 'warning', 'info', 'success');
  el.classList.add(variant === 'success' ? 'success' : variant === 'warning' ? 'warning' : 'info');
}

const MOBILE_PAY_POLL_MS = 2000;
const MOBILE_PAY_POLL_INITIAL_MAX_TICKS = 10; // 20s max
const MOBILE_PAY_POLL_RELAUNCH_MAX_TICKS = 5; // 10s max
const MOBILE_PAY_MANUAL_SESSIONS_MAX = 3;

let pollTimer = null;
/** Restant après premier timeout auto ; décrémenté à chaque timeout d’une série lancée via « Relancer ». */
let mobilePayManualSessionsLeft = null;
let mobilePayPollInFlight = false;
let mobilePayPollErrorStreak = 0;

function stopMobilePaymentPollTimer() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
  mobilePayPollInFlight = false;
}

function endMobilePaymentPollSession() {
  App.paymentPollActive = false;
  if (typeof persistRetraitePaymentPollState === 'function') {
    persistRetraitePaymentPollState(false);
  }
}

function showPaymentPollRelaunchUi(sessionsLeft) {
  const wrap = document.getElementById('paymentPollRelaunchWrap');
  const hint = document.getElementById('paymentPollRelaunchHint');
  const relBtn = document.getElementById('btnRelaunchPaymentPoll');
  if (!wrap || !relBtn || sessionsLeft < 1) return;
  wrap.classList.remove('hidden');
  const n = sessionsLeft;
  if (hint) {
    hint.innerHTML = `L’opérateur n’a pas renvoyé de confirmation dans le délai. Utilisez le bouton pour <strong>interroger à nouveau le statut</strong> (encore <strong>${n}</strong> relance${n > 1 ? 's' : ''} possible${n > 1 ? 's' : ''}).`;
  }
  relBtn.disabled = false;
  relBtn.innerHTML = `<i class="bi bi-arrow-clockwise"></i> Relancer la vérification du statut (${n} essai${n > 1 ? 's' : ''} restant${n > 1 ? 's' : ''})`;
}

function showMobilePaymentPollFullyExhausted() {
  hidePaymentPollRelaunchUi();
  endMobilePaymentPollSession();
  if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES) {
    trackRetraiteFunnel(
      RETRAITE_FUNNEL_STAGES.payment_mobile_poll_exhausted,
      'Toutes les relances de vérification sont épuisées.',
      { channel: 'mobile_money' }
    );
  }
  const hint = document.getElementById('mobilePayHint');
  setPaymentProgressStep(2, 'Plusieurs vérifications sans confirmation automatique.');
  showPaymentBanner(
    '<strong>Nous n’avons toujours pas de confirmation</strong>, même après 3 relances. <strong>Vérifiez votre compte Mobile Money</strong> : un débit correspondant au montant d’inscription est-il apparu ? Si oui, conservez une preuve (capture) et contactez l’organisation. Si non, vous pouvez <strong>réessayer</strong> avec «&nbsp;Déclencher le paiement&nbsp;» ou choisir un <strong>autre mode</strong> sur cette page (carte bancaire ou espèces avec preuve).',
    'warning'
  );
  if (hint) {
    hint.innerHTML =
      'En cas de débit sans badge : notez l’heure et le montant. Sinon, changez de moyen de paiement ou relancez une nouvelle demande Mobile Money.';
  }
  mobilePayManualSessionsLeft = null;
}

function startMobilePaymentStatusPolling(reference, mode, originalBtnHtml, pollOptions) {
  const opts = pollOptions || {};
  const decrementCreditOnTimeout = !!opts.decrementManualCreditOnTimeout;
  const maxTicks = decrementCreditOnTimeout
    ? MOBILE_PAY_POLL_RELAUNCH_MAX_TICKS
    : MOBILE_PAY_POLL_INITIAL_MAX_TICKS;

  stopMobilePaymentPollTimer();
  hidePaymentPollRelaunchUi();

  const base = getRetraiteApiBase();
  let tries = 0;

  const btn = document.getElementById('btnTriggerMobilePay');
  const relBtnPoll = document.getElementById('btnRelaunchPaymentPoll');
  const hint = document.getElementById('mobilePayHint');
  const origHtml =
    originalBtnHtml !== undefined && originalBtnHtml !== null
      ? originalBtnHtml
      : btn
        ? btn.innerHTML
        : '';

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Suivi du paiement…';
  }

  const finishPollStopped = () => {
    stopMobilePaymentPollTimer();
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = origHtml;
    }
    if (relBtnPoll) {
      relBtnPoll.disabled = false;
      relBtnPoll.classList.remove('loading');
    }
  };

  if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES) {
    trackRetraiteFunnel(
      RETRAITE_FUNNEL_STAGES.payment_mobile_polling,
      'Surveillance opérateur en cours sur la page.',
      { channel: 'mobile_money', payment_reference: reference }
    );
  }

  const runPollTick = async () => {
    if (mobilePayPollInFlight) {
      return;
    }
    mobilePayPollInFlight = true;
    tries += 1;
    const pct = Math.round((Math.min(tries, maxTicks) / maxTicks) * 100);
    setPaymentProgressStep(
      2,
      `Attente de la confirmation de votre opérateur… ${pct} % (contrôle ${tries}/${maxTicks}). Gardez cette page ouverte.`
    );
    if (hint) {
      hint.textContent =
        'Vérifiez les notifications sur votre téléphone. Nous interrogeons votre opérateur pour confirmer le paiement.';
    }

    if (tries > maxTicks) {
      finishPollStopped();
      setPaymentProgressStep(2, 'Temps d’attente dépassé.');
      mobilePayPollInFlight = false;

      if (!decrementCreditOnTimeout) {
        mobilePayManualSessionsLeft = MOBILE_PAY_MANUAL_SESSIONS_MAX;
        if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES) {
          trackRetraiteFunnel(
            RETRAITE_FUNNEL_STAGES.payment_mobile_poll_timeout,
            'Première série de vérification terminée sans confirmation.',
            { channel: 'mobile_money' }
          );
        }
        showPaymentBanner(
          'Première surveillance terminée sans confirmation. Validez le paiement sur le téléphone si ce n’est pas fait, puis utilisez «&nbsp;Relancer la vérification du statut&nbsp;» jusqu’à 3 fois.',
          'warning'
        );
        showPaymentPollRelaunchUi(mobilePayManualSessionsLeft);
      } else if (mobilePayManualSessionsLeft !== null && mobilePayManualSessionsLeft > 0) {
        mobilePayManualSessionsLeft -= 1;
        if (mobilePayManualSessionsLeft > 0) {
          showPaymentBanner(
            'Cette série de vérifications s’est terminée sans confirmation. Contrôlez votre téléphone puis relancez si besoin, ou changez de moyen de paiement.',
            'warning'
          );
          showPaymentPollRelaunchUi(mobilePayManualSessionsLeft);
        } else {
          showMobilePaymentPollFullyExhausted();
        }
      } else {
        showMobilePaymentPollFullyExhausted();
      }
      return;
    }

    let json = {};
    try {
      const res = await fetch(`${base}/payments/check?reference=${encodeURIComponent(reference)}`, {
        headers: { Accept: 'application/json' },
      });
      json = await res.json().catch(() => ({}));
      if (!res.ok) {
        mobilePayPollErrorStreak += 1;
        const errMsg = json.message || 'Impossible de joindre le service de vérification.';
        setPaymentProgressStep(
          2,
          `${errMsg} (tentative ${tries}/${maxTicks}) — nous réessayons automatiquement…`
        );
        if (mobilePayPollErrorStreak >= 3) {
          showPaymentBanner(
            `<strong>Problème technique</strong> lors de la vérification : ${errMsg}. Gardez la page ouverte ou utilisez « Relancer la vérification ».`,
            'warning'
          );
        }
        mobilePayPollInFlight = false;
        return;
      }
      mobilePayPollErrorStreak = 0;
    } catch (e) {
      mobilePayPollErrorStreak += 1;
      setPaymentProgressStep(
        2,
        `Connexion interrompue (tentative ${tries}/${maxTicks}). Nouvelle tentative…`
      );
      mobilePayPollInFlight = false;
      return;
    }

    const d = json.data || {};
    if (retraitePaymentPollIndicatesSuccess(d)) {
      finishPollStopped();
      endMobilePaymentPollSession();
      mobilePayManualSessionsLeft = null;
      setPaymentProgressStep(3, 'Le paiement est confirmé. Vous pouvez passer à votre billet.');
      showPaymentBanner('Paiement confirmé par votre opérateur. Ouverture de votre billet…', 'success');
      App.paymentModeCompleted = mode;
      if (hint) hint.textContent = 'Paiement confirmé.';
      if (typeof finalizeBadgeUi === 'function') {
        await finalizeBadgeUi('electronic_success');
      }
      mobilePayPollInFlight = false;
      return;
    }

    const code = d.statut_code;
    const cancelled = code === 1 || code === '1';
    if (cancelled) {
      finishPollStopped();
      endMobilePaymentPollSession();
      mobilePayManualSessionsLeft = null;
      if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES) {
        trackRetraiteFunnel(
          RETRAITE_FUNNEL_STAGES.payment_mobile_cancelled,
          'Paiement annulé côté opérateur.',
          { channel: 'mobile_money' }
        );
      }
      setPaymentProgressStep(2, 'Paiement annulé par l’opérateur.');
      showPaymentBanner(
        'Ce paiement a été annulé côté opérateur. Vous pouvez relancer, ou changer immédiatement de moyen de paiement (carte / espèces).',
        'warning'
      );
      if (hint) hint.textContent = 'Paiement annulé.';
    }
    mobilePayPollInFlight = false;
  };

  void runPollTick();
  pollTimer = setInterval(() => {
    void runPollTick();
  }, MOBILE_PAY_POLL_MS);
}

function pollPaymentStatus(reference, mode, originalBtnHtml) {
  startMobilePaymentStatusPolling(reference, mode, originalBtnHtml, {
    decrementManualCreditOnTimeout: false,
  });
}

function wireRelaunchPaymentPoll() {
  const relBtn = document.getElementById('btnRelaunchPaymentPoll');
  if (!relBtn || relBtn.dataset.wiredPoll === '1') return;
  relBtn.dataset.wiredPoll = '1';
  relBtn.addEventListener('click', () => {
    if (!App.paymentReference) {
      retraiteNotifyToast('Référence de paiement introuvable. Utilisez « Déclencher le paiement ».', 'warning');
      return;
    }
    if (mobilePayManualSessionsLeft === null || mobilePayManualSessionsLeft < 1) {
      retraiteNotifyToast('Aucune relance disponible. Réessayez le paiement ou choisissez un autre mode.', 'warning');
      return;
    }
    showPaymentBanner(
      'Nouvelle série de vérifications auprès de l’opérateur… Laissez cette page ouverte.',
      'info'
    );
    if (document.getElementById('mobilePayHint')) {
      document.getElementById('mobilePayHint').textContent =
        'Contrôle du statut en cours. Répondez à l’invite sur votre téléphone si elle réapparaît.';
    }
    const orig =
      typeof App.mobilePayPollOrigBtnHtml === 'string' ? App.mobilePayPollOrigBtnHtml : '';
    relBtn.disabled = true;
    relBtn.classList.add('loading');
    relBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Relance en cours…';
    startMobilePaymentStatusPolling(App.paymentReference, 'mobile_money', orig, {
      decrementManualCreditOnTimeout: true,
    });
  });
}

async function handleCardReturnFlash() {
  const ret = window.__RETRAITE_CARD_RETURN__;
  if (!ret || !ret.ref) return;

  if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES && App.participantId) {
    trackRetraiteFunnel(
      RETRAITE_FUNNEL_STAGES.payment_card_return_unpaid,
      ret.status === 'missing' ? 'Retour carte sans référence.' : 'Retour carte sans encaissement.',
      { channel: 'card', payment_reference: ret.ref }
    );
  }

  showPaymentBanner(
    ret.status === 'missing'
      ? 'Référence de paiement introuvable : reprenez l’étape carte ou utilisez un autre mode.'
      : 'Retour carte : transaction annulée ou refusée. Réessayez par carte ou choisissez un autre moyen de paiement.',
    'warning'
  );
  App.paymentReference = ret.ref;

  setTimeout(() => {
    if (typeof goToStep === 'function') goToStep(4);
  }, 350);
}

async function resumeAfterCardPayment(reference) {
  const base = getRetraiteApiBase();
  if (!base) return;

  const res = await fetch(`${base}/payments/receipt?reference=${encodeURIComponent(reference)}`, {
    headers: { Accept: 'application/json' },
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    retraiteNotifyError({
      title: 'Reprise d’inscription',
      text: json.message || 'Résumé de paiement introuvable.',
      persistent: true,
    });
    return;
  }

  const participant = json.data && json.data.participant;
  if (participant && participant.id) App.participantId = participant.id;
  if (App.participantId) {
    sessionStorage.setItem('retraite_participant_id', String(App.participantId));
  }

  App.paymentReference = reference;

  const receiptPaid =
    participant &&
    participant.paiement_valide &&
    json.data &&
    json.data.etat === 'payee';

  if (receiptPaid) {
    showPaymentBanner('Paiement confirmé. Ouverture de votre billet…', 'success');
    if (typeof finalizeBadgeUi === 'function') finalizeBadgeUi('electronic_success');
    if (typeof resetRetraiteUrlParams === 'function') resetRetraiteUrlParams();
    return;
  }

  retraiteNotifyToast(
    'Le paiement n’est pas encore enregistré comme encaissé. Patientez ou repassez à l’étape paiement.',
    'warning'
  );
  if (typeof goToStep === 'function') goToStep(4);
}

/**
 * Reprend le suivi Mobile Money après rechargement de la page.
 */
async function resumeInscriptionPaymentPollIfNeeded() {
  let ref = null;
  let polling = false;
  try {
    ref = sessionStorage.getItem('retraite_payment_ref');
    polling = sessionStorage.getItem('retraite_payment_poll') === '1';
  } catch (e) {
    return;
  }
  if (!polling || !ref || !App.participantId) {
    return;
  }
  App.paymentReference = ref;
  App.paymentPollActive = true;
  if (typeof goToStep === 'function') {
    goToStep(4);
  }
  const mmRadio = document.getElementById('payModeMm');
  if (mmRadio) {
    mmRadio.checked = true;
    togglePaymentSections('mobile_money');
  }
  setPaymentProgressStep(2, 'Reprise du suivi après rechargement de la page…');
  showPaymentBanner(
    'Nous reprenons la vérification de votre paiement Mobile Money. Laissez cette page ouverte.',
    'info'
  );
  const btn = document.getElementById('btnTriggerMobilePay');
  const orig = btn ? btn.innerHTML : '';
  startMobilePaymentStatusPolling(ref, 'mobile_money', orig, {
    decrementManualCreditOnTimeout: false,
  });
}

window.onEnterPaymentStep = onEnterPaymentStep;
window.wirePaymentModes = wirePaymentModes;
window.confirmRecapAndProceed = confirmRecapAndProceed;
window.resumeAfterCardPayment = resumeAfterCardPayment;
window.resumeInscriptionPaymentPollIfNeeded = resumeInscriptionPaymentPollIfNeeded;

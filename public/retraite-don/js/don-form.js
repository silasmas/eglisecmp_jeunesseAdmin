'use strict';

function getDonApiBase() {
  const meta = document.querySelector('meta[name="retraite-don-api-base"]');
  return meta ? meta.getAttribute('content').replace(/\/$/, '') : '/api/v1/retreat/donations';
}

function escapeHtml(text) {
  const d = document.createElement('div');
  d.textContent = text == null ? '' : String(text);
  return d.innerHTML;
}

const DonApp = {
  kind: 'in_kind',
  context: null,
  donationId: null,
  donationReference: null,
  pollTimer: null,
  selectedFlexpayType: null,
  proofFile: null,
  mobilePayPollOrigBtnHtml: null,
};

const DON_MOBILE_PAY_POLL_MS = 2000;
const DON_MOBILE_PAY_POLL_INITIAL_MAX_TICKS = 10;
const DON_MOBILE_PAY_POLL_RELAUNCH_MAX_TICKS = 5;
const DON_MOBILE_PAY_MANUAL_SESSIONS_MAX = 3;

let donMobilePayManualSessionsLeft = null;
let donMobilePayPollInFlight = false;
let donMobilePayPollErrorStreak = 0;

function updateCapacityUi() {
  const banner = document.getElementById('donCapacityBanner');
  const sponsorHint = document.getElementById('sponsorCapacityHint');
  const youthInput = document.getElementById('youthSlots');
  const ctx = DonApp.context;

  if (banner && ctx && ctx.places_message) {
    banner.classList.remove('hidden');
    banner.textContent = ctx.places_message;
  }

  const available = ctx && ctx.sponsor_slots_available != null ? Number(ctx.sponsor_slots_available) : null;

  if (sponsorHint) {
    if (available === null) {
      sponsorHint.textContent = '';
    } else if (available === 0) {
      sponsorHint.textContent = 'Aucune place disponible pour sponsoriser des jeunes actuellement.';
    } else {
      sponsorHint.textContent = `Maximum sponsorisable : ${available} jeune${available > 1 ? 's' : ''}.`;
    }
  }

  if (youthInput && available !== null) {
    youthInput.max = String(Math.max(1, available));
    if (Number(youthInput.value) > available) {
      youthInput.value = String(available);
    }
  }

  updateYouthTotal();
}

/**
 * Désactive l'option « prise en charge jeunes » si la retraite est clôturée.
 *
 * @return {void}
 */
function applySponsorshipClosedState() {
  const ctx = DonApp.context;
  const option = document.getElementById('sponsorYouthOption');
  const input = document.getElementById('cashPurposeSponsorYouth');
  const hint = document.getElementById('sponsorClosedHint');
  const generalRadio = document.querySelector('input[name="cashPurpose"][value="general"]');

  if (!ctx || !ctx.sponsorship_disabled) {
    option?.classList.remove('is-disabled');
    if (input) {
      input.disabled = false;
    }
    if (hint) {
      hint.textContent = '';
      hint.classList.add('hidden');
    }
    return;
  }

  option?.classList.add('is-disabled');
  if (input) {
    input.disabled = true;
    if (input.checked && generalRadio) {
      generalRadio.checked = true;
    }
  }
  if (hint) {
    hint.textContent =
      ctx.sponsorship_disabled_reason ||
      'La prise en charge de jeunes n\'est plus disponible pour cette retraite clôturée.';
    hint.classList.remove('hidden');
  }
  toggleCashPurposeFields();
}

function updateCashAmountFieldHints() {
  const input = document.getElementById('cashAmount');
  if (!input || !DonApp.context) {
    return;
  }

  const currency = String(DonApp.context.currency || 'USD').toUpperCase();
  const isCdf = currency === 'CDF';

  input.min = isCdf ? '100' : '1';
  input.step = isCdf ? '1' : '0.01';
  input.placeholder = isCdf ? 'Minimum 100 FC' : 'Minimum 1 $';
}

async function loadDonContext() {
  const res = await fetch(`${getDonApiBase()}/context`, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(json.message || 'Impossible de charger la retraite.');
  }
  DonApp.context = json.data;
  const cur = document.getElementById('donCurrency');
  const unit = document.getElementById('unitPriceLabel');
  if (cur) {
    cur.textContent = DonApp.context.currency || 'USD';
  }
  if (unit) {
    unit.textContent = `${DonApp.context.price_to_pay} ${DonApp.context.currency}`;
  }
  updateCashAmountFieldHints();
  updateCapacityUi();
  applySponsorshipClosedState();
}

function updateYouthTotal() {
  const slots = Number(document.getElementById('youthSlots')?.value || 1);
  const price = DonApp.context ? Number(DonApp.context.price_to_pay) : 0;
  const cur = DonApp.context?.currency || 'USD';
  const el = document.getElementById('youthTotalLabel');
  if (el) {
    el.textContent = `${(slots * price).toFixed(2)} ${cur}`;
  }
}

function setDonKind(kind) {
  DonApp.kind = kind;
  document.querySelectorAll('.don-tab').forEach((t) => {
    t.classList.toggle('is-active', t.dataset.kind === kind);
  });
  document.getElementById('inKindPanel')?.classList.toggle('hidden', kind !== 'in_kind');
  document.getElementById('cashPanel')?.classList.toggle('hidden', kind !== 'cash');
  document.getElementById('cashPaymentBlock')?.classList.add('hidden');
  const btn = document.getElementById('btnSubmitDon');
  if (btn) {
    btn.innerHTML =
      kind === 'in_kind'
        ? '<i class="bi bi-heart"></i> Envoyer ma proposition de don'
        : '<i class="bi bi-cash-coin"></i> Préparer le paiement';
  }
  toggleCashPurposeFields();
}

function getCashPurpose() {
  const checked = document.querySelector('input[name="cashPurpose"]:checked');
  return checked ? checked.value : 'general';
}

function toggleCashPurposeFields() {
  const purpose = getCashPurpose();
  document.getElementById('generalAmountField')?.classList.toggle('hidden', purpose !== 'general');
  document.getElementById('youthSlotsField')?.classList.toggle('hidden', purpose !== 'sponsor_youth');
  updateYouthTotal();
}

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

function flexpayMsisdnMatchesSelectedType(normalized, providerKey) {
  const list = (DonApp.context && DonApp.context.flexpay_mobile_providers) || [];
  const p = list.find(
    (x) => String(x.type) === String(providerKey) || String(x.code) === String(providerKey)
  );
  if (!p || !p.msisdn_regex) {
    return /^243\d{9}$/.test(normalized);
  }
  try {
    return new RegExp(p.msisdn_regex).test(normalized);
  } catch (e) {
    return /^243\d{9}$/.test(normalized);
  }
}

function syncDonFlexpayMsisdnFormatHint() {
  const hint = document.getElementById('donFlexpayPhoneFormatHint');
  if (!hint) {
    return;
  }
  const p = ((DonApp.context && DonApp.context.flexpay_mobile_providers) || []).find(
    (x) =>
      String(x.type) === String(DonApp.selectedFlexpayType) ||
      String(x.code) === String(DonApp.selectedFlexpayType)
  );
  if (!p) {
    hint.innerHTML =
      'Sans le signe « + » : 12 chiffres au total, en commençant par <strong>243</strong> (format international RDC sans préfixe plus).';
    return;
  }
  hint.innerHTML = `Réseau <strong>${escapeHtml(p.label || p.code || '')}</strong> — saisissez 12 chiffres commençant par <strong>243</strong>, cohérents avec cette ligne.`;
}

function setDonPaymentProgressStep(activeStep, detailText) {
  const panel = document.getElementById('donPaymentProgressPanel');
  if (!panel) {
    return;
  }
  panel.classList.remove('hidden');
  panel.querySelectorAll('.payment-progress-step').forEach((el) => {
    const n = Number(el.dataset.step);
    el.classList.remove('active', 'done');
    if (n < activeStep) {
      el.classList.add('done');
    }
    if (n === activeStep) {
      el.classList.add('active');
    }
  });
  const d = document.getElementById('donPaymentProgressDetail');
  if (d) {
    d.textContent = detailText || '';
  }
}

function hideDonPaymentPollRelaunchUi() {
  const wrap = document.getElementById('donPaymentPollRelaunchWrap');
  const relBtn = document.getElementById('btnRelaunchDonPaymentPoll');
  if (wrap) {
    wrap.classList.add('hidden');
  }
  if (relBtn) {
    relBtn.disabled = false;
  }
}

function hideDonPaymentProgressPanel() {
  const panel = document.getElementById('donPaymentProgressPanel');
  if (panel) {
    panel.classList.add('hidden');
  }
  const d = document.getElementById('donPaymentProgressDetail');
  if (d) {
    d.textContent = '';
  }
  hideDonPaymentPollRelaunchUi();
}

function showDonPaymentBanner(html, variant) {
  const el = document.getElementById('donPaymentStatusBanner');
  if (!el) {
    return;
  }
  el.innerHTML = `<i class="bi bi-info-circle"></i> <span>${html}</span>`;
  el.classList.remove('hidden', 'warning', 'info', 'success');
  el.classList.add(variant === 'success' ? 'success' : variant === 'warning' ? 'warning' : 'info');
}

function showDonPaymentPollRelaunchUi(sessionsLeft) {
  const wrap = document.getElementById('donPaymentPollRelaunchWrap');
  const hint = document.getElementById('donPaymentPollRelaunchHint');
  const relBtn = document.getElementById('btnRelaunchDonPaymentPoll');
  if (!wrap || !relBtn || sessionsLeft < 1) {
    return;
  }
  wrap.classList.remove('hidden');
  const n = sessionsLeft;
  if (hint) {
    hint.innerHTML = `L’opérateur n’a pas renvoyé de confirmation dans le délai. Utilisez le bouton pour <strong>interroger à nouveau le statut</strong> (encore <strong>${n}</strong> relance${n > 1 ? 's' : ''} possible${n > 1 ? 's' : ''}).`;
  }
  relBtn.disabled = false;
  relBtn.innerHTML = `<i class="bi bi-arrow-clockwise"></i> Relancer la vérification du statut (${n} essai${n > 1 ? 's' : ''} restant${n > 1 ? 's' : ''})`;
}

function showDonMobilePaymentPollFullyExhausted() {
  hideDonPaymentPollRelaunchUi();
  const hint = document.getElementById('donMobilePayHint');
  setDonPaymentProgressStep(2, 'Plusieurs vérifications sans confirmation automatique.');
  showDonPaymentBanner(
    '<strong>Nous n’avons toujours pas de confirmation</strong>, même après 3 relances. <strong>Vérifiez votre compte Mobile Money</strong> : un débit correspondant au montant du don est-il apparu ? Si oui, conservez une preuve (capture) et contactez l’organisation. Sinon, vous pouvez <strong>réessayer</strong> avec «&nbsp;Déclencher le paiement&nbsp;» ou choisir un <strong>autre mode</strong> sur cette page.',
    'warning'
  );
  if (hint) {
    hint.innerHTML =
      'En cas de débit sans confirmation : notez l’heure et le montant, puis contactez l’organisation.';
  }
  donMobilePayManualSessionsLeft = null;
}

function stopDonMobilePaymentPollTimer() {
  if (DonApp.pollTimer) {
    clearInterval(DonApp.pollTimer);
    DonApp.pollTimer = null;
  }
  donMobilePayPollInFlight = false;
}

function donPaymentPollIndicatesSuccess(d) {
  if (!d || typeof d !== 'object') {
    return false;
  }
  if (d.paid === true) {
    return true;
  }
  const code = d.statut_code;
  if (code === 0 || code === '0') {
    return true;
  }
  if (d.payee === true) {
    return true;
  }
  return false;
}

function prefillDonFlexpayPhoneFromDonor() {
  const donorPhone = document.getElementById('donorPhone')?.value.trim();
  const flexInput = document.getElementById('donFlexpayPhone');
  if (!flexInput || flexInput.value.trim()) {
    return;
  }
  if (donorPhone) {
    flexInput.value = normalizeFlexpayCdMsisdn(donorPhone);
  }
}

function mountDonFlexpayProviders(providers) {
  const mount = document.getElementById('donFlexpayProvidersMount');
  if (!mount) {
    return;
  }
  mount.innerHTML = '';
  const list = providers || [];
  DonApp.selectedFlexpayType = list[0] ? String(list[0].type || list[0].code || '') : null;

  list.forEach((p, i) => {
    const card = document.createElement('button');
    card.type = 'button';
    card.className = 'payment-card' + (i === 0 ? ' flexpay-prov-active' : '');
    card.dataset.flexpayType = String(p.type || p.code || '');
    const code = (p.code || '').toLowerCase();
    const iconKind = code.includes('airtel') || code.includes('afri') ? 'airtel' : code.includes('orange') ? 'bank' : 'mpesa';
    card.innerHTML = `
      <div class="payment-card-icon ${iconKind}"><i class="bi bi-phone"></i></div>
      <div class="payment-card-name">${escapeHtml(p.label || p.code || '')}</div>
      <div class="payment-card-number">Paiement mobile</div>
    `;
    card.addEventListener('click', () => {
      mount.querySelectorAll('.payment-card').forEach((c) => c.classList.remove('flexpay-prov-active'));
      card.classList.add('flexpay-prov-active');
      DonApp.selectedFlexpayType = card.dataset.flexpayType;
      syncDonFlexpayMsisdnFormatHint();
    });
    mount.appendChild(card);
  });
  syncDonFlexpayMsisdnFormatHint();
}

function toggleDonPaymentSections(mode) {
  const mm = document.getElementById('donMobileMoneyBlock');
  const card = document.getElementById('donCardBlock');
  const cash = document.getElementById('donCashBlock');
  [mm, card, cash].forEach((el) => el && el.classList.add('hidden'));
  if (mode === 'mobile_money' && mm) {
    mm.classList.remove('hidden');
    syncDonFlexpayMsisdnFormatHint();
  }
  if (mode === 'card' && card) {
    card.classList.remove('hidden');
  }
  if (mode === 'cash' && cash) {
    cash.classList.remove('hidden');
  }

  const ext = DonApp.context && DonApp.context.card_payment && DonApp.context.card_payment.external_form_url;
  const txt = document.getElementById('donCardExplainerText');
  if (txt) {
    txt.textContent = ext
      ? 'Vous allez être redirigé vers le formulaire carte défini pour cet événement.'
      : 'Vous allez être redirigé vers le portail sécurisé de paiement par carte.';
  }
}

function wireDonPaymentModes() {
  document.querySelectorAll('input[name="donPaymentMode"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      if (radio.checked) {
        toggleDonPaymentSections(radio.value);
      }
    });
  });
}

function showDonPaymentPrepared() {
  const block = document.getElementById('cashPaymentBlock');
  block?.classList.remove('hidden');
  mountDonFlexpayProviders(DonApp.context?.flexpay_mobile_providers || []);
  prefillDonFlexpayPhoneFromDonor();
  hideDonPaymentProgressPanel();
  const banner = document.getElementById('donPaymentStatusBanner');
  if (banner) {
    banner.classList.add('hidden');
  }
  const mmRadio = document.getElementById('donPayModeMm');
  if (mmRadio) {
    mmRadio.checked = true;
    toggleDonPaymentSections('mobile_money');
  }
  const submitBtn = document.getElementById('btnSubmitDon');
  if (submitBtn) {
    submitBtn.classList.add('hidden');
  }
}

/**
 * Réinitialise le formulaire don après un envoi ou un paiement terminé.
 *
 * @return {void}
 */
function resetDonForm() {
  stopDonMobilePaymentPollTimer();
  hideDonPaymentProgressPanel();
  donMobilePayManualSessionsLeft = null;

  DonApp.donationId = null;
  DonApp.donationReference = null;
  DonApp.proofFile = null;
  DonApp.selectedFlexpayType = null;
  DonApp.mobilePayPollOrigBtnHtml = null;

  document.getElementById('donForm')?.reset();

  document.getElementById('cashPaymentBlock')?.classList.add('hidden');
  document.getElementById('btnSubmitDon')?.classList.remove('hidden');

  const banner = document.getElementById('donPaymentStatusBanner');
  if (banner) {
    banner.classList.add('hidden');
    banner.innerHTML = '';
  }

  const statusEl = document.getElementById('donPaymentStatus');
  if (statusEl) {
    statusEl.textContent = '';
  }

  const hint = document.getElementById('donMobilePayHint');
  if (hint) {
    hint.textContent = 'Une demande sera envoyée sur votre téléphone. Laissez cette page ouverte.';
  }

  const proofInput = document.getElementById('donProofInput');
  if (proofInput) {
    proofInput.value = '';
  }
  document.getElementById('donProofDropZone')?.classList.remove('has-file');
  document.getElementById('donProofPreview')?.classList.add('hidden');
  const image = document.getElementById('donProofImage');
  if (image) {
    image.src = '';
    image.style.display = 'none';
  }

  setDonKind('in_kind');
  toggleCashPurposeFields();
  updateCapacityUi();
}

/**
 * Réinitialise le formulaire après un court délai pour laisser lire le message de succès.
 *
 * @param {number} delayMs Délai en millisecondes
 * @return {void}
 */
function scheduleDonFormReset(delayMs = 3500) {
  window.setTimeout(() => {
    resetDonForm();
  }, delayMs);
}

function buildDonPayload() {
  return {
    donor_name: document.getElementById('donorName').value.trim(),
    donor_phone: document.getElementById('donorPhone').value.trim(),
    donor_email: document.getElementById('donorEmail').value.trim(),
    donor_message: document.getElementById('donorMessage').value.trim(),
    event_id: DonApp.context?.event_id,
  };
}

async function submitDonForm(e) {
  e.preventDefault();
  const base = getDonApiBase();
  const payload = buildDonPayload();

  if (!payload.donor_name) {
    retraiteNotifyToast('Indiquez votre nom.', 'warning');
    return;
  }
  if (!payload.donor_email) {
    retraiteNotifyToast('Indiquez votre e-mail.', 'warning');
    return;
  }

  const btn = document.getElementById('btnSubmitDon');
  if (btn) {
    btn.disabled = true;
  }

  try {
    if (DonApp.kind === 'in_kind') {
      payload.in_kind_description = document.getElementById('inKindDescription').value.trim();
      if (!payload.in_kind_description) {
        retraiteNotifyToast('Décrivez votre don en nature.', 'warning');
        return;
      }
      const res = await fetch(`${base}/in-kind`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify(payload),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        throw new Error(json.message || 'Échec envoi.');
      }
      retraiteNotifyToast('Merci ! Un e-mail de confirmation vous sera envoyé.', 'success');
      scheduleDonFormReset(2500);
      return;
    }

    payload.cash_purpose = getCashPurpose();
    if (payload.cash_purpose === 'sponsor_youth' && DonApp.context?.sponsorship_disabled) {
      retraiteNotifyToast(
        DonApp.context.sponsorship_disabled_reason ||
          'La prise en charge de jeunes n\'est plus disponible pour cette retraite clôturée.',
        'warning'
      );
      return;
    }
    if (payload.cash_purpose === 'general') {
      payload.amount = document.getElementById('cashAmount').value;
    } else {
      payload.youth_slots_count = document.getElementById('youthSlots').value;
      const available = DonApp.context?.sponsor_slots_available;
      if (available != null && Number(payload.youth_slots_count) > Number(available)) {
        retraiteNotifyToast(`Maximum ${available} jeune(s) sponsorisable(s).`, 'warning');
        return;
      }
    }

    const res = await fetch(`${base}/cash/init`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(payload),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(json.message || 'Échec préparation.');
    }
    DonApp.donationId = json.data.donation_id;
    DonApp.donationReference = json.data.reference;
    showDonPaymentPrepared();
    retraiteNotifyToast('Don préparé. Choisissez votre mode de paiement ci-dessous.', 'info');
  } catch (err) {
    retraiteNotifyToast(err.message || 'Erreur.', 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
    }
  }
}

async function triggerDonMobilePay() {
  if (!DonApp.donationId) {
    retraiteNotifyToast('Préparez d’abord le don.', 'warning');
    return;
  }
  if (!DonApp.selectedFlexpayType) {
    retraiteNotifyToast('Choisissez un opérateur Mobile Money.', 'warning');
    return;
  }

  const rawPhone = document.getElementById('donFlexpayPhone')?.value.trim();
  if (!rawPhone) {
    retraiteNotifyToast('Indiquez le numéro Mobile Money.', 'warning');
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
  if (!flexpayMsisdnMatchesSelectedType(normalized, DonApp.selectedFlexpayType)) {
    retraiteNotifyToast(
      'Ce numéro ne semble pas correspondre au réseau choisi. Vérifiez l’opérateur ou le numéro saisi.',
      'warning'
    );
    return;
  }

  const btn = document.getElementById('btnDonMobilePay');
  const originalBtnHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Préparation du paiement…';
  }

  stopDonMobilePaymentPollTimer();
  hideDonPaymentPollRelaunchUi();
  donMobilePayManualSessionsLeft = null;

  setDonPaymentProgressStep(1, 'Nous préparons la demande auprès de votre opérateur mobile…');
  showDonPaymentBanner('Transmission de la demande de paiement à votre opérateur…', 'info');

  const statusEl = document.getElementById('donPaymentStatus');
  if (statusEl) {
    statusEl.textContent = '';
  }

  let json = {};
  let res;
  try {
    res = await fetch(`${getDonApiBase()}/${DonApp.donationId}/payments/mobile`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({ phone: normalized, flexpay_type: DonApp.selectedFlexpayType }),
    });
    json = await res.json().catch(() => ({}));
  } catch (err) {
    json = {};
    res = { ok: false };
  }

  if (!res.ok) {
    hideDonPaymentProgressPanel();
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalBtnHtml;
    }
    if (statusEl) {
      statusEl.textContent = json.message || 'Échec.';
    }
    showDonPaymentBanner(json.message || 'Paiement refusé par l’opérateur.', 'warning');
    retraiteNotifyToast(json.message || 'Paiement refusé.', 'error');
    return;
  }

  if (btn) {
    btn.disabled = false;
    btn.innerHTML = originalBtnHtml;
  }

  setDonPaymentProgressStep(
    2,
    'Une alerte a été envoyée sur votre téléphone — validez avec votre code secret ou selon les instructions de votre opérateur.'
  );
  showDonPaymentBanner(
    json.message ||
      'Demande transmise à votre opérateur. Confirmez le paiement sur votre téléphone puis patientez ici.',
    'info'
  );
  if (statusEl) {
    statusEl.textContent = 'Validez sur votre téléphone. Vérification en cours…';
  }

  DonApp.mobilePayPollOrigBtnHtml = originalBtnHtml;
  startDonPaymentPoll(originalBtnHtml, { decrementManualCreditOnTimeout: false });
}

async function triggerDonCardPay() {
  if (!DonApp.donationId) {
    return;
  }
  const btn = document.getElementById('btnDonCardPay');
  const orig = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Redirection…';
  }

  const res = await fetch(`${getDonApiBase()}/${DonApp.donationId}/payments/card`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok || !json.data?.redirect_url) {
    retraiteNotifyToast(json.message || 'Impossible d’ouvrir le paiement carte.', 'error');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = orig;
    }
    return;
  }
  window.location.href = json.data.redirect_url;
}

async function submitDonCashProof() {
  if (!DonApp.donationId || !DonApp.proofFile) {
    retraiteNotifyToast('Joignez une preuve de paiement.', 'warning');
    return;
  }
  const btn = document.getElementById('btnDonSubmitCashProof');
  const orig = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Envoi…';
  }

  const fd = new FormData();
  fd.append('proof', DonApp.proofFile);

  const res = await fetch(`${getDonApiBase()}/${DonApp.donationId}/payments/cash`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
    body: fd,
  });
  const json = await res.json().catch(() => ({}));

  if (!res.ok) {
    retraiteNotifyToast(json.message || 'Erreur envoi preuve.', 'error');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = orig;
    }
    return;
  }

  const statusEl = document.getElementById('donPaymentStatus');
  if (statusEl) {
    statusEl.textContent =
      'Preuve envoyée. Vous serez notifié par e-mail après validation par l\'administration.';
  }
  retraiteNotifyToast(json.message || 'Preuve reçue.', 'success');
  if (btn) {
    btn.disabled = false;
    btn.innerHTML = orig;
  }
  scheduleDonFormReset(3500);
}

function startDonPaymentPoll(originalBtnHtml, pollOptions) {
  const opts = pollOptions || {};
  const decrementCreditOnTimeout = !!opts.decrementManualCreditOnTimeout;
  const maxTicks = decrementCreditOnTimeout
    ? DON_MOBILE_PAY_POLL_RELAUNCH_MAX_TICKS
    : DON_MOBILE_PAY_POLL_INITIAL_MAX_TICKS;

  stopDonMobilePaymentPollTimer();
  hideDonPaymentPollRelaunchUi();

  let tries = 0;
  const btn = document.getElementById('btnDonMobilePay');
  const relBtnPoll = document.getElementById('btnRelaunchDonPaymentPoll');
  const hint = document.getElementById('donMobilePayHint');
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
    stopDonMobilePaymentPollTimer();
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = origHtml;
    }
    if (relBtnPoll) {
      relBtnPoll.disabled = false;
      relBtnPoll.classList.remove('loading');
    }
  };

  const runPollTick = async () => {
    if (donMobilePayPollInFlight) {
      return;
    }
    if (!DonApp.donationReference) {
      finishPollStopped();
      return;
    }

    tries += 1;
    donMobilePayPollInFlight = true;

    const pct = Math.min(100, Math.round((tries / maxTicks) * 100));
    setDonPaymentProgressStep(
      2,
      `Attente de la confirmation de votre opérateur… ${pct} % (contrôle ${tries}/${maxTicks}). Gardez cette page ouverte.`
    );
    if (hint) {
      hint.textContent =
        'Vérifiez les notifications sur votre téléphone. Nous interrogeons votre opérateur pour confirmer le paiement.';
    }

    if (tries > maxTicks) {
      finishPollStopped();
      setDonPaymentProgressStep(2, 'Temps d’attente dépassé.');
      donMobilePayPollInFlight = false;

      if (!decrementCreditOnTimeout) {
        donMobilePayManualSessionsLeft = DON_MOBILE_PAY_MANUAL_SESSIONS_MAX;
        showDonPaymentBanner(
          'Première surveillance terminée sans confirmation. Validez le paiement sur le téléphone si ce n’est pas fait, puis utilisez «&nbsp;Relancer la vérification du statut&nbsp;» jusqu’à 3 fois.',
          'warning'
        );
        showDonPaymentPollRelaunchUi(donMobilePayManualSessionsLeft);
      } else if (donMobilePayManualSessionsLeft !== null && donMobilePayManualSessionsLeft > 0) {
        donMobilePayManualSessionsLeft -= 1;
        if (donMobilePayManualSessionsLeft > 0) {
          showDonPaymentBanner(
            'Cette série de vérifications s’est terminée sans confirmation. Contrôlez votre téléphone puis relancez si besoin, ou changez de moyen de paiement.',
            'warning'
          );
          showDonPaymentPollRelaunchUi(donMobilePayManualSessionsLeft);
        } else {
          showDonMobilePaymentPollFullyExhausted();
        }
      } else {
        showDonMobilePaymentPollFullyExhausted();
      }
      return;
    }

    let json = {};
    try {
      const res = await fetch(
        `${getDonApiBase()}/payments/check?reference=${encodeURIComponent(DonApp.donationReference)}`,
        { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
      );
      json = await res.json().catch(() => ({}));
      if (!res.ok) {
        donMobilePayPollErrorStreak += 1;
        const errMsg = json.message || `Erreur serveur (${res.status})`;
        setDonPaymentProgressStep(
          2,
          `${errMsg} (tentative ${tries}/${maxTicks}) — nous réessayons automatiquement…`
        );
        if (donMobilePayPollErrorStreak >= 3) {
          showDonPaymentBanner(
            `<strong>Problème technique</strong> lors de la vérification : ${errMsg}. Gardez la page ouverte ou utilisez « Relancer la vérification ».`,
            'warning'
          );
        }
        donMobilePayPollInFlight = false;
        return;
      }
      donMobilePayPollErrorStreak = 0;
    } catch (e) {
      donMobilePayPollErrorStreak += 1;
      setDonPaymentProgressStep(
        2,
        `Connexion interrompue (tentative ${tries}/${maxTicks}). Nouvelle tentative…`
      );
      donMobilePayPollInFlight = false;
      return;
    }

    const d = json.data || {};
    if (donPaymentPollIndicatesSuccess(d)) {
      finishPollStopped();
      donMobilePayManualSessionsLeft = null;
      setDonPaymentProgressStep(3, 'Le paiement est confirmé. Merci pour votre générosité !');
      showDonPaymentBanner(
        'Paiement confirmé. Un e-mail de confirmation vous a été envoyé. Pour les codes parrainage, contactez le département jeunesse.',
        'success'
      );
      const statusEl = document.getElementById('donPaymentStatus');
      if (statusEl) {
        statusEl.textContent = 'Paiement confirmé. Consultez votre e-mail. Les codes parrainage se retirent auprès de l\'administration.';
      }
      if (hint) {
        hint.textContent = 'Paiement confirmé.';
      }
      retraiteNotifyToast('Merci pour votre don !', 'success');
      loadDonContext().catch(() => {});
      scheduleDonFormReset(4000);
      donMobilePayPollInFlight = false;
      return;
    }

    if (d.cash_pending) {
      finishPollStopped();
      donMobilePayPollInFlight = false;
      return;
    }

    const code = d.statut_code;
    const cancelled = code === 1 || code === '1';
    if (cancelled) {
      finishPollStopped();
      donMobilePayManualSessionsLeft = null;
      setDonPaymentProgressStep(2, 'Paiement annulé par l’opérateur.');
      showDonPaymentBanner(
        'Ce paiement a été annulé côté opérateur. Vous pouvez relancer, ou changer immédiatement de moyen de paiement (carte / espèces).',
        'warning'
      );
      if (hint) {
        hint.textContent = 'Paiement annulé.';
      }
    }

    donMobilePayPollInFlight = false;
  };

  void runPollTick();
  DonApp.pollTimer = setInterval(() => {
    void runPollTick();
  }, DON_MOBILE_PAY_POLL_MS);
}

function wireRelaunchDonPaymentPoll() {
  const relBtn = document.getElementById('btnRelaunchDonPaymentPoll');
  if (!relBtn || relBtn.dataset.wiredPoll === '1') {
    return;
  }
  relBtn.dataset.wiredPoll = '1';
  relBtn.addEventListener('click', () => {
    if (!DonApp.donationReference) {
      retraiteNotifyToast('Référence de paiement introuvable. Utilisez « Déclencher le paiement ».', 'warning');
      return;
    }
    if (donMobilePayManualSessionsLeft === null || donMobilePayManualSessionsLeft < 1) {
      retraiteNotifyToast('Aucune relance disponible. Réessayez le paiement ou choisissez un autre mode.', 'warning');
      return;
    }
    showDonPaymentBanner(
      'Nouvelle série de vérifications auprès de l’opérateur… Laissez cette page ouverte.',
      'info'
    );
    const hint = document.getElementById('donMobilePayHint');
    if (hint) {
      hint.textContent =
        'Contrôle du statut en cours. Répondez à l’invite sur votre téléphone si elle réapparaît.';
    }
    const orig =
      typeof DonApp.mobilePayPollOrigBtnHtml === 'string' ? DonApp.mobilePayPollOrigBtnHtml : '';
    relBtn.disabled = true;
    relBtn.classList.add('loading');
    relBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Relance en cours…';
    startDonPaymentPoll(orig, { decrementManualCreditOnTimeout: true });
  });
}

function wireDonProofUpload() {
  const input = document.getElementById('donProofInput');
  const zone = document.getElementById('donProofDropZone');
  const preview = document.getElementById('donProofPreview');
  const fileName = document.getElementById('donProofFileName');
  const image = document.getElementById('donProofImage');
  const removeBtn = document.getElementById('donProofRemoveBtn');

  if (!input || !zone) {
    return;
  }

  zone.addEventListener('click', () => input.click());
  zone.addEventListener('dragover', (e) => {
    e.preventDefault();
    zone.classList.add('dragover');
  });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', (e) => {
    e.preventDefault();
    zone.classList.remove('dragover');
    if (e.dataTransfer.files[0]) {
      handleDonProofFile(e.dataTransfer.files[0]);
    }
  });
  input.addEventListener('change', (e) => {
    if (e.target.files[0]) {
      handleDonProofFile(e.target.files[0]);
    }
  });
  removeBtn?.addEventListener('click', () => {
    DonApp.proofFile = null;
    input.value = '';
    zone.classList.remove('has-file');
    preview?.classList.add('hidden');
    if (image) {
      image.src = '';
      image.style.display = 'none';
    }
  });

  function handleDonProofFile(file) {
    DonApp.proofFile = file;
    zone.classList.add('has-file');
    preview?.classList.remove('hidden');
    const span = fileName?.querySelector('span');
    if (span) {
      span.textContent = file.name;
    }
    if (image && file.type.startsWith('image/')) {
      image.src = URL.createObjectURL(file);
      image.style.display = 'block';
    } else if (image) {
      image.style.display = 'none';
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  loadDonContext().catch(() => {});
  document.querySelectorAll('.don-tab').forEach((tab) => {
    tab.addEventListener('click', () => setDonKind(tab.dataset.kind));
  });
  document.querySelectorAll('input[name="cashPurpose"]').forEach((r) => {
    r.addEventListener('change', toggleCashPurposeFields);
  });
  document.getElementById('youthSlots')?.addEventListener('input', updateYouthTotal);
  document.getElementById('donForm')?.addEventListener('submit', submitDonForm);
  document.getElementById('btnDonMobilePay')?.addEventListener('click', triggerDonMobilePay);
  document.getElementById('btnDonCardPay')?.addEventListener('click', triggerDonCardPay);
  document.getElementById('btnDonSubmitCashProof')?.addEventListener('click', submitDonCashProof);
  document.getElementById('donorPhone')?.addEventListener('input', () => {
    const flexInput = document.getElementById('donFlexpayPhone');
    const block = document.getElementById('cashPaymentBlock');
    if (flexInput && block && !block.classList.contains('hidden') && !flexInput.value.trim()) {
      prefillDonFlexpayPhoneFromDonor();
    }
  });
  wireDonPaymentModes();
  wireDonProofUpload();
  wireRelaunchDonPaymentPoll();
  toggleCashPurposeFields();
});

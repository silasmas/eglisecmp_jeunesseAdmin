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
};

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
  updateCapacityUi();
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

function mountDonFlexpayProviders(providers) {
  const mount = document.getElementById('donFlexpayProvidersMount');
  if (!mount) {
    return;
  }
  mount.innerHTML = '';
  const list = providers || [];
  DonApp.selectedFlexpayType = list[0] ? String(list[0].type) : null;

  list.forEach((p, i) => {
    const card = document.createElement('button');
    card.type = 'button';
    card.className = 'payment-card' + (i === 0 ? ' flexpay-prov-active' : '');
    card.dataset.flexpayType = String(p.type);
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
    });
    mount.appendChild(card);
  });
}

function toggleDonPaymentSections(mode) {
  const mm = document.getElementById('donMobileMoneyBlock');
  const card = document.getElementById('donCardBlock');
  const cash = document.getElementById('donCashBlock');
  [mm, card, cash].forEach((el) => el && el.classList.add('hidden'));
  if (mode === 'mobile_money' && mm) {
    mm.classList.remove('hidden');
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
      document.getElementById('donForm')?.reset();
      return;
    }

    payload.cash_purpose = getCashPurpose();
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
  const phone = document.getElementById('donFlexpayPhone')?.value.trim();
  if (!phone) {
    retraiteNotifyToast('Indiquez le numéro Mobile Money.', 'warning');
    return;
  }
  const statusEl = document.getElementById('donPaymentStatus');
  if (statusEl) {
    statusEl.textContent = 'Envoi de la demande…';
  }

  const res = await fetch(`${getDonApiBase()}/${DonApp.donationId}/payments/mobile`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
    body: JSON.stringify({ phone, provider_code: DonApp.selectedFlexpayType }),
  });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) {
    if (statusEl) {
      statusEl.textContent = json.message || 'Échec.';
    }
    retraiteNotifyToast(json.message || 'Paiement refusé.', 'error');
    return;
  }
  if (statusEl) {
    statusEl.textContent = 'Validez sur votre téléphone. Vérification en cours…';
  }
  startDonPaymentPoll();
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
      'Preuve envoyée. Validation admin en cours — vous recevrez un e-mail de confirmation.';
  }
  retraiteNotifyToast(json.message || 'Preuve reçue.', 'success');
  if (btn) {
    btn.disabled = false;
    btn.innerHTML = orig;
  }
}

function startDonPaymentPoll() {
  clearInterval(DonApp.pollTimer);
  let tries = 0;
  DonApp.pollTimer = setInterval(async () => {
    tries += 1;
    if (tries > 24 || !DonApp.donationReference) {
      clearInterval(DonApp.pollTimer);
      return;
    }
    const res = await fetch(
      `${getDonApiBase()}/payments/check?reference=${encodeURIComponent(DonApp.donationReference)}`,
      { headers: { Accept: 'application/json' } }
    );
    const json = await res.json().catch(() => ({}));
    if (json.data?.paid) {
      clearInterval(DonApp.pollTimer);
      const statusEl = document.getElementById('donPaymentStatus');
      if (statusEl) {
        statusEl.textContent = 'Paiement confirmé. Un e-mail de confirmation vous a été envoyé.';
      }
      retraiteNotifyToast('Merci pour votre don !', 'success');
      loadDonContext().catch(() => {});
    }
    if (json.data?.cash_pending) {
      clearInterval(DonApp.pollTimer);
    }
  }, 5000);
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
  wireDonPaymentModes();
  wireDonProofUpload();
  toggleCashPurposeFields();
});

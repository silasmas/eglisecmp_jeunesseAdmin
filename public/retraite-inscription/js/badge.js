/* ═══════════════════════════════════════════
   BADGE & FINALISATION (synthèse + QR vérif.)
═══════════════════════════════════════════ */

function updateBadgeExportHeaderFromEvent() {
  const ev = App.activeEvent;
  const titleEl = document.getElementById('badgeExportEventTitle');
  const metaEl = document.getElementById('badgeExportMeta');
  if (titleEl && ev && ev.name) titleEl.textContent = ev.name;
  if (metaEl) {
    const parts = [];
    if (ev && ev.location) parts.push(ev.location);
    if (ev && ev.start_at) {
      try {
        const d = new Date(ev.start_at);
        if (!Number.isNaN(d.getTime())) parts.push(`Début : ${d.toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}`);
      } catch (e) {
        /* ignore */
      }
    }
    metaEl.textContent = parts.filter(Boolean).join(' · ');
  }
}

async function fetchParticipantVerificationUrlIfNeeded() {
  if (App.retreatVerificationUrl) return App.retreatVerificationUrl;
  if (!App.participantId) return null;
  const base = typeof getRetraiteApiBase === 'function' ? getRetraiteApiBase() : '';
  if (!base) return null;
  try {
    const res = await fetch(`${base}/participants/${App.participantId}/status`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const json = res.ok ? await res.json().catch(() => ({})) : {};
    const d = json.data || {};
    const u = d.verification_url || d.access_url;
    if (u) {
      App.retreatVerificationUrl = u;
      if (d.download_token) App.retreatDownloadToken = d.download_token;
      if (d.billet_url) App.retreatBilletUrl = d.billet_url;
      return u;
    }
  } catch (e) {
    console.warn('URL accès inscription', e);
  }
  return null;
}

function renderBadgeQrCode(url) {
  const mount = document.getElementById('badgeQrMount');
  const linkLine = document.getElementById('badgeQrLinkLine');
  const esc = typeof escapeHtml === 'function' ? escapeHtml : (t => String(t));
  if (!mount || !linkLine || !url) {
    if (mount) mount.innerHTML = '';
    if (linkLine) linkLine.textContent = '';
    return;
  }

  linkLine.innerHTML = '';
  linkLine.innerHTML = `<small class="wrap-anywhere">${esc(url)}</small>`;

  mount.innerHTML = '';

  /* qrcode.js (davidshimjs) expose global QRCode */
  if (typeof QRCode !== 'function') {
    mount.innerHTML = `<span class="field-hint">QR&nbsp;: chargez la page puis réessayez.</span>`;
    return;
  }

  const correctLevel =
    QRCode.CorrectLevel && typeof QRCode.CorrectLevel.M !== 'undefined'
      ? QRCode.CorrectLevel.M
      : undefined;

  try {
    // eslint-disable-next-line new-cap
    const opts = {
      text: url,
      width: 132,
      height: 132,
      colorDark: '#1a1018',
      colorLight: '#ffffff',
    };
    if (correctLevel !== undefined) opts.correctLevel = correctLevel;
    new QRCode(mount, opts);
    const img = mount.querySelector('img');
    if (img) img.alt = 'Code QR inscription retraite';
  } catch (e) {
    mount.innerHTML = `<span class="field-hint">QR indisponible.</span>`;
  }
}

function renderBadgeRecapMirrored() {
  const c = document.getElementById('badgeRecapMirrored');
  if (!c || typeof buildRetraiteRecapSectionModels !== 'function') return;
  if (typeof renderRecapSectionsIntoContainer !== 'function') return;
  const sections = buildRetraiteRecapSectionModels();
  renderRecapSectionsIntoContainer(c, sections, { showEdit: false });
}

async function ensureBadgeQrRendered() {
  const url = await fetchParticipantVerificationUrlIfNeeded();
  renderBadgeQrCode(url);
  const billetWrap = document.getElementById('badgeBilletLinkWrap');
  const billetLink = document.getElementById('badgeBilletLink');
  if (billetWrap && billetLink && App.retreatBilletUrl) {
    billetLink.href = App.retreatBilletUrl;
    billetWrap.style.display = '';
  }
}

function fillBadgeFromForm() {
  updateBadgeExportHeaderFromEvent();
  renderBadgeRecapMirrored();
  mountBadgePortalNotifications();
}

/**
 * Notifications portail / OTP affichées en fin de parcours (étape badge), pas dans le bandeau d’accueil.
 */
function mountBadgePortalNotifications() {
  const wrap = document.getElementById('badgePortalNotifications');
  if (!wrap) return;

  const ev = App.activeEvent;
  const pn = ev && ev.participant_notifications;
  if (!pn || !Array.isArray(pn.lines) || !pn.lines.length) {
    wrap.classList.add('hidden');
    wrap.innerHTML = '';
    return;
  }

  wrap.classList.remove('hidden');
  const esc = typeof escapeHtml === 'function' ? escapeHtml : (t => String(t));
  const lines = pn.lines
    .map(line => `<p class="badge-portal-line">${esc(String(line))}</p>`)
    .join('');
  wrap.innerHTML = `<div class="badge-portal-inner"><strong>Portail & confirmations</strong>${lines}</div>`;
}

async function assertParticipantPaymentVerifiedForView(view) {
  const allowedByView = {
    electronic_success: ['mobile_money', 'card'],
    sponsorship_success: ['sponsorship_voucher'],
  };
  const allowedChannels = allowedByView[view];
  if (!allowedChannels || !allowedChannels.length) {
    return false;
  }

  if (!App.participantId) {
    return false;
  }
  const base = typeof getRetraiteApiBase === 'function' ? getRetraiteApiBase() : '';
  if (!base) {
    return false;
  }
  try {
    const res = await fetch(`${base}/participants/${App.participantId}/status`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const json = res.ok ? await res.json().catch(() => ({})) : {};
    const d = json.data || {};
    const ch = d.payment && d.payment.channel;
    return (
      d.paiement_valide === true &&
      d.payment &&
      d.payment.etat === 'payee' &&
      allowedChannels.includes(ch)
    );
  } catch (e) {
    console.warn('Statut paiement badge', e);
    return false;
  }
}

function getBadgeJsPdfConstructor() {
  const g = typeof window !== 'undefined' ? window : {};
  const pack = g.jspdf;
  if (pack) {
    if (typeof pack.jsPDF === 'function') return pack.jsPDF;
    if (pack.default && typeof pack.default.jsPDF === 'function') return pack.default.jsPDF;
    if (typeof pack.default === 'function') return pack.default;
  }
  if (typeof g.jsPDF === 'function') return g.jsPDF;
  return null;
}

/**
 * @param {'electronic_success'|'sponsorship_success'|'cash_pending'} view
 */
async function finalizeBadgeUi(view) {
  if (typeof showBilletCreationLoader === 'function') {
    showBilletCreationLoader('Finalisation de votre inscription…');
  }

  try {
    if (view === 'electronic_success' || view === 'sponsorship_success') {
      const ok = await assertParticipantPaymentVerifiedForView(view);
      if (!ok) {
        if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES) {
          trackRetraiteFunnel(
            RETRAITE_FUNNEL_STAGES.payment_server_verify_failed,
            'Le serveur n’a pas confirmé le paiement avant l’affichage du billet.',
            { channel: App.paymentModeCompleted || view }
          );
        }
        retraiteNotifyToast(
          'Le paiement n’est pas confirmé côté serveur. Le suivi reste visible — réessayez ou contactez l’organisation.',
          'warning'
        );
        if (typeof goToStep === 'function') {
          goToStep(4);
        }
        return;
      }
    }

    if (typeof showBilletCreationLoader === 'function') {
      showBilletCreationLoader('Génération de votre billet…');
    }

    fillBadgeFromForm();
    await ensureBadgeQrRendered();

    const bannerOk = document.getElementById('badgeElectronicBanner');
    const bannerCash = document.getElementById('badgeCashPendingBanner');
    const bannerGen = document.getElementById('badgeGenericBanner');

    if (bannerOk) bannerOk.classList.add('hidden');
    if (bannerCash) bannerCash.classList.add('hidden');
    if (bannerGen) bannerGen.classList.remove('hidden');

    const titleEl = document.getElementById('badgeMainTitle');
    const subEl = document.getElementById('badgeMainSubtitle');

    if (view === 'electronic_success') {
      if (bannerOk) bannerOk.classList.remove('hidden');
      if (bannerGen) bannerGen.classList.add('hidden');
      if (titleEl) titleEl.textContent = 'Paiement validé';
      if (subEl) subEl.textContent = 'Votre paiement électronique est confirmé. Les détails officiels vous sont envoyés par e-mail.';
    } else if (view === 'sponsorship_success') {
      if (bannerGen) bannerGen.classList.remove('hidden');
      if (titleEl) titleEl.textContent = 'Inscription prise en charge';
      if (subEl) {
        subEl.textContent =
          'Votre code parrainage couvre les frais d’inscription. Les détails officiels vous sont envoyés par e-mail.';
      }
      const genericText = document.getElementById('badgeGenericBannerText');
      if (genericText) {
        genericText.textContent =
          'Inscription confirmée grâce au parrainage. Conservez un exemplaire PNG ou PDF de cette synthèse (QR vérifiable) jusqu’à l’accueil sur place.';
      }
    } else if (view === 'cash_pending') {
      if (bannerCash) bannerCash.classList.remove('hidden');
      if (titleEl) titleEl.textContent = 'Preuve bien reçue';
      if (subEl) {
        subEl.textContent =
          'Après validation du paiement par l’équipe, vous recevrez un e-mail de confirmation avec votre billet et les détails officiels.';
      }
    }

    if (typeof trackRetraiteFunnel === 'function' && window.RETRAITE_FUNNEL_STAGES) {
      trackRetraiteFunnel(RETRAITE_FUNNEL_STAGES.badge_reached, 'Billet affiché.', null);
    }
    goToStep(5);
    saveState();
    if (typeof resetRetraiteUrlParams === 'function') resetRetraiteUrlParams();
  } finally {
    if (typeof hideBilletCreationLoader === 'function') hideBilletCreationLoader();
  }
}

async function captureBadgeExportCompositeCanvas() {
  const root =
    document.getElementById('badgeExportComposite') || document.querySelector('.badge-export-sheet');
  if (!root) return null;

  await ensureBadgeQrRendered();

  if (typeof html2canvas === 'undefined') {
    retraiteNotifyToast('Module d’export non chargé : actualisez la page.', 'warning');
    return null;
  }

  await new Promise(resolve => requestAnimationFrame(() => resolve()));

  const canvas = await html2canvas(root, {
    scale: 3,
    backgroundColor: '#faf8f9',
    useCORS: true,
    logging: false,
    scrollX: 0,
    scrollY: -window.scrollY,
  });
  return canvas;
}

window.downloadBadgePngComposite = async function downloadBadgePngComposite() {
  const btn = document.getElementById('downloadBadgeBtn');
  if (!btn) return;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> PNG…';
  btn.disabled = true;

  try {
    const canvas = await captureBadgeExportCompositeCanvas();
    if (!canvas) return;
    const link = document.createElement('a');
    const name = `${val('prenom')}_${val('nom')}`.replace(/\s+/g, '_') || 'inscription';
    link.download = `retraite_synthese_${name}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
  } catch (err) {
    retraiteNotifyToast(
      'Erreur lors de la génération du PNG. Faites une capture d’écran de cette page.',
      'warning'
    );
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
  }
};

window.downloadBadgePdfComposite = async function downloadBadgePdfComposite() {
  const btn = document.getElementById('downloadBadgePdfBtn');
  if (!btn) return;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> PDF…';
  btn.disabled = true;

  try {
    const JsPdf = getBadgeJsPdfConstructor();
    if (!JsPdf) {
      retraiteNotifyToast(
        'Le module PDF n’est pas disponible (script non chargé). Utilisez l’export PNG ou actualisez la page.',
        'warning'
      );
      return;
    }
    const canvas = await captureBadgeExportCompositeCanvas();
    if (!canvas) return;

    const pdf = new JsPdf({ orientation: 'p', unit: 'mm', format: 'a4' });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();
    const margin = 10;
    const imgData = canvas.toDataURL('image/png', 1.0);
    const imgW = pageW - margin * 2;
    const imgH = (canvas.height * imgW) / canvas.width;
    let y = margin;
    if (imgH + margin * 2 > pageH) {
      const scale = (pageH - margin * 2) / imgH;
      const w2 = imgW * scale;
      const h2 = imgH * scale;
      pdf.addImage(imgData, 'PNG', margin + (imgW - w2) / 2, y, w2, h2);
    } else {
      pdf.addImage(imgData, 'PNG', margin, y, imgW, imgH);
    }
    const name = `${val('prenom')}_${val('nom')}`.replace(/\s+/g, '_') || 'inscription';
    pdf.save(`retraite_synthese_${name}.pdf`);
  } catch (err) {
    retraiteNotifyToast('Erreur PDF. Essayez l’export PNG.', 'warning');
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
  }
};

/* ═══════════════════════════════════════════
   VALIDATION TÉL. EN TEMPS RÉEL + INDICATION FOYER (API)
═══════════════════════════════════════════ */
'use strict';

function normalizeMainPhoneCanon(indicatif, telephone) {
  const ind = String(indicatif || '')
    .trim()
    .replace(/^\+/, '')
    .replace(/\D/g, '');
  const num = String(telephone || '').replace(/\D/g, '');
  return ind + num;
}

function canonicalEmergencyDigitsClient(raw, indicatifPrincipal) {
  const t = String(raw || '').trim();
  if (!t) return null;
  const compact = t.replace(/\s+/g, '');
  if (compact.startsWith('+')) {
    return compact.replace(/\D/g, '');
  }
  return normalizeMainPhoneCanon(indicatifPrincipal, compact);
}

function digitsLookLikeE164(canon) {
  return canon && /^[0-9]{10,15}$/.test(canon);
}

function normalizeGuardianNameKeyClient(raw) {
  const t = String(raw || '').trim();
  if (t.length < 3) return null;
  const n = t.replace(/\s+/g, ' ').toLowerCase();
  return n.length >= 3 ? n : null;
}

let tutorHintTimer = null;
let tutorAbort = null;
let mainPhoneHintTimer = null;
let mainPhoneAbort = null;
let emailHintTimer = null;
let emailAbort = null;

/** @param {string} raw */
function retraiteLiveEmailLooksValid(raw) {
  const t = (raw || '').trim();
  if (!t) return false;
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);
}

function setTutorSameFamilyAckVisible(show) {
  const row = document.getElementById('tutorSameFamilyField');
  const chk = document.getElementById('tutorSameFamilyCheck');
  const ex = document.getElementById('tutorSameFamilyExplain');
  if (!row) return;
  if (show) {
    row.classList.remove('hidden');
  } else {
    row.classList.add('hidden');
    if (chk) chk.checked = false;
    if (ex) ex.innerHTML = '';
  }
}

function renderTutorSameFamilyExplain(d) {
  const box = document.getElementById('tutorSameFamilyExplain');
  if (!box) return;
  if (!d || !d.matches_registered_participant) {
    box.innerHTML = '';
    return;
  }
  const esc = typeof escapeHtml === 'function' ? escapeHtml : (t => String(t));
  const items = [];
  if (d.tutor_matches_registered_participant && d.hint_tutor) items.push(d.hint_tutor);
  if (d.guardian_matches_registered_participant && d.hint_guardian) items.push(d.hint_guardian);
  if (d.guardian_phone_matches_registered_guardian && d.hint_guardian_dup_phone) {
    items.push(d.hint_guardian_dup_phone);
  }
  if (d.guardian_name_matches_registered_guardian_name && d.hint_guardian_dup_name) {
    items.push(d.hint_guardian_dup_name);
  }
  const masked = Array.isArray(d.masked_matches) ? d.masked_matches : [];
  if (!items.length && !masked.length) {
    box.innerHTML = '';
    return;
  }
  const lis = items.map(t => `<li>${esc(String(t))}</li>`).join('');

  let matchList = '';
  if (masked.length) {
    const max = 15;
    const sub = masked.slice(0, max)
      .map(m => {
        const label = esc(String(m.masked_label || ''));
        const motives = Array.isArray(m.motives) ? m.motives : [];
        const motiveStr = motives.map(x => esc(String(x))).join(' · ');
        return `<li><strong>${label}</strong>${motiveStr ? ` <span class="tutor-mask-motives">— ${motiveStr}</span>` : ''}</li>`;
      })
      .join('');
    const more = masked.length > max
      ? `<p class="field-hint">${esc(`… et ${masked.length - max} autre(s) correspondance(s) non affichée(s).`)}</p>`
      : '';
    matchList = `
    <div class="tutor-masked-matches">
      <p><strong>Inscriptions déjà enregistrées avec les mêmes repères</strong> (prénom + initiales, à titre d’aide — ne partagez ces infos qu’avec l’organisation si besoin) :</p>
      <ul class="tutor-masked-list">${sub}</ul>
      ${more}
    </div>`;
  }

  const motivesBlock = items.length
    ? `<p><strong>Un ou plusieurs motifs</strong> suggèrent un lien avec une inscription déjà enregistrée :</p>
    <ul>${lis}</ul>`
    : `<p><strong>Correspondances</strong> avec d’autres fiches sur cet événement :</p>`;

  box.innerHTML = `
    ${motivesBlock}
    ${matchList}
    <p class="tutor-same-family-hint-next">À lire attentivement puis <strong>cochez la case sous ce bloc</strong> seulement si vous souhaitez vous identifier comme faisant partie du <strong>même foyer</strong> et consentir au regroupement administratif.</p>`;
}

function setLiveHint(elId, html, variant) {
  const el = document.getElementById(elId);
  if (!el) return;
  if (!html) {
    el.innerHTML = '';
    el.classList.add('hidden');
    el.dataset.variant = '';
    return;
  }
  el.innerHTML = html;
  el.classList.remove('hidden');
  el.dataset.variant = variant || '';
  el.style.color =
    variant === 'danger'
      ? 'var(--color-danger-strong, #b71c1c)'
      : variant === 'warn'
        ? 'var(--color-warn-strong, #a86b00)'
        : variant === 'ok'
          ? 'var(--color-success-strong, #1b5e20)'
          : variant === 'info'
            ? '#1565c0'
            : '';
}

function refreshMainTelephoneHints() {
  const indicatifEl = document.getElementById('indicatif');
  const telInput = document.getElementById('telephone');
  if (!telInput) return null;

  const indicatif = indicatifEl ? indicatifEl.value : '+243';
  const canon = normalizeMainPhoneCanon(indicatif, telInput.value);

  let okVisual = false;
  if ((telInput.value || '').trim() === '') {
    setLiveHint('telephoneLiveFeedback', '');
    App.mainPhoneDuplicateRegistered = false;
  } else if (!digitsLookLikeE164(canon)) {
    setLiveHint(
      'telephoneLiveFeedback',
      '<i class="bi bi-exclamation-circle"></i> Saisissez un numéro complet (avec indicatif si besoin dans le champ d’à côté).',
      'warn'
    );
  } else {
    okVisual = true;
    setLiveHint(
      'telephoneLiveFeedback',
      '<i class="bi bi-check2-circle"></i> Format du numéro principal accepté.',
      'ok'
    );
  }

  return { canon, okVisual };
}

function scheduleMainPhoneDuplicateFetch() {
  clearTimeout(mainPhoneHintTimer);
  const indicatifEl = document.getElementById('indicatif');
  const telInput = document.getElementById('telephone');
  if (!telInput) return;

  const indicatif = indicatifEl ? indicatifEl.value : '+243';

  mainPhoneHintTimer = setTimeout(async () => {
    const curCanon = normalizeMainPhoneCanon(indicatif, telInput.value);
    if (!digitsLookLikeE164(curCanon)) {
      App.mainPhoneDuplicateRegistered = false;
      return;
    }

    const base = typeof getRetraiteApiBase === 'function' ? getRetraiteApiBase() : '';
    if (!base) return;

    if (mainPhoneAbort) mainPhoneAbort.abort();
    mainPhoneAbort = new AbortController();

    const qs = new URLSearchParams({
      telephone: telInput.value.trim(),
      indicatif,
    });
    const ev = App.activeEvent;
    if (ev && ev.id) qs.set('event_id', String(ev.id));

    try {
      const res = await fetch(`${base}/hints/main-phone?${qs.toString()}`, {
        headers: { Accept: 'application/json' },
        signal: mainPhoneAbort.signal,
      });
      const json = res.ok ? await res.json() : null;
      const d = json && json.data;

      App.mainPhoneDuplicateRegistered = !!(d && d.duplicate_registered);

      const refreshedCanon = normalizeMainPhoneCanon(indicatif, telInput.value);
      if (!digitsLookLikeE164(refreshedCanon)) {
        App.mainPhoneDuplicateRegistered = false;
        return;
      }

      if (App.mainPhoneDuplicateRegistered) {
        const hintTxt =
          (d && d.hint) ||
          'Ce numéro est déjà enregistré pour un autre participant sur cette retraite.';
        setLiveHint('telephoneLiveFeedback', `<i class="bi bi-telephone-x"></i> ${hintTxt}`, 'danger');
      } else {
        setLiveHint(
          'telephoneLiveFeedback',
          '<i class="bi bi-check2-circle"></i> Numéro principal accepté pour cette ligne (doublon vérifié).',
          'ok'
        );
      }
    } catch (e) {
      if (e.name === 'AbortError') return;
      App.mainPhoneDuplicateRegistered = false;
    }
  }, 480);
}

const EMAIL_HINT_DEBOUNCE_MS = 220;

function refreshEmailLiveFeedback() {
  const emailEl = document.getElementById('email');
  if (!emailEl) return;
  const raw = (emailEl.value || '').trim();
  if (!raw) {
    setLiveHint('emailLiveFeedback', '');
    App.emailDuplicateRegistered = false;
    return;
  }
  if (!retraiteLiveEmailLooksValid(raw)) {
    setLiveHint(
      'emailLiveFeedback',
      '<i class="bi bi-envelope-exclamation"></i> Saisissez une adresse e-mail valide (ex.&nbsp;nom@domaine.com).',
      'warn'
    );
    App.emailDuplicateRegistered = false;
    return;
  }
  setLiveHint(
    'emailLiveFeedback',
    '<i class="bi bi-arrow-repeat spin"></i> Vérification du doublon sur cette retraite…',
    'info'
  );
}

async function fetchEmailDuplicateStatus(emailEl) {
  const cur = (emailEl.value || '').trim();
  if (!retraiteLiveEmailLooksValid(cur)) {
    App.emailDuplicateRegistered = false;
    refreshEmailLiveFeedback();
    return;
  }

  const base = typeof getRetraiteApiBase === 'function' ? getRetraiteApiBase() : '';
  if (!base) return;

  if (emailAbort) emailAbort.abort();
  emailAbort = new AbortController();

  const qs = new URLSearchParams({ email: cur });
  const ev = App.activeEvent;
  if (ev && ev.id) qs.set('event_id', String(ev.id));

  try {
    const res = await fetch(`${base}/hints/email?${qs.toString()}`, {
      headers: { Accept: 'application/json' },
      signal: emailAbort.signal,
    });
    const json = res.ok ? await res.json() : null;
    const d = json && json.data;

    App.emailDuplicateRegistered = !!(d && d.duplicate_registered);

    const refreshed = (emailEl.value || '').trim();
    if (!retraiteLiveEmailLooksValid(refreshed)) {
      App.emailDuplicateRegistered = false;
      refreshEmailLiveFeedback();
      return;
    }

    if (App.emailDuplicateRegistered) {
      const hintTxt =
        (d && d.hint) ||
        'Cette adresse e-mail est déjà utilisée pour une autre inscription à cette retraite.';
      setLiveHint('emailLiveFeedback', `<i class="bi bi-envelope-x"></i> ${hintTxt}`, 'danger');
    } else {
      setLiveHint(
        'emailLiveFeedback',
        '<i class="bi bi-check2-circle"></i> Adresse disponible pour cette retraite (doublon vérifié).',
        'ok'
      );
    }
  } catch (e) {
    if (e.name === 'AbortError') return;
    App.emailDuplicateRegistered = false;
    const refreshed = (emailEl.value || '').trim();
    if (retraiteLiveEmailLooksValid(refreshed)) {
      setLiveHint(
        'emailLiveFeedback',
        '<i class="bi bi-wifi-off"></i> Impossible de vérifier le doublon pour l’instant. Réessayez ou continuez puis actualisez.',
        'warn'
      );
    }
  }
}

function scheduleEmailDuplicateFetch() {
  clearTimeout(emailHintTimer);
  const emailEl = document.getElementById('email');
  if (!emailEl) return;

  emailHintTimer = setTimeout(() => {
    void fetchEmailDuplicateStatus(emailEl);
  }, EMAIL_HINT_DEBOUNCE_MS);
}

/** Au blur : vérification immédiate (sans attendre le debounce). */
function flushEmailDuplicateFetch() {
  clearTimeout(emailHintTimer);
  const emailEl = document.getElementById('email');
  if (!emailEl) return;
  void fetchEmailDuplicateStatus(emailEl);
}

function scheduleTutorFamilyHintFetch() {
  const indicatifEl = document.getElementById('indicatif');
  const telUrgEl = document.getElementById('telUrgence');
  const guardianEl = document.getElementById('guardianPhone');
  const guardianNameEl = document.getElementById('guardianName');

  const rawTutor = telUrgEl && telUrgEl.value.trim();
  const rawGuardian = guardianEl && guardianEl.value.trim();
  const rawGuardianName = guardianNameEl && guardianNameEl.value.trim();
  const nameKeyClient = normalizeGuardianNameKeyClient(rawGuardianName);

  if (!rawTutor && !rawGuardian && !nameKeyClient) {
    setLiveHint('telUrgenceLiveFeedback', '');
    setLiveHint('guardianPhoneLiveFeedback', '');
    setLiveHint('guardianNameLiveFeedback', '');
    setTutorSameFamilyAckVisible(false);
    renderTutorSameFamilyExplain(null);
    return;
  }

  clearTimeout(tutorHintTimer);

  tutorHintTimer = setTimeout(async () => {
    const tutorNow = telUrgEl ? telUrgEl.value.trim() : '';
    const guardianNow = guardianEl ? guardianEl.value.trim() : '';
    const nameNowRaw = guardianNameEl ? guardianNameEl.value.trim() : '';
    const nameKeyNow = normalizeGuardianNameKeyClient(nameNowRaw);

    if (!tutorNow && !guardianNow && !nameKeyNow) {
      setLiveHint('telUrgenceLiveFeedback', '');
      setLiveHint('guardianPhoneLiveFeedback', '');
      setLiveHint('guardianNameLiveFeedback', '');
      setTutorSameFamilyAckVisible(false);
      renderTutorSameFamilyExplain(null);
      return;
    }

    const indicatif = indicatifEl ? indicatifEl.value : '+243';
    const mainInfo = refreshMainTelephoneHints();
    const mainCanon = mainInfo?.canon;

    const tutorCanon = tutorNow ? canonicalEmergencyDigitsClient(tutorNow, indicatif) : null;
    const guardianCanon = guardianNow ? canonicalEmergencyDigitsClient(guardianNow, indicatif) : null;

    const tutorDigitsOk = !!(tutorCanon && digitsLookLikeE164(tutorCanon));
    const guardianDigitsOk = !!(guardianCanon && digitsLookLikeE164(guardianCanon));

    if (mainCanon && tutorDigitsOk && tutorCanon === mainCanon) {
      setLiveHint(
        'telUrgenceLiveFeedback',
        '<i class="bi bi-exclamation-triangle-fill"></i> Le tél. d’urgence ne doit pas être identique au numéro principal.',
        'danger'
      );
      setLiveHint('guardianPhoneLiveFeedback', '');
      setLiveHint('guardianNameLiveFeedback', '');
      setTutorSameFamilyAckVisible(false);
      renderTutorSameFamilyExplain(null);
      return;
    }

    if (mainCanon && guardianDigitsOk && guardianCanon === mainCanon) {
      setLiveHint(
        'guardianPhoneLiveFeedback',
        '<i class="bi bi-exclamation-triangle-fill"></i> Le téléphone du tuteur ne doit pas être identique au vôtre (portable principal).',
        'danger'
      );
      if (!tutorNow) setLiveHint('telUrgenceLiveFeedback', '');
      setLiveHint('guardianNameLiveFeedback', '');
      setTutorSameFamilyAckVisible(false);
      renderTutorSameFamilyExplain(null);
      return;
    }

    if (tutorNow && !tutorDigitsOk) {
      const partial = (telUrgEl.value || '').replace(/\D/g, '').length;
      if (partial >= 4) {
        setLiveHint(
          'telUrgenceLiveFeedback',
          '<i class="bi bi-telephone"></i> Complétez le numéro d’urgence (indication internationale avec + ou indicatif commun).',
          'warn'
        );
      } else {
        setLiveHint('telUrgenceLiveFeedback', '');
      }
    }

    if (guardianNow && !guardianDigitsOk) {
      const partial = (guardianEl.value || '').replace(/\D/g, '').length;
      if (partial >= 4) {
        setLiveHint(
          'guardianPhoneLiveFeedback',
          '<i class="bi bi-telephone"></i> Complétez le téléphone du tuteur.',
          'warn'
        );
      } else {
        setLiveHint('guardianPhoneLiveFeedback', '');
      }
    }

    if (tutorDigitsOk && guardianDigitsOk && tutorCanon === guardianCanon) {
      setLiveHint(
        'guardianPhoneLiveFeedback',
        '<i class="bi bi-exclamation-triangle-fill"></i> Utilisez un numéro différent du téléphone d’urgence pour le tuteur.',
        'danger'
      );
      setLiveHint('guardianNameLiveFeedback', '');
      setTutorSameFamilyAckVisible(false);
      renderTutorSameFamilyExplain(null);
      return;
    }

    const nameOkForHints = !!nameKeyNow;
    /** Numéro partiel : au moins 6 chiffres pour une recherche utile côté serveur */
    const guardianPartialDigits = guardianNow
      ? String(guardianNow).replace(/\D/g, '')
      : '';
    const tutorPartialDigits = tutorNow ? String(tutorNow).replace(/\D/g, '') : '';
    const guardianPartialOk = guardianPartialDigits.length >= 6 && guardianPartialDigits.length < 12;
    const tutorPartialOk = tutorPartialDigits.length >= 6 && tutorPartialDigits.length < 12;

    const canFetch =
      tutorDigitsOk ||
      guardianDigitsOk ||
      nameOkForHints ||
      guardianPartialOk ||
      tutorPartialOk;

    if (!canFetch) {
      setLiveHint('guardianNameLiveFeedback', '');
      setTutorSameFamilyAckVisible(false);
      renderTutorSameFamilyExplain(null);
      return;
    }

    const base = typeof getRetraiteApiBase === 'function' ? getRetraiteApiBase() : '';
    if (!base) return;

    if (tutorAbort) tutorAbort.abort();
    tutorAbort = new AbortController();

    const qs = new URLSearchParams({ indicatif });
    if (tutorNow) qs.set('tel_urgence', tutorNow);
    if (guardianNow) qs.set('guardian_phone', guardianNow);
    if (nameNowRaw) qs.set('guardian_name', nameNowRaw);
    const ev = App.activeEvent;
    if (ev && ev.id) qs.set('event_id', String(ev.id));

    try {
      setLiveHint(
        'guardianNameLiveFeedback',
        '<i class="bi bi-arrow-repeat spin"></i> Vérification des liens avec d’autres inscriptions…',
        'info'
      );

      const res = await fetch(`${base}/hints/tutor-family?${qs.toString()}`, {
        headers: { Accept: 'application/json' },
        signal: tutorAbort.signal,
      });
      const json = res.ok ? await res.json() : null;
      const d = json && json.data;

      if (!d || !d.eligible) {
        if (tutorDigitsOk && tutorNow) setLiveHint('telUrgenceLiveFeedback', '');
        if (guardianDigitsOk && guardianNow) setLiveHint('guardianPhoneLiveFeedback', '');
        if (nameOkForHints) setLiveHint('guardianNameLiveFeedback', '');
        setTutorSameFamilyAckVisible(false);
        renderTutorSameFamilyExplain(null);
        return;
      }

      const tutorMatch = !!d.tutor_matches_registered_participant;
      const guardianMatchMain = !!d.guardian_matches_registered_participant;
      const guardianDupPhone = !!d.guardian_phone_matches_registered_guardian;
      const guardianDupName = !!d.guardian_name_matches_registered_guardian_name;
      const any = tutorMatch || guardianMatchMain || guardianDupPhone || guardianDupName;

      if (tutorMatch && d.hint_tutor) {
        setLiveHint('telUrgenceLiveFeedback', `<i class="bi bi-info-circle"></i> ${d.hint_tutor}`, 'info');
      } else if (tutorNow && tutorDigitsOk) {
        setLiveHint('telUrgenceLiveFeedback', '');
      }

      if (guardianMatchMain && d.hint_guardian) {
        setLiveHint('guardianPhoneLiveFeedback', `<i class="bi bi-info-circle"></i> ${d.hint_guardian}`, 'info');
      } else if (guardianDupPhone && d.hint_guardian_dup_phone) {
        setLiveHint('guardianPhoneLiveFeedback', `<i class="bi bi-info-circle"></i> ${d.hint_guardian_dup_phone}`, 'info');
      } else if (guardianNow && guardianDigitsOk) {
        setLiveHint('guardianPhoneLiveFeedback', '');
      }

      if (guardianDupName && d.hint_guardian_dup_name) {
        setLiveHint('guardianNameLiveFeedback', `<i class="bi bi-info-circle"></i> ${d.hint_guardian_dup_name}`, 'info');
      } else if (nameOkForHints) {
        setLiveHint('guardianNameLiveFeedback', '');
      }

      renderTutorSameFamilyExplain(any ? d : null);
      setTutorSameFamilyAckVisible(any);
    } catch (e) {
      if (e.name === 'AbortError') return;
      setLiveHint('telUrgenceLiveFeedback', '');
      setLiveHint('guardianPhoneLiveFeedback', '');
      setLiveHint('guardianNameLiveFeedback', '');
      setTutorSameFamilyAckVisible(false);
      renderTutorSameFamilyExplain(null);
    }
  }, 520);
}

function telephoneMarkInvalid(show, message) {
  const telInput = document.getElementById('telephone');
  if (!telInput) return;
  if (show) {
    showFieldError(
      telInput,
      message || 'Le numéro de téléphone est obligatoire ou invalide'
    );
  } else if (
    digitsLookLikeE164(
      normalizeMainPhoneCanon(
        document.getElementById('indicatif') ? document.getElementById('indicatif').value : '+243',
        telInput.value
      )
    )
  ) {
    markFieldValid(telInput);
  }
}

function isFamilyMultiChildMode() {
  const chk = document.getElementById('familyMultiChildCheck');
  return !!(chk && chk.checked);
}

function isRegFieldDomVisible(fieldKey) {
  const wrapper = document.querySelector(`[data-reg-field="${fieldKey}"]`);
  if (!wrapper) {
    return true;
  }
  return !wrapper.classList.contains('hidden');
}

/**
 * À appeler depuis validateStep lors des étapes identité / coordonnées.
 * @param {number|null} stepIndex Étape courante (0 = identité, 1 = coordonnées)
 * @returns {boolean}
 */
function validateContactStepPhones(stepIndex = null) {
  const indicatifEl = document.getElementById('indicatif');
  const telInput = document.getElementById('telephone');
  const telUrgEl = document.getElementById('telUrgence');
  const guardianEl = document.getElementById('guardianPhone');
  const emailEl = document.getElementById('email');
  const indicatif = indicatifEl ? indicatifEl.value : '+243';
  const familyMode = isFamilyMultiChildMode();
  const telVisible = isRegFieldDomVisible('telephone');
  const emailVisible = isRegFieldDomVisible('email');
  const validateStep0Contacts = stepIndex === null || stepIndex === 0;
  const validateStep1Contacts = stepIndex === null || stepIndex === 1;

  let valid = true;

  let telRequired = !!(telInput && telInput.hasAttribute('data-required') && telVisible);
  if (familyMode) {
    telRequired = false;
  }
  const emailRequired = !!(emailEl && emailEl.hasAttribute('data-required') && emailVisible);

  if (validateStep0Contacts && telInput && telVisible) {
    const telRaw = (telInput.value || '').trim();
    const mainCanon = normalizeMainPhoneCanon(indicatif, telInput.value);

    if (telRequired) {
      if (!telRaw) {
        telephoneMarkInvalid(true, 'Le numéro de téléphone est obligatoire');
        setLiveHint(
          'telephoneLiveFeedback',
          '<i class="bi bi-exclamation-circle"></i> Le numéro de téléphone est obligatoire.',
          'danger'
        );
        valid = false;
      } else if (!digitsLookLikeE164(mainCanon)) {
        telephoneMarkInvalid(true, 'Numéro de téléphone invalide');
        valid = false;
      } else {
        telephoneMarkInvalid(false);
      }
    } else if (telRaw && !digitsLookLikeE164(mainCanon)) {
      telephoneMarkInvalid(true);
      setLiveHint(
        'telephoneLiveFeedback',
        '<i class="bi bi-exclamation-circle"></i> Complétez ou corrigez le numéro principal, ou laissez le champ vide.',
        'danger'
      );
      valid = false;
    } else if (!telRaw) {
      telephoneMarkInvalid(false);
      setLiveHint('telephoneLiveFeedback', '');
    }

    if (telRequired && !familyMode && App.mainPhoneDuplicateRegistered) {
      setLiveHint(
        'telephoneLiveFeedback',
        '<i class="bi bi-telephone-x"></i> Ce numéro est déjà utilisé pour une autre inscription à cette retraite.',
        'danger'
      );
      telephoneMarkInvalid(true);
      valid = false;
    }
  }

  const emailRaw = emailEl ? (emailEl.value || '').trim() : '';
  if (validateStep0Contacts && emailEl && emailVisible) {
    if (emailRequired) {
      if (!emailRaw) {
        showFieldError(emailEl);
        setLiveHint(
          'emailLiveFeedback',
          '<i class="bi bi-exclamation-circle"></i> L’adresse e-mail est obligatoire.',
          'danger'
        );
        valid = false;
      } else if (!retraiteLiveEmailLooksValid(emailRaw)) {
        showFieldError(emailEl, 'Adresse e-mail invalide');
        valid = false;
      }
    }

    if (
      emailRaw &&
      retraiteLiveEmailLooksValid(emailRaw) &&
      !familyMode &&
      App.emailDuplicateRegistered
    ) {
      setLiveHint(
        'emailLiveFeedback',
        '<i class="bi bi-envelope-x"></i> Cette adresse e-mail est déjà utilisée pour une autre inscription à cette retraite.',
        'danger'
      );
      emailEl.classList.add('is-error');
      emailEl.classList.remove('is-valid');
      valid = false;
    } else if (emailRaw && retraiteLiveEmailLooksValid(emailRaw)) {
      markFieldValid(emailEl);
    }
  }

  if (!validateStep1Contacts || familyMode) {
    return valid;
  }

  const mainCanon = telInput
    ? normalizeMainPhoneCanon(indicatif, telInput.value)
    : '';

  const tutorCanon = telUrgEl
    ? canonicalEmergencyDigitsClient((telUrgEl.value || '').trim(), indicatif)
    : null;

  const guardianCanon = guardianEl
    ? canonicalEmergencyDigitsClient((guardianEl.value || '').trim(), indicatif)
    : null;

  if (
    tutorCanon &&
    digitsLookLikeE164(tutorCanon) &&
    mainCanon &&
    digitsLookLikeE164(mainCanon) &&
    tutorCanon === mainCanon
  ) {
    setTutorSameFamilyAckVisible(false);
    if (telUrgEl) {
      telUrgEl.classList.add('is-error');
      telUrgEl.classList.remove('is-valid');
    }
    setLiveHint(
      'telUrgenceLiveFeedback',
      '<i class="bi bi-exclamation-triangle-fill"></i> Utilisez un numéro de contact différent du vôtre pour l’urgence.',
      'danger'
    );
    valid = false;
  } else if (telUrgEl) {
    telUrgEl.classList.remove('is-error');
    if ((telUrgEl.value || '').trim()) {
      telUrgEl.classList.add('is-valid');
    } else {
      telUrgEl.classList.remove('is-valid');
    }
  }

  const rawGuardian = guardianEl && (guardianEl.value || '').trim();

  if (
    rawGuardian &&
    guardianCanon &&
    digitsLookLikeE164(guardianCanon) &&
    mainCanon &&
    digitsLookLikeE164(mainCanon) &&
    guardianCanon === mainCanon
  ) {
    guardianEl.classList.add('is-error');
    guardianEl.classList.remove('is-valid');
    setLiveHint(
      'guardianPhoneLiveFeedback',
      '<i class="bi bi-exclamation-triangle-fill"></i> Le téléphone du tuteur doit être différent du vôtre.',
      'danger'
    );
    valid = false;
  } else if (rawGuardian && (!guardianCanon || !digitsLookLikeE164(guardianCanon))) {
    guardianEl.classList.add('is-error');
    guardianEl.classList.remove('is-valid');
    valid = false;
  } else if (guardianEl) {
    guardianEl.classList.remove('is-error');
    if ((guardianEl.value || '').trim() && guardianCanon && digitsLookLikeE164(guardianCanon)) {
      guardianEl.classList.add('is-valid');
    } else {
      guardianEl.classList.remove('is-valid');
    }
  }

  if (
    tutorCanon &&
    digitsLookLikeE164(tutorCanon) &&
    guardianCanon &&
    digitsLookLikeE164(guardianCanon) &&
    tutorCanon === guardianCanon
  ) {
    if (guardianEl) {
      guardianEl.classList.add('is-error');
      guardianEl.classList.remove('is-valid');
    }
    setLiveHint(
      'guardianPhoneLiveFeedback',
      '<i class="bi bi-exclamation-triangle-fill"></i> Utilisez un numéro différent du téléphone d’urgence pour le tuteur.',
      'danger'
    );
    valid = false;
  }

  return valid;
}

function wirePhoneLiveValidation() {
  const indicatifEl = document.getElementById('indicatif');
  const telInput = document.getElementById('telephone');
  const telUrgEl = document.getElementById('telUrgence');
  const guardianEl = document.getElementById('guardianPhone');
  const guardianNameEl = document.getElementById('guardianName');
  const emailEl = document.getElementById('email');

  const onEmailChange = () => {
    refreshEmailLiveFeedback();
    scheduleEmailDuplicateFetch();
  };

  const onMainChange = () => {
    refreshMainTelephoneHints();
    scheduleMainPhoneDuplicateFetch();
    scheduleTutorFamilyHintFetch();
  };

  const flushTutorHints = () => scheduleTutorFamilyHintFetch();

  if (telInput) {
    telInput.addEventListener('input', onMainChange);
    if (indicatifEl) indicatifEl.addEventListener('change', onMainChange);
    if (telUrgEl) {
      telUrgEl.addEventListener('input', scheduleTutorFamilyHintFetch);
      telUrgEl.addEventListener('blur', flushTutorHints);
    }
    if (guardianEl) {
      guardianEl.addEventListener('input', scheduleTutorFamilyHintFetch);
      guardianEl.addEventListener('blur', flushTutorHints);
    }
    if (guardianNameEl) {
      guardianNameEl.addEventListener('input', scheduleTutorFamilyHintFetch);
      guardianNameEl.addEventListener('blur', flushTutorHints);
    }
    onMainChange();
  } else if (telUrgEl || guardianEl || guardianNameEl) {
    if (telUrgEl) {
      telUrgEl.addEventListener('input', scheduleTutorFamilyHintFetch);
      telUrgEl.addEventListener('blur', flushTutorHints);
    }
    if (guardianEl) {
      guardianEl.addEventListener('input', scheduleTutorFamilyHintFetch);
      guardianEl.addEventListener('blur', flushTutorHints);
    }
    if (guardianNameEl) {
      guardianNameEl.addEventListener('input', scheduleTutorFamilyHintFetch);
      guardianNameEl.addEventListener('blur', flushTutorHints);
    }
  }

  if (emailEl) {
    emailEl.addEventListener('input', onEmailChange);
    emailEl.addEventListener('paste', () => {
      queueMicrotask(onEmailChange);
    });
    emailEl.addEventListener('blur', () => {
      refreshEmailLiveFeedback();
      flushEmailDuplicateFetch();
    });
    onEmailChange();
  }
}

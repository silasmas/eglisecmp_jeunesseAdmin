/* ═══════════════════════════════════════════
   VALIDATION NOM / PRÉNOM EN TEMPS RÉEL
═══════════════════════════════════════════ */
'use strict';

let identityHintTimer = null;
let identityAbort = null;

/**
 * Vérifie le format d'un nom ou prénom (lettres, espaces, tirets, apostrophes).
 *
 * @param {string} raw Valeur saisie
 * @return {boolean}
 */
function retraiteNameLooksValid(raw) {
  const t = String(raw || '').trim();
  if (t.length < 2) {
    return false;
  }
  if (t.length > 100) {
    return false;
  }
  return /^[\p{L}\s'’-]+$/u.test(t);
}

/**
 * Met à jour le bandeau d'aide sous un champ identité.
 *
 * @param {string} elId Identifiant du paragraphe de feedback
 * @param {string} html Contenu HTML
 * @param {string} [variant] danger|ok|warn
 * @return {void}
 */
function setIdentityLiveHint(elId, html, variant) {
  if (typeof setLiveHint === 'function') {
    setLiveHint(elId, html, variant);
    return;
  }
  const el = document.getElementById(elId);
  if (!el) {
    return;
  }
  if (!html) {
    el.innerHTML = '';
    el.classList.add('hidden');
    return;
  }
  el.innerHTML = html;
  el.classList.remove('hidden');
}

/**
 * Valide un champ nom ou prénom (format + obligatoire).
 *
 * @param {HTMLElement|null} field Champ input
 * @param {string} emptyMessage Message si vide
 * @return {boolean}
 */
function validateNameField(field, emptyMessage) {
  if (!field) {
    return true;
  }

  const required =
    field.hasAttribute('data-required') ||
    (typeof isRegistrationFieldRequired === 'function' &&
      ((field.id === 'nom' && isRegistrationFieldRequired('nom')) ||
        (field.id === 'prenom' && isRegistrationFieldRequired('prenom'))));

  const value = (field.value || '').trim();

  if (!required && value === '') {
    if (typeof clearFieldError === 'function') {
      clearFieldError(field);
    }
    return true;
  }

  if (!value) {
    if (typeof showFieldError === 'function') {
      showFieldError(field, emptyMessage || 'Ce champ est requis');
    }
    return false;
  }

  if (!retraiteNameLooksValid(value)) {
    if (typeof showFieldError === 'function') {
      showFieldError(
        field,
        'Saisissez au moins 2 lettres (espaces, tirets et apostrophes autorisés).'
      );
    }
    return false;
  }

  if (typeof markFieldValid === 'function') {
    markFieldValid(field);
  }
  return true;
}

/**
 * Rafraîchit l'état visuel des champs nom/prénom sans appel API.
 *
 * @return {{ nomOk: boolean, prenomOk: boolean }}
 */
function refreshIdentityFieldsVisual() {
  const nomEl = document.getElementById('nom');
  const prenomEl = document.getElementById('prenom');
  let nomOk = true;
  let prenomOk = true;

  if (nomEl) {
    const raw = (nomEl.value || '').trim();
    if (raw === '') {
      setIdentityLiveHint('nomLiveFeedback', '');
    } else if (!retraiteNameLooksValid(raw)) {
      nomOk = false;
      setIdentityLiveHint(
        'nomLiveFeedback',
        '<i class="bi bi-exclamation-circle"></i> Nom trop court ou caractères non autorisés.',
        'warn'
      );
    } else {
      setIdentityLiveHint(
        'nomLiveFeedback',
        '<i class="bi bi-check2-circle"></i> Format du nom accepté.',
        'ok'
      );
    }
  }

  if (prenomEl) {
    const raw = (prenomEl.value || '').trim();
    if (raw === '') {
      setIdentityLiveHint('prenomLiveFeedback', '');
    } else if (!retraiteNameLooksValid(raw)) {
      prenomOk = false;
      setIdentityLiveHint(
        'prenomLiveFeedback',
        '<i class="bi bi-exclamation-circle"></i> Prénom trop court ou caractères non autorisés.',
        'warn'
      );
    } else {
      setIdentityLiveHint(
        'prenomLiveFeedback',
        '<i class="bi bi-check2-circle"></i> Format du prénom accepté.',
        'ok'
      );
    }
  }

  return { nomOk, prenomOk };
}

/**
 * Interroge l'API pour détecter un doublon nom + prénom sur l'événement actif.
 *
 * @return {Promise<void>}
 */
async function flushIdentityDuplicateFetch() {
  const nomEl = document.getElementById('nom');
  const prenomEl = document.getElementById('prenom');
  if (!nomEl || !prenomEl) {
    return;
  }

  const nom = (nomEl.value || '').trim();
  const prenom = (prenomEl.value || '').trim();

  if (!retraiteNameLooksValid(nom) || !retraiteNameLooksValid(prenom)) {
    App.identityDuplicateRegistered = false;
    return;
  }

  const base = typeof getRetraiteApiBase === 'function' ? getRetraiteApiBase() : '';
  if (!base) {
    return;
  }

  const qs = new URLSearchParams({ nom, prenom });
  if (App.activeEvent && App.activeEvent.id) {
    qs.set('event_id', String(App.activeEvent.id));
  }

  if (identityAbort) {
    identityAbort.abort();
  }
  identityAbort = new AbortController();

  try {
    const res = await fetch(`${base}/hints/participant-identity?${qs.toString()}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      signal: identityAbort.signal,
    });
    const json = await res.json().catch(() => ({}));
    const d = json.data || {};
    App.identityDuplicateRegistered = !!(d.eligible && d.duplicate_registered);

    if (App.identityDuplicateRegistered) {
      const hint =
        d.hint ||
        'Une inscription avec ce nom et ce prénom existe déjà pour cette retraite.';
      setIdentityLiveHint(
        'nomLiveFeedback',
        `<i class="bi bi-person-x"></i> ${typeof escapeHtml === 'function' ? escapeHtml(hint) : hint}`,
        'danger'
      );
      setIdentityLiveHint('prenomLiveFeedback', '');
      nomEl.classList.add('is-error');
      nomEl.classList.remove('is-valid');
      prenomEl.classList.add('is-error');
      prenomEl.classList.remove('is-valid');
      if (typeof showFieldError === 'function') {
        showFieldError(nomEl, 'Cette identité est déjà inscrite à la retraite');
        showFieldError(prenomEl, 'Vérifiez l’orthographe ou contactez l’organisation');
      }
    } else if (d.eligible) {
      App.identityDuplicateRegistered = false;
      refreshIdentityFieldsVisual();
      if (typeof validateNameField === 'function') {
        validateNameField(nomEl, 'Le nom est requis');
        validateNameField(prenomEl, 'Le prénom est requis');
      }
    }
  } catch (err) {
    if (err && err.name === 'AbortError') {
      return;
    }
    App.identityDuplicateRegistered = false;
  }
}

/**
 * Planifie la vérification doublon après une pause de frappe.
 *
 * @return {void}
 */
function scheduleIdentityDuplicateFetch() {
  clearTimeout(identityHintTimer);
  identityHintTimer = setTimeout(() => {
    flushIdentityDuplicateFetch();
  }, 450);
}

/**
 * Valide nom + prénom pour le passage d'étape (format + doublon API).
 *
 * @return {boolean}
 */
function validateIdentityStepFields() {
  const nomEl = document.getElementById('nom');
  const prenomEl = document.getElementById('prenom');
  const nomVisible =
    !App.formFields ||
    !App.formFields.nom ||
    App.formFields.nom.is_visible !== false;
  const prenomVisible =
    !App.formFields ||
    !App.formFields.prenom ||
    App.formFields.prenom.is_visible !== false;

  if (!nomVisible && !prenomVisible) {
    return true;
  }

  let valid = true;

  if (nomVisible && !validateNameField(nomEl, 'Le nom est requis')) {
    valid = false;
  }
  if (prenomVisible && !validateNameField(prenomEl, 'Le prénom est requis')) {
    valid = false;
  }

  const visual = refreshIdentityFieldsVisual();
  if (!visual.nomOk || !visual.prenomOk) {
    valid = false;
  }

  if (App.identityDuplicateRegistered) {
    valid = false;
    if (nomEl) {
      nomEl.classList.add('is-error');
    }
    if (prenomEl) {
      prenomEl.classList.add('is-error');
    }
  }

  return valid;
}

/**
 * Branche la validation instantanée sur nom et prénom (étape Identité).
 *
 * @return {void}
 */
function wireIdentityLiveValidation() {
  const nomEl = document.getElementById('nom');
  const prenomEl = document.getElementById('prenom');
  if (!nomEl && !prenomEl) {
    return;
  }

  App.identityDuplicateRegistered = false;

  const onChange = () => {
    refreshIdentityFieldsVisual();
    scheduleIdentityDuplicateFetch();
  };

  const onBlur = () => {
    if (nomEl) {
      validateNameField(nomEl, 'Le nom est requis');
    }
    if (prenomEl) {
      validateNameField(prenomEl, 'Le prénom est requis');
    }
    flushIdentityDuplicateFetch();
  };

  if (nomEl) {
    nomEl.addEventListener('input', onChange);
    nomEl.addEventListener('blur', onBlur);
  }
  if (prenomEl) {
    prenomEl.addEventListener('input', onChange);
    prenomEl.addEventListener('blur', onBlur);
  }

  onChange();
}

window.validateIdentityStepFields = validateIdentityStepFields;
window.wireIdentityLiveValidation = wireIdentityLiveValidation;
window.validateNameField = validateNameField;

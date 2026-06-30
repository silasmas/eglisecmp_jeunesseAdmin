/* ═══════════════════════════════════════════
   FORM VALIDATION
═══════════════════════════════════════════ */

/** Correspondance clé API → id DOM pour les champs texte/select. */
const REGISTRATION_FIELD_INPUT_IDS = {
  nom: 'nom',
  prenom: 'prenom',
  sexe: 'sexe',
  date_naissance: 'dateNaissance',
  tel_urgence: 'telUrgence',
  guardian_name: 'guardianName',
  guardian_phone: 'guardianPhone',
  adresse: 'adresse',
  commune: 'commune',
  ville: 'ville',
  eglise: 'eglise',
  departement: 'departement',
};

/**
 * Libellé lisible d'un champ pour les messages de validation.
 *
 * @param {HTMLElement} field Champ DOM
 * @return {string|null}
 */
function getFieldValidationLabel(field) {
  if (!field) {
    return null;
  }

  const wrapper = field.closest('[data-reg-field]');
  const regKey = wrapper ? wrapper.getAttribute('data-reg-field') : null;

  if (regKey && App.formFields && App.formFields[regKey] && App.formFields[regKey].label) {
    return App.formFields[regKey].label;
  }

  const labelEl = wrapper
    ? wrapper.querySelector('[data-reg-label], .field-label')
    : field.closest('.field')?.querySelector('.field-label');

  if (!labelEl) {
    return null;
  }

  return (labelEl.textContent || '').replace(/\*/g, '').replace(/\(facultatif\)/gi, '').trim() || null;
}

/**
 * Affiche l'erreur sous un champ et le surligne en rouge.
 *
 * @param {HTMLElement} field Champ concerné
 * @param {string|null} message Message optionnel sous le champ
 * @return {void}
 */
function showFieldError(field, message) {
  if (!field) {
    return;
  }

  field.classList.add('is-error');
  field.classList.remove('is-valid');

  const fieldWrap = field.closest('.field');
  let error = fieldWrap ? fieldWrap.querySelector('.field-error') : null;

  if (!error && fieldWrap) {
    error = document.createElement('span');
    error.className = 'field-error';
    error.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ce champ est obligatoire';
    const liveFeedback = fieldWrap.querySelector('.phone-live-feedback');
    if (liveFeedback) {
      fieldWrap.insertBefore(error, liveFeedback);
    } else {
      fieldWrap.appendChild(error);
    }
  }

  if (error) {
    error.classList.add('visible');
    if (message) {
      error.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${message}`;
    }
  }
}

function clearFieldError(field) {
  field.classList.remove('is-error');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement?.parentElement?.querySelector('.field-error');
  if (error) {
    error.classList.remove('visible');
  }
}

function markFieldValid(field) {
  field.classList.remove('is-error');
  field.classList.add('is-valid');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement?.parentElement?.querySelector('.field-error');
  if (error) {
    error.classList.remove('visible');
  }
}

/**
 * Valide un champ obligatoire isolé (utilisé au blur / input).
 *
 * @param {HTMLElement} field Champ à valider
 * @return {boolean}
 */
function validateRequiredField(field) {
  if (!field || !field.hasAttribute('data-required')) {
    return true;
  }

  const value = (field.value || '').trim();

  if (!value) {
    const label = getFieldValidationLabel(field);
    showFieldError(field, label ? `Le champ « ${label} » est obligatoire` : 'Ce champ est obligatoire');
    return false;
  }

  if (field.type === 'email') {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      showFieldError(field, 'Adresse e-mail invalide');
      return false;
    }
  }

  markFieldValid(field);
  return true;
}

/**
 * Branche la validation instantanée (rouge au blur) sur les champs obligatoires.
 *
 * @return {void}
 */
function wireInstantRequiredValidation() {
  document.querySelectorAll('.step [data-required]').forEach((field) => {
    if (field.dataset.instantValidationWired === '1') {
      return;
    }
    if (field.id === 'nom' || field.id === 'prenom') {
      return;
    }
    field.dataset.instantValidationWired = '1';

    field.addEventListener('blur', () => {
      validateRequiredField(field);
    });

    field.addEventListener('input', () => {
      if (field.classList.contains('is-error')) {
        validateRequiredField(field);
      }
    });
  });
}

/**
 * Valide le département selon la config (requis sauf si « aucun département »).
 *
 * @return {boolean}
 */
function validateDepartementFieldConfigured() {
  if (typeof isRegistrationFieldVisible === 'function' && !isRegistrationFieldVisible('departement')) {
    return true;
  }

  if (typeof isRegistrationFieldRequired !== 'function' || !isRegistrationFieldRequired('departement')) {
    return true;
  }

  const noDeptCheck = document.getElementById('noDepartement');
  if (noDeptCheck && noDeptCheck.checked) {
    const deptInput = document.getElementById('departement');
    if (deptInput) {
      clearFieldError(deptInput);
      deptInput.classList.remove('is-error');
    }
    return true;
  }

  const deptInput = document.getElementById('departement');
  if (!deptInput) {
    return true;
  }

  deptInput.setAttribute('data-required', '');
  return validateRequiredField(deptInput);
}

/**
 * Valide le choix d'hébergement lorsque requis par la configuration admin.
 *
 * @return {boolean}
 */
function validateHebergementFieldConfigured() {
  if (typeof isRegistrationFieldVisible === 'function' && !isRegistrationFieldVisible('hebergement')) {
    const wrapper = document.querySelector('[data-reg-field="hebergement"]');
    if (wrapper) {
      wrapper.classList.remove('is-error');
    }
    return true;
  }

  if (typeof isRegistrationFieldRequired !== 'function' || !isRegistrationFieldRequired('hebergement')) {
    const wrapper = document.querySelector('[data-reg-field="hebergement"]');
    if (wrapper) {
      wrapper.classList.remove('is-error');
    }
    return true;
  }

  const wrapper = document.querySelector('[data-reg-field="hebergement"]');
  const selected = document.querySelector('input[name="hebergement"]:checked');

  if (!selected) {
    if (wrapper) {
      wrapper.classList.add('is-error');
    }
    const err = document.getElementById('hebergementRequiredError');
    if (err) {
      err.classList.add('visible');
    }
    return false;
  }

  if (wrapper) {
    wrapper.classList.remove('is-error');
  }
  const err = document.getElementById('hebergementRequiredError');
  if (err) {
    err.classList.remove('visible');
  }
  return true;
}

/**
 * Valide les champs texte/select configurables d'une étape (hors cas spéciaux).
 *
 * @param {number} step Index d'étape
 * @return {boolean}
 */
function validateConfiguredInputFieldsForStep(step) {
  const fields = Object.values(App.formFields || {}).filter(
    (field) => field.step === step && field.is_visible && field.is_required
  );

  const skipKeys = new Set([
    'nom',
    'prenom',
    'date_naissance',
    'telephone',
    'email',
    'photo',
    'departement',
    'hebergement',
    'observations',
    'tel_urgence',
    'guardian_phone',
  ]);

  let valid = true;

  fields.forEach((field) => {
    if (skipKeys.has(field.key)) {
      return;
    }

    const inputId = REGISTRATION_FIELD_INPUT_IDS[field.api_key]
      || REGISTRATION_FIELD_INPUT_IDS[field.key]
      || (typeof registrationFieldInputId === 'function'
        ? registrationFieldInputId(field.api_key || field.key)
        : null);
    if (!inputId) {
      return;
    }

    const input = document.getElementById(inputId);
    if (!input) {
      return;
    }

    input.setAttribute('data-required', '');
    if (!validateRequiredField(input)) {
      valid = false;
    }
  });

  return valid;
}

/**
 * Valide les téléphones optionnels/requis des étapes coordonnées (urgence, tuteur).
 *
 * @param {number} step Index d'étape
 * @return {boolean}
 */
function validateConfiguredPhoneFieldsForStep(step) {
  let valid = true;

  if (step === 1 && typeof isRegistrationFieldRequired === 'function') {
    if (isRegistrationFieldRequired('tel_urgence')) {
      const telUrgEl = document.getElementById('telUrgence');
      if (telUrgEl && typeof isRegFieldDomVisible === 'function' && isRegFieldDomVisible('tel_urgence')) {
        telUrgEl.setAttribute('data-required', '');
        const telUrgRaw = (telUrgEl.value || '').trim();
        if (!telUrgRaw) {
          const label = getFieldValidationLabel(telUrgEl);
          showFieldError(
            telUrgEl,
            label ? `Le champ « ${label} » est obligatoire` : 'Le téléphone d\'urgence est obligatoire'
          );
          valid = false;
        } else if (typeof canonicalEmergencyDigitsClient === 'function' && typeof digitsLookLikeE164 === 'function') {
          const indicatifEl = document.getElementById('indicatif');
          const indicatif = indicatifEl ? indicatifEl.value : '+243';
          const canon = canonicalEmergencyDigitsClient(telUrgRaw, indicatif);
          if (!digitsLookLikeE164(canon)) {
            showFieldError(telUrgEl, 'Numéro de téléphone d\'urgence invalide ou incomplet');
            valid = false;
          }
        }
      }
    }

    if (isRegistrationFieldRequired('guardian_phone')) {
      const guardianEl = document.getElementById('guardianPhone');
      if (guardianEl && !document.getElementById('familyMultiChildCheck')?.checked) {
        guardianEl.setAttribute('data-required', '');
        if (!(guardianEl.value || '').trim()) {
          const label = getFieldValidationLabel(guardianEl);
          showFieldError(
            guardianEl,
            label ? `Le champ « ${label} » est obligatoire` : 'Ce champ est obligatoire'
          );
          valid = false;
        }
      }
    }

    if (isRegistrationFieldRequired('guardian_name')) {
      const guardianNameEl = document.getElementById('guardianName');
      if (guardianNameEl && !document.getElementById('familyMultiChildCheck')?.checked) {
        guardianNameEl.setAttribute('data-required', '');
        if (!validateRequiredField(guardianNameEl)) {
          valid = false;
        }
      }
    }
  }

  return valid;
}

/**
 * Valide toutes les étapes de saisie (0 à 2) avant envoi au serveur.
 *
 * @return {{ valid: boolean, step: number|null }}
 */
function validateAllRegistrationSteps() {
  for (let step = 0; step <= 2; step += 1) {
    if (!validateStep(step)) {
      return { valid: false, step };
    }
  }

  return { valid: true, step: null };
}

function validateStep(step, options) {
  const opts = options || {};
  wireInstantRequiredValidation();

  const steps = document.querySelectorAll('.step');
  const section = steps[step];
  const requiredFields = section.querySelectorAll('[data-required]');
  let valid = true;

  requiredFields.forEach((field) => {
    clearFieldError(field);

    if (field.id === 'telephone' || field.id === 'email') {
      return;
    }

    if (field.id === 'nom' || field.id === 'prenom') {
      return;
    }

    if (!validateRequiredField(field)) {
      valid = false;
    }
  });

  if ((step === 0 || step === 1) && !validateConfiguredPhoneFieldsForStep(step)) {
    valid = false;
  }

  if ((step === 0 || step === 1) && typeof validateContactStepPhones === 'function') {
    const okPhones = validateContactStepPhones(step);
    if (!okPhones) {
      valid = false;
    }
  }

  if (step === 1) {
    const familyMultiChildCheck = document.getElementById('familyMultiChildCheck');
    if (familyMultiChildCheck && familyMultiChildCheck.checked && App.parentContactVerified !== true) {
      const status = document.getElementById('parentOtpStatus');
      if (status) {
        status.textContent = 'Veuillez d’abord vérifier l’OTP parent/tuteur via le canal choisi.';
        status.classList.add('is-error-text');
      }
      valid = false;
    }

    const parentFullNameInput = document.getElementById('parentFullName');
    if (
      familyMultiChildCheck &&
      familyMultiChildCheck.checked &&
      App.parentContactVerified === true &&
      parentFullNameInput &&
      parentFullNameInput.hasAttribute('data-required') &&
      !(parentFullNameInput.value || '').trim()
    ) {
      showFieldError(parentFullNameInput);
      valid = false;
    }
  }

  if (step === 0) {
    if (typeof validateIdentityStepFields === 'function') {
      if (!validateIdentityStepFields()) {
        valid = false;
      }
    }

    const dobInput = document.getElementById('dateNaissance');
    const dobRequired = typeof isRegistrationFieldRequired === 'function'
      ? isRegistrationFieldRequired('date_naissance')
      : dobInput?.hasAttribute('data-required');

    if (dobInput && dobRequired) {
      const altValue = dobInput._flatpickr?.altInput?.value || '';
      const rawValue = (altValue || dobInput.value || '').trim();

      if (!rawValue) {
        showFieldError(dobInput);
        valid = false;
      } else {
        const parsedDob = typeof parseBirthDateString === 'function'
          ? parseBirthDateString(rawValue) || parseBirthDateString(dobInput.value)
          : null;

        if (!parsedDob) {
          showFieldError(dobInput);
          const hint = document.getElementById('birthDateFormatHint');
          if (hint) {
            hint.textContent = 'Format invalide. Utilisez JJ-MM-AAAA (ex. 15-03-2005).';
          }
          valid = false;
        } else {
          const hint = document.getElementById('birthDateFormatHint');
          if (hint) {
            hint.textContent = 'Format attendu : JJ-MM-AAAA (ex. 15-03-2005). Vous pouvez aussi choisir dans le calendrier.';
          }

          const age = typeof computeAgeFromBirthDate === 'function'
            ? computeAgeFromBirthDate(parsedDob)
            : null;

          if (age !== null && age < 15) {
            showFieldError(dobInput);
            const ageDisplay = document.getElementById('ageDisplay');
            if (ageDisplay) {
              ageDisplay.textContent = 'Âge minimum requis : 15 ans.';
            }
            valid = false;
          } else {
            markFieldValid(dobInput);
          }
        }
      }
    }

    const photoRequired = typeof isRegistrationFieldRequired === 'function'
      ? isRegistrationFieldRequired('photo')
      : true;

    const photoErr = document.getElementById('photoRequiredError');
    if (photoRequired && !App.photoDataURL) {
      if (photoErr) {
        photoErr.classList.add('visible');
      }
      const zone = document.getElementById('photoZone');
      if (zone) {
        zone.classList.add('is-error');
      }
      valid = false;
    } else {
      if (photoErr) {
        photoErr.classList.remove('visible');
      }
      const zone = document.getElementById('photoZone');
      if (zone) {
        zone.classList.remove('is-error');
      }
    }
  }

  if (step === 2) {
    if (!validateDepartementFieldConfigured()) {
      valid = false;
    }

    if (!validateHebergementFieldConfigured()) {
      valid = false;
    }

    const observationsField = App.formFields && App.formFields.observations;
    if (observationsField && observationsField.is_visible && observationsField.is_required) {
      const yesRadio = document.getElementById('hasObservationsYes');
      const noRadio = document.getElementById('hasObservationsNo');
      const observationsInput = document.getElementById('observations');
      const wrapper = document.querySelector('[data-reg-field="observations"]');

      if (!yesRadio?.checked && !noRadio?.checked) {
        if (wrapper) {
          wrapper.classList.add('is-error');
        }
        valid = false;
      } else if (wrapper) {
        wrapper.classList.remove('is-error');
      }

      if (yesRadio?.checked && observationsInput && !(observationsInput.value || '').trim()) {
        showFieldError(observationsInput);
        valid = false;
      }
    }
  }

  if (!validateConfiguredInputFieldsForStep(step)) {
    valid = false;
  }

  if (!valid) {
    const firstError = section.querySelector(
      '.field-input.is-error, .field-error.visible, #photoZone.is-error, .is-error-text, [data-reg-field].is-error'
    );
    if (firstError) {
      firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    if (opts.showToast && typeof retraiteNotifyToast === 'function') {
      retraiteNotifyToast(
        'Complétez les champs obligatoires signalés en rouge sous chaque champ avant de continuer.',
        'warning'
      );
    }
  }

  return valid;
}

window.validateAllRegistrationSteps = validateAllRegistrationSteps;
window.validateStep = validateStep;

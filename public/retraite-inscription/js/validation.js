/* ═══════════════════════════════════════════
   FORM VALIDATION
═══════════════════════════════════════════ */

/**
 * Affiche l'erreur sous un champ et le surligne en rouge.
 *
 * @param {HTMLElement} field Champ concerné
 * @param {string|null} message Message optionnel sous le champ
 * @return {void}
 */
function showFieldError(field, message) {
  field.classList.add('is-error');
  field.classList.remove('is-valid');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement?.parentElement?.querySelector('.field-error');
  if (error) {
    error.classList.add('visible');
    if (message) {
      const icon = error.querySelector('i');
      error.innerHTML = icon ? `${icon.outerHTML} ${message}` : message;
    }
  }
}

function clearFieldError(field) {
  field.classList.remove('is-error');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement?.parentElement?.querySelector('.field-error');
  if (error) error.classList.remove('visible');
}

function markFieldValid(field) {
  field.classList.remove('is-error');
  field.classList.add('is-valid');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement?.parentElement?.querySelector('.field-error');
  if (error) error.classList.remove('visible');
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
    showFieldError(field);
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

function validateStep(step) {
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
    if (dobInput && dobInput.hasAttribute('data-required')) {
      if (!(dobInput.value || '').trim()) {
        showFieldError(dobInput);
        valid = false;
      } else {
        const dob = new Date(dobInput.value);
        if (!isNaN(dob.getTime())) {
          const today = new Date();
          let age = today.getFullYear() - dob.getFullYear();
          const m = today.getMonth() - dob.getMonth();
          if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
          }
          if (age < 15) {
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

  if (!valid) {
    const firstError = section.querySelector('.is-error, .field-error.visible, #photoZone.is-error, .is-error-text');
    if (firstError) {
      firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  return valid;
}

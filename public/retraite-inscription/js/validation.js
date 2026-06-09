/* ═══════════════════════════════════════════
   FORM VALIDATION
═══════════════════════════════════════════ */

function validateStep(step) {
  const steps = document.querySelectorAll('.step');
  const section = steps[step];
  const requiredFields = section.querySelectorAll('[data-required]');
  let valid = true;

  requiredFields.forEach(field => {
    clearFieldError(field);
    if (!field.value || !field.value.trim()) {
      showFieldError(field);
      valid = false;
      return;
    }
    /* Email validation */
    if (field.type === 'email') {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(field.value.trim())) {
        showFieldError(field);
        valid = false;
        return;
      }
    }
    if (step === 0 && field.id === 'telephone') {
      return;
    }
    if (step === 0 && field.id === 'email') {
      return;
    }
    markFieldValid(field);
  });

  /* Téléphone/e-mail déplacés en identité, liens tuteur/foyer gardés en coordonnées. */
  if ((step === 0 || step === 1) && typeof validateContactStepPhones === 'function') {
    const okPhones = validateContactStepPhones();
    if (!okPhones) valid = false;
  }

  if (step === 1) {
    const familyMultiChildCheck = document.getElementById('familyMultiChildCheck');
    if (familyMultiChildCheck && familyMultiChildCheck.checked && App.parentContactVerified !== true) {
      const status = document.getElementById('parentOtpStatus');
      if (status) status.textContent = 'Veuillez d’abord vérifier l’OTP parent/tuteur via le canal choisi.';
      valid = false;
    }
  }

  /* Étape identité : âge minimum 15 ans + photo obligatoire */
  if (step === 0) {
    const dobInput = document.getElementById('dateNaissance');
    if (dobInput && dobInput.value) {
      const dob = new Date(dobInput.value);
      if (!isNaN(dob.getTime())) {
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        if (age < 15) {
          showFieldError(dobInput);
          const ageDisplay = document.getElementById('ageDisplay');
          if (ageDisplay) ageDisplay.textContent = 'Âge minimum requis : 15 ans.';
          valid = false;
        }
      }
    }

    const photoRequired = typeof isRegistrationFieldRequired === 'function'
      ? isRegistrationFieldRequired('photo')
      : true;

    const photoErr = document.getElementById('photoRequiredError');
    if (photoRequired && !App.photoDataURL) {
      if (photoErr) photoErr.classList.add('visible');
      const zone = document.getElementById('photoZone');
      if (zone) zone.classList.add('is-error');
      valid = false;
    } else {
      if (photoErr) photoErr.classList.remove('visible');
      const zone = document.getElementById('photoZone');
      if (zone) zone.classList.remove('is-error');
    }
  }

  if (!valid) {
    const firstError = section.querySelector('.is-error');
    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  return valid;
}

function showFieldError(field) {
  field.classList.add('is-error');
  field.classList.remove('is-valid');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement.parentElement.querySelector('.field-error');
  if (error) error.classList.add('visible');
}

function clearFieldError(field) {
  field.classList.remove('is-error');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement.parentElement.querySelector('.field-error');
  if (error) error.classList.remove('visible');
}

function markFieldValid(field) {
  field.classList.remove('is-error');
  field.classList.add('is-valid');
  const error = field.closest('.field')
    ? field.closest('.field').querySelector('.field-error')
    : field.parentElement.parentElement.querySelector('.field-error');
  if (error) error.classList.remove('visible');
}

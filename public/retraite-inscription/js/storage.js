/* ═══════════════════════════════════════════
   SESSION STORAGE
═══════════════════════════════════════════ */

function saveState() {
  const fields = [
    'nom', 'prenom', 'sexe', 'dateNaissance',
    'indicatif', 'telephone', 'telUrgence', 'guardianName', 'guardianPhone',
    'email', 'adresse', 'commune', 'ville', 'parentContactEmail', 'parentContactPhone', 'parentEmailOtp', 'parentSmsOtp',
    'eglise', 'departement', 'observations'
  ];
  const data = {};
  fields.forEach(id => { data[id] = val(id); });
  data.hasObservations = document.querySelector('input[name="hasObservations"]:checked')?.value || '';
  data.role = val('role') || 'Participant';
  data.hebergement = getHebergementValue();
  const familyMultiChildCheck = document.getElementById('familyMultiChildCheck');
  data.familyMultiChildCheck = !!(familyMultiChildCheck && familyMultiChildCheck.checked);
  data.parentVerifiedToken = App.parentVerifiedToken || '';
  data.parentContactVerified = App.parentContactVerified === true;
  data.parentOtpVerificationId = App.parentOtpVerificationId || '';
  const noDeptCheck = document.getElementById('noDepartement');
  data.noDepartement = noDeptCheck ? noDeptCheck.checked : false;
  data._step = App.currentStep;
  try {
    sessionStorage.setItem('retraite_inscription', JSON.stringify(data));
  } catch (e) { /* ignore */ }
}

function resetRetraiteInscriptionFully() {
  if (App.registrationOpen !== true) return;

  try {
    sessionStorage.removeItem('retraite_inscription');
    sessionStorage.removeItem('retraite_participant_id');
    sessionStorage.removeItem('retraite_payment_ref');
    sessionStorage.removeItem('retraite_payment_poll');
  } catch (e) {
    /* ignore */
  }

  App.currentStep = 0;
  App.photoDataURL = null;
  App.proofFile = null;
  App.proofDataURL = null;
  App.participantId = null;
  App.paymentReference = null;
  App.paymentModeCompleted = null;
  App.paymentPollActive = false;
  if (typeof persistRetraitePaymentPollState === 'function') {
    persistRetraitePaymentPollState(false);
  }
  App.selectedFlexpayType = null;
  App.badgeView = null;
  App.acceptedPolicyIds = [];
  App.policyListRendered = [];
  App.policiesGateRequired = false;
  App.policiesModalAccepted = false;
  App.mainPhoneDuplicateRegistered = false;
  App.emailDuplicateRegistered = false;
  App.identityDuplicateRegistered = false;
  App.retreatVerificationUrl = null;
  App.retreatDownloadToken = null;
  App.parentOtpVerificationId = null;
  App.parentVerifiedToken = null;
  App.parentContactVerified = false;

  const textIds = [
    'nom', 'prenom', 'sexe', 'dateNaissance',
    'indicatif', 'telephone', 'telUrgence', 'guardianName', 'guardianPhone',
    'email', 'adresse', 'commune', 'ville', 'parentContactEmail', 'parentContactPhone', 'parentEmailOtp', 'parentSmsOtp', 'eglise', 'departement',
    'observations', 'flexpayPhoneInput'
  ];
  textIds.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    if (el.tagName === 'SELECT') {
      el.selectedIndex = 0;
    } else {
      el.value = '';
    }
    el.classList.remove('is-error', 'is-valid');
    if (typeof clearFieldError === 'function') clearFieldError(el);
  });

  const dob = document.getElementById('dateNaissance');
  if (dob && dob._flatpickr) {
    dob._flatpickr.clear();
  }
  const ageDisplay = document.getElementById('ageDisplay');
  if (ageDisplay) ageDisplay.textContent = '';

  const roleInput = document.getElementById('role');
  if (roleInput) roleInput.value = 'Participant';
  const workerCheck = document.getElementById('isWorkerCheck');
  if (workerCheck) workerCheck.checked = false;
  const workerLookup = document.getElementById('workerPrefillLookup');
  if (workerLookup) workerLookup.classList.add('hidden');
  const workerIdentifier = document.getElementById('workerIdentifier');
  if (workerIdentifier) workerIdentifier.value = '';
  const workerFeedback = document.getElementById('workerPrefillFeedback');
  if (workerFeedback) workerFeedback.textContent = '';

  document.querySelectorAll('input[name="hebergement"]').forEach(r => { r.checked = false; });
  const noDeptCheck = document.getElementById('noDepartement');
  const deptInput = document.getElementById('departement');
  if (noDeptCheck) noDeptCheck.checked = false;
  if (deptInput) {
    deptInput.disabled = false;
    deptInput.placeholder = 'Ex: Cellule Amour';
  }

  const photoRemove = document.getElementById('photoRemoveBtn');
  if (photoRemove) photoRemove.click();

  const proofRemove = document.getElementById('proofRemoveBtn');
  if (proofRemove) proofRemove.click();

  document.querySelectorAll('input[name="paymentMode"]').forEach(r => { r.checked = false; });
  if (typeof togglePaymentSections === 'function') togglePaymentSections(null);
  if (typeof hidePaymentProgressPanel === 'function') hidePaymentProgressPanel();

  const payBanner = document.getElementById('paymentStatusBanner');
  if (payBanner) {
    payBanner.classList.add('hidden');
    payBanner.innerHTML = '';
  }

  const tutorChk = document.getElementById('tutorSameFamilyCheck');
  if (tutorChk) tutorChk.checked = false;
  if (typeof setTutorSameFamilyAckVisible === 'function') setTutorSameFamilyAckVisible(false);
  const familyMultiChildCheck = document.getElementById('familyMultiChildCheck');
  if (familyMultiChildCheck) familyMultiChildCheck.checked = false;
  const familyPanel = document.getElementById('familyMultiChildPanel');
  if (familyPanel) familyPanel.classList.add('hidden');
  const guardianNameField = document.getElementById('guardianNameField');
  if (guardianNameField) guardianNameField.classList.remove('hidden');
  const guardianPhoneField = document.getElementById('guardianPhoneField');
  if (guardianPhoneField) guardianPhoneField.classList.remove('hidden');
  const parentOtpChannel = typeof getParentOtpChannelFromEvent === 'function' ? getParentOtpChannelFromEvent() : 'email';
  const parentContactEmailField = document.getElementById('parentContactEmailField');
  if (parentContactEmailField) parentContactEmailField.classList.toggle('hidden', parentOtpChannel !== 'email');
  const parentContactPhoneField = document.getElementById('parentContactPhoneField');
  if (parentContactPhoneField) parentContactPhoneField.classList.toggle('hidden', parentOtpChannel !== 'sms');
  const parentOtpFieldsWrap = document.getElementById('parentOtpFieldsWrap');
  if (parentOtpFieldsWrap) parentOtpFieldsWrap.classList.add('hidden');
  const parentEmailOtpField = document.getElementById('parentEmailOtpField');
  if (parentEmailOtpField) parentEmailOtpField.classList.toggle('hidden', parentOtpChannel !== 'email');
  const parentSmsOtpField = document.getElementById('parentSmsOtpField');
  if (parentSmsOtpField) parentSmsOtpField.classList.toggle('hidden', parentOtpChannel !== 'sms');
  const parentSendOtpBtn = document.getElementById('btnSendParentOtp');
  if (parentSendOtpBtn) {
    parentSendOtpBtn.innerHTML = parentOtpChannel === 'sms'
      ? '<i class="bi bi-shield-lock"></i> Envoyer le code OTP par SMS'
      : '<i class="bi bi-shield-lock"></i> Envoyer le code OTP par e-mail';
  }
  const parentVerifyOtpBtn = document.getElementById('btnVerifyParentOtp');
  if (parentVerifyOtpBtn) parentVerifyOtpBtn.classList.add('hidden');
  const parentOtpStatus = document.getElementById('parentOtpStatus');
  if (parentOtpStatus) parentOtpStatus.textContent = '';

  document.querySelectorAll('.phone-live-feedback').forEach(el => {
    el.classList.add('hidden');
    el.textContent = '';
    el.innerHTML = '';
    el.dataset.variant = '';
    el.style.color = '';
  });

  const confirmCheck = document.getElementById('confirmCheck');
  if (confirmCheck) confirmCheck.checked = false;

  const badgeRecap = document.getElementById('badgeRecapMirrored');
  if (badgeRecap) badgeRecap.innerHTML = '';

  const qrMount = document.getElementById('badgeQrMount');
  if (qrMount) qrMount.innerHTML = '';
  const qrLine = document.getElementById('badgeQrLinkLine');
  if (qrLine) qrLine.textContent = '';

  document.querySelectorAll('.step').forEach((step, idx) => {
    step.classList.toggle('active', idx === 0);
  });

  App.currentStep = 0;

  try {
    if (typeof refreshMainTelephoneHints === 'function') refreshMainTelephoneHints();
    if (typeof scheduleMainPhoneDuplicateFetch === 'function') scheduleMainPhoneDuplicateFetch();
    if (typeof scheduleTutorFamilyHintFetch === 'function') scheduleTutorFamilyHintFetch();
  } catch (e) {
    /* ignore */
  }

  try {
    if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();
  } catch (e) {
    /* ignore */
  }

  if (typeof updateStepper === 'function') updateStepper();
  if (typeof resetRetraiteUrlParams === 'function') resetRetraiteUrlParams();
  window.scrollTo({ top: 0, behavior: 'smooth' });
  try {
    retraiteNotifyToast('Formulaire réinitialisé. Vous pouvez recommencer une nouvelle inscription.', 'success');
  } catch (e) {
    /* ignore */
  }
}

window.resetRetraiteInscriptionFully = resetRetraiteInscriptionFully;

function restoreState() {
  try {
    const raw = sessionStorage.getItem('retraite_inscription');
    if (!raw) return;
    const data = JSON.parse(raw);
    Object.keys(data).forEach(id => {
      if (id === '_step' || id === 'hebergement' || id === 'noDepartement') return;
      const el = document.getElementById(id);
      if (el) el.value = data[id] || '';
    });
    const roleEl = document.getElementById('role');
    if (roleEl) roleEl.value = data.role || 'Participant';
    if (data.hasObservations) {
      const obsRadio = document.querySelector(`input[name="hasObservations"][value="${data.hasObservations}"]`);
      if (obsRadio) {
        obsRadio.checked = true;
        obsRadio.dispatchEvent(new Event('change'));
      }
    }
    const workerCheck = document.getElementById('isWorkerCheck');
    if (workerCheck && (data.role === 'Ouvrier' || workerCheck.checked)) {
      workerCheck.checked = data.role === 'Ouvrier';
      workerCheck.dispatchEvent(new Event('change'));
    }
    /* Restore hébergement radio */
    if (data.hebergement) {
      const hebergementSelect = document.getElementById('hebergement');
      if (hebergementSelect) {
        hebergementSelect.value = data.hebergement;
      } else {
        const radio = document.querySelector(`input[name="hebergement"][value="${data.hebergement}"]`);
        if (radio) radio.checked = true;
      }
    }
    /* Restore noDepartement checkbox */
    if (data.noDepartement) {
      const noDeptCheck = document.getElementById('noDepartement');
      if (noDeptCheck) {
        noDeptCheck.checked = true;
        noDeptCheck.dispatchEvent(new Event('change'));
      }
    }
    /* Trigger age calculation */
    if (data.dateNaissance) {
      document.getElementById('dateNaissance').dispatchEvent(new Event('change'));
    }
    /* Restore step (but not badge step) */
    if (data._step && data._step < 5) {
      const steps = document.querySelectorAll('.step');
      steps[0].classList.remove('active');
      App.currentStep = data._step;
      steps[App.currentStep].classList.add('active');
      updateStepper();
    }
    const familyMultiChildCheck = document.getElementById('familyMultiChildCheck');
    if (familyMultiChildCheck && data.familyMultiChildCheck) {
      familyMultiChildCheck.checked = true;
      familyMultiChildCheck.dispatchEvent(new Event('change'));
    }
    App.parentVerifiedToken = data.parentVerifiedToken || null;
    App.parentContactVerified = data.parentContactVerified === true;
    App.parentOtpVerificationId = data.parentOtpVerificationId || null;

    try {
      if (typeof refreshEmailLiveFeedback === 'function') refreshEmailLiveFeedback();
      if (typeof flushEmailDuplicateFetch === 'function') flushEmailDuplicateFetch();
      else if (typeof scheduleEmailDuplicateFetch === 'function') scheduleEmailDuplicateFetch();
    } catch (e) {
      /* ignore */
    }
  } catch (e) { /* ignore */ }
}

/* ═══════════════════════════════════════════
   APP BOOTSTRAP
═══════════════════════════════════════════ */

/**
 * Masque le splash avec animation de sortie puis exécute un callback.
 *
 * @param {Function|null} onDone Callback après la transition
 * @return {void}
 */
function dismissRetraiteGateSplash(onDone) {
  const splash = document.getElementById('retraiteGateSplash');
  if (!splash) {
    if (typeof onDone === 'function') {
      onDone();
    }
    return;
  }

  splash.classList.remove('hold');
  splash.classList.add('exit');
  window.setTimeout(() => {
    splash.classList.add('hidden');
    if (typeof onDone === 'function') {
      onDone();
    }
  }, 650);
}

document.addEventListener('DOMContentLoaded', async () => {
  wireMandatoryPoliciesModal();

  const gateOverlay = document.getElementById('retraiteGateOverlay');
  const gateSplash = document.getElementById('retraiteGateSplash');
  const gateClosed = document.getElementById('retraiteGateClosed');
  const mainShell = document.getElementById('retraiteMainShell');

  App.registrationOpen = false;

  let ev = null;
  try {
    ev = await fetchRetraiteEvent();
  } catch (e) {
    console.warn('Événement retraite non chargé', e);
  }

  if (!ev) {
    if (gateSplash) {
      gateSplash.classList.add('hidden');
    }
    if (gateClosed) {
      gateClosed.classList.remove('hidden');
    }
    App.registrationOpen = false;
    return;
  }

  dismissRetraiteGateSplash(() => {
    if (gateOverlay) {
      gateOverlay.classList.add('hidden');
    }
    if (mainShell) {
      mainShell.classList.remove('hidden');
    }
  });
  App.registrationOpen = true;

  applyHeroFromEvent(ev);

  if (typeof applyRegistrationFormConfig === 'function') {
    applyRegistrationFormConfig(ev);
  }

  try {
    const params = new URLSearchParams(window.location.search);
    const resumeRef = params.get('resume_payment_ref');
    if (resumeRef && typeof resumeAfterCardPayment === 'function') {
      await resumeAfterCardPayment(resumeRef);
    }
  } catch (e) {
    console.warn('Reprise paiement carte', e);
  }

  /* ─── Flux formulaire uniquement si un événement est ouvert ─── */

  document.querySelectorAll('.field-input').forEach(input => {
    input.addEventListener('input', () => clearFieldError(input));
    input.addEventListener('change', () => clearFieldError(input));
  });

  const confirmCheck = document.getElementById('confirmCheck');
  const submitBtn = document.getElementById('submitBtn');
  if (confirmCheck && submitBtn) {
    confirmCheck.addEventListener('change', () => {
      if (typeof recapUpdateSubmitGate === 'function') recapUpdateSubmitGate();
    });
    submitBtn.disabled = true;
  }

  const noDeptCheck = document.getElementById('noDepartement');
  const deptInput = document.getElementById('departement');
  if (noDeptCheck && deptInput) {
    noDeptCheck.addEventListener('change', () => {
      if (noDeptCheck.checked) {
        deptInput.value = '';
        deptInput.disabled = true;
        deptInput.placeholder = 'Aucun département / cellule';
      } else {
        deptInput.disabled = false;
        deptInput.placeholder = 'Ex: Cellule Amour';
      }
    });
  }

  const storedPid = sessionStorage.getItem('retraite_participant_id');
  if (storedPid && !App.participantId) {
    App.participantId = Number(storedPid) || null;
  }

  const newRegBtn = document.getElementById('badgeNewRegistrationBtn');
  if (newRegBtn) {
    newRegBtn.addEventListener('click', () => resetRetraiteInscriptionFully());
  }

  if (typeof wirePaymentModes === 'function') {
    wirePaymentModes();
  }

  if (typeof wireIdentityLiveValidation === 'function') {
    wireIdentityLiveValidation();
  }
  if (typeof wirePhoneLiveValidation === 'function') {
    wirePhoneLiveValidation();
  }
  if (typeof wireInstantRequiredValidation === 'function') {
    wireInstantRequiredValidation();
  }
  wireParentMultiChildVerification();
  wireWorkerPrefill();
  wireObservationsToggle();

  initFlatpickr();
  initDateFallback();
  initPhotoUpload();
  initProofUpload();
  restoreState();
  updateStepper();

  if (typeof resumeInscriptionPaymentPollIfNeeded === 'function') {
    await resumeInscriptionPaymentPollIfNeeded();
  }

  if (App.participantId && typeof refreshPaidProceedToBadgePanel === 'function') {
    void refreshPaidProceedToBadgePanel();
  }

  if (typeof handleCardReturnFlash === 'function') {
    handleCardReturnFlash();
  }
});

function wireParentMultiChildVerification() {
  const check = document.getElementById('familyMultiChildCheck');
  const panel = document.getElementById('familyMultiChildPanel');
  const sendBtn = document.getElementById('btnSendParentOtp');
  const verifyBtn = document.getElementById('btnVerifyParentOtp');
  const status = document.getElementById('parentOtpStatus');
  if (!check || !panel || !sendBtn || !verifyBtn || !status) return;
  const contactEmailField = document.getElementById('parentContactEmailField');
  const contactPhoneField = document.getElementById('parentContactPhoneField');
  const contactEmailInput = document.getElementById('parentContactEmail');
  const contactPhoneInput = document.getElementById('parentContactPhone');
  const otpFieldsWrap = document.getElementById('parentOtpFieldsWrap');
  const emailOtpField = document.getElementById('parentEmailOtpField');
  const smsOtpField = document.getElementById('parentSmsOtpField');
  const emailOtpInput = document.getElementById('parentEmailOtp');
  const smsOtpInput = document.getElementById('parentSmsOtp');
  const parentFullNameField = document.getElementById('parentFullNameField');
  const parentFullNameInput = document.getElementById('parentFullName');
  const channelHint = document.getElementById('parentOtpChannelHint');
  const guardianNameField = document.getElementById('guardianNameField');
  const guardianPhoneField = document.getElementById('guardianPhoneField');
  const guardianNameInput = document.getElementById('guardianName');
  const guardianPhoneInput = document.getElementById('guardianPhone');

  const getSelectedChannel = () => {
    return typeof getParentOtpChannelFromEvent === 'function' ? getParentOtpChannelFromEvent() : 'email';
  };

  const setStatus = (text, type) => {
    status.textContent = text || '';
    status.classList.remove(
      'error', 'success', 'warning', 'info',
      'parent-otp-status--error', 'parent-otp-status--success',
      'parent-otp-status--warning', 'parent-otp-status--info'
    );
    if (type) {
      status.classList.add(type);
      status.classList.add(`parent-otp-status--${type}`);
    }
  };

  const clearVerificationState = () => {
    App.parentOtpVerificationId = null;
    App.parentVerifiedToken = null;
    App.parentContactVerified = false;
    App.parentSmsDeliveryLogId = null;
    setParentContactLocked(false);
    if (parentFullNameField) parentFullNameField.classList.add('hidden');
    if (parentFullNameInput) {
      parentFullNameInput.removeAttribute('data-required');
      parentFullNameInput.value = '';
      clearFieldError(parentFullNameInput);
    }
    if (otpFieldsWrap) otpFieldsWrap.classList.add('hidden');
    verifyBtn.classList.add('hidden');
    sendBtn.classList.remove('hidden');
    if (emailOtpInput) emailOtpInput.value = '';
    if (smsOtpInput) smsOtpInput.value = '';
  };

  const setParentContactLocked = (locked) => {
    [contactEmailInput, contactPhoneInput, emailOtpInput, smsOtpInput].forEach((el) => {
      if (!el) return;
      el.readOnly = !!locked;
      el.classList.toggle('is-locked', !!locked);
    });
  };

  const showParentVerifiedState = (knownParentFullName) => {
    setParentContactLocked(true);
    sendBtn.classList.add('hidden');
    verifyBtn.classList.add('hidden');
    if (parentFullNameField) parentFullNameField.classList.remove('hidden');
    if (parentFullNameInput) {
      parentFullNameInput.setAttribute('data-required', '');
      if (knownParentFullName && !parentFullNameInput.value.trim()) {
        parentFullNameInput.value = knownParentFullName;
      }
      parentFullNameInput.focus();
    }
  };
  const syncOtpChannelUi = () => {
    const channel = getSelectedChannel();
    if (contactEmailField) contactEmailField.classList.toggle('hidden', channel !== 'email');
    if (contactPhoneField) contactPhoneField.classList.toggle('hidden', channel !== 'sms');
    if (contactEmailInput && channel !== 'email') {
      contactEmailInput.value = '';
      clearFieldError(contactEmailInput);
    }
    if (contactPhoneInput && channel !== 'sms') {
      contactPhoneInput.value = '';
      clearFieldError(contactPhoneInput);
    }
    if (emailOtpField) emailOtpField.classList.toggle('hidden', channel !== 'email');
    if (smsOtpField) smsOtpField.classList.toggle('hidden', channel !== 'sms');
    if (emailOtpInput && channel !== 'email') {
      emailOtpInput.value = '';
      clearFieldError(emailOtpInput);
    }
    if (smsOtpInput && channel !== 'sms') {
      smsOtpInput.value = '';
      clearFieldError(smsOtpInput);
    }
    sendBtn.innerHTML = channel === 'sms'
      ? '<i class="bi bi-shield-lock"></i> Envoyer le code OTP par SMS'
      : '<i class="bi bi-shield-lock"></i> Envoyer le code OTP par e-mail';
    if (channelHint) {
      channelHint.textContent = channel === 'sms'
        ? 'Anti-robot: l’OTP sera envoyé par SMS selon la configuration de l’événement. Ensuite, vous pourrez réutiliser les mêmes contacts pour les prochains enfants.'
        : 'Anti-robot: l’OTP sera envoyé par e-mail selon la configuration de l’événement. Ensuite, vous pourrez réutiliser les mêmes contacts pour les prochains enfants.';
    }
  };

  const syncParentFieldsVisibility = () => {
    const hideGuardianFields = check.checked;
    if (guardianNameField) guardianNameField.classList.toggle('hidden', hideGuardianFields);
    if (guardianPhoneField) guardianPhoneField.classList.toggle('hidden', hideGuardianFields);
    if (!hideGuardianFields) return;
    if (guardianNameInput) {
      guardianNameInput.value = '';
      clearFieldError(guardianNameInput);
    }
    if (guardianPhoneInput) {
      guardianPhoneInput.value = '';
      clearFieldError(guardianPhoneInput);
    }
    if (typeof setLiveHint === 'function') {
      setLiveHint('guardianNameLiveFeedback', '');
      setLiveHint('guardianPhoneLiveFeedback', '');
    }
  };

  const pollParentSmsDelivery = async (logId, remainingChecks = 4) => {
    if (!logId || remainingChecks < 1) return;
    try {
      const base = getRetraiteApiBase();
      const res = await fetch(`${base}/contact-verification/sms-delivery?log_id=${encodeURIComponent(logId)}`, {
        headers: { Accept: 'application/json' },
      });
      const json = await res.json().catch(() => ({}));
      const d = json.data || {};
      if (d.delivery_status === 'DELIVERED') {
        setStatus('SMS OTP livré au téléphone parent/tuteur. Entrez le code reçu.', 'success');
        return;
      }
      if (d.delivery_status === 'FAILED' || d.delivery_status === 'ERROR') {
        setStatus('Le SMS OTP n’a pas été livré. Vérifiez le numéro ou renvoyez le code.', 'error');
        return;
      }
      setStatus('SMS OTP transmis à l’opérateur. Attente de confirmation de livraison...', 'info');
    } catch (e) {
      /* La vérification de livraison est informative : ne bloque pas la saisie OTP. */
    }
    setTimeout(() => pollParentSmsDelivery(logId, remainingChecks - 1), 5000);
  };

  syncOtpChannelUi();
  syncParentFieldsVisibility();

  ['parentContactEmail', 'parentContactPhone'].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => {
      if (!check.checked) return;
      clearVerificationState();
      syncOtpChannelUi();
      setStatus('Contacts modifiés. Relancez l’envoi OTP pour revalider.', 'warning');
    });
  });

  check.addEventListener('change', () => {
    panel.classList.toggle('hidden', !check.checked);
    syncParentFieldsVisibility();
    syncOtpChannelUi();
    if (!check.checked) {
      clearVerificationState();
      syncOtpChannelUi();
      setStatus('');
      return;
    }
    setStatus('Renseignez vos contacts puis lancez l’envoi des OTP.', 'info');
  });

  sendBtn.addEventListener('click', async () => {
    const channel = getSelectedChannel();
    const email = val('parentContactEmail');
    const phone = val('parentContactPhone');
    if (channel === 'email' && !email) {
      setStatus('Renseignez l’e-mail parent/tuteur.', 'warning');
      return;
    }
    if (channel === 'sms' && !phone) {
      setStatus('Renseignez le téléphone parent/tuteur.', 'warning');
      return;
    }

    const sendOriginalHtml = sendBtn.innerHTML;
    sendBtn.disabled = true;
    sendBtn.classList.add('loading');
    sendBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Envoi en cours...';
    setStatus(channel === 'sms' ? 'Envoi du code OTP SMS en cours...' : 'Envoi du code OTP e-mail en cours...', 'info');
    clearVerificationState();

    try {
      const base = getRetraiteApiBase();
      const res = await fetch(`${base}/contact-verification/request-otp`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          email,
          phone,
          event_id: App.activeEvent && App.activeEvent.id ? App.activeEvent.id : null,
        }),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.message || 'Envoi OTP impossible.');
      App.parentOtpVerificationId = json.data && json.data.verification_id ? String(json.data.verification_id) : null;
      App.parentSmsDeliveryLogId = json.data && json.data.sms_log_id ? Number(json.data.sms_log_id) : null;
      if (otpFieldsWrap) otpFieldsWrap.classList.remove('hidden');
      verifyBtn.classList.remove('hidden');
      sendBtn.innerHTML = channel === 'sms'
        ? '<i class="bi bi-arrow-clockwise"></i> Renvoyer le code OTP par SMS'
        : '<i class="bi bi-arrow-clockwise"></i> Renvoyer le code OTP par e-mail';
      setStatus(channel === 'sms'
        ? 'Code OTP envoyé par SMS. S’il n’arrive pas, vous pouvez le renvoyer.'
        : 'Code OTP envoyé par e-mail. S’il n’arrive pas, vous pouvez le renvoyer.', 'success');
      if (channel === 'sms' && App.parentSmsDeliveryLogId) {
        setTimeout(() => pollParentSmsDelivery(App.parentSmsDeliveryLogId), 3500);
      }
    } catch (e) {
      setStatus(e.message || 'Échec d’envoi OTP.', 'error');
    } finally {
      sendBtn.disabled = false;
      sendBtn.classList.remove('loading');
      if (!App.parentOtpVerificationId) sendBtn.innerHTML = sendOriginalHtml;
    }
  });

  verifyBtn.addEventListener('click', async () => {
    const channel = getSelectedChannel();
    const verificationId = App.parentOtpVerificationId;
    const otp = channel === 'sms' ? val('parentSmsOtp') : val('parentEmailOtp');
    if (!verificationId) {
      setStatus('Demandez d’abord l’envoi de l’OTP.', 'warning');
      return;
    }
    if (!otp || otp.length < 6) {
      setStatus(channel === 'sms' ? 'Entrez le code OTP SMS (6 chiffres).' : 'Entrez le code OTP e-mail (6 chiffres).', 'warning');
      return;
    }

    const verifyOriginalHtml = verifyBtn.innerHTML;
    verifyBtn.disabled = true;
    verifyBtn.classList.add('loading');
    verifyBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Vérification en cours...';
    setStatus(channel === 'sms' ? 'Vérification du code OTP SMS...' : 'Vérification du code OTP e-mail...', 'info');

    try {
      const base = getRetraiteApiBase();
      const res = await fetch(`${base}/contact-verification/verify-otp`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          verification_id: verificationId,
          otp,
        }),
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(json.message || 'Vérification OTP impossible.');
      App.parentVerifiedToken = json.data && json.data.verified_token ? String(json.data.verified_token) : null;
      App.parentContactVerified = !!App.parentVerifiedToken;
      const knownParentName = json.data && json.data.known_parent_full_name
        ? String(json.data.known_parent_full_name)
        : '';
      if (App.parentContactVerified) {
        showParentVerifiedState(knownParentName);
      }
      setStatus(channel === 'sms'
        ? 'Téléphone parent/tuteur vérifié par SMS. Vous pouvez réutiliser ces contacts pour d’autres enfants.'
        : 'E-mail parent/tuteur vérifié. Vous pouvez réutiliser ces contacts pour d’autres enfants.', 'success');
    } catch (e) {
      App.parentContactVerified = false;
      App.parentVerifiedToken = null;
      setStatus(e.message || 'OTP invalides.', 'error');
    } finally {
      verifyBtn.disabled = false;
      verifyBtn.classList.remove('loading');
      verifyBtn.innerHTML = verifyOriginalHtml;
    }
  });
}

async function wireWorkerPrefill() {
  const check = document.getElementById('isWorkerCheck');
  const lookup = document.getElementById('workerPrefillLookup');
  const input = document.getElementById('workerIdentifier');
  const btn = document.getElementById('workerPrefillBtn');
  const feedback = document.getElementById('workerPrefillFeedback');

  if (!check || !lookup || !input || !btn || !feedback) return;

  const setFeedback = (message, type = 'info') => {
    feedback.textContent = message;
    feedback.classList.remove(
      'worker-prefill-feedback-success',
      'worker-prefill-feedback-error',
      'worker-prefill-feedback-warning',
      'worker-prefill-feedback-info'
    );
    feedback.classList.add(`worker-prefill-feedback-${type}`);
  };

  const syncNoDepartementForWorker = (isWorker) => {
    const noDeptWrap = document.getElementById('noDepartementWrap');
    const noDeptCheck = document.getElementById('noDepartement');
    const deptInput = document.getElementById('departement');
    if (!noDeptWrap || !noDeptCheck) {
      return;
    }
    noDeptWrap.classList.toggle('hidden', isWorker);
    if (isWorker) {
      noDeptCheck.checked = false;
      if (deptInput) {
        deptInput.disabled = false;
        deptInput.placeholder = 'Ex: Cellule Amour';
      }
    }
  };

  check.addEventListener('change', () => {
    lookup.classList.toggle('hidden', !check.checked);
    syncNoDepartementForWorker(check.checked);
    if (check.checked) {
      document.getElementById('role').value = 'Ouvrier';
      document.getElementById('role').dispatchEvent(new Event('change', { bubbles: true }));
      input.focus();
      setFeedback('Entrez l’e-mail ou le téléphone lié à votre compte ouvrier.', 'info');
    } else {
      document.getElementById('role').value = 'Participant';
      document.getElementById('role').dispatchEvent(new Event('change', { bubbles: true }));
      setFeedback('', 'info');
    }
  });

  btn.addEventListener('click', async () => {
    const identifier = input.value.trim();
    if (!identifier) {
      setFeedback('Indiquez un e-mail ou un téléphone.', 'warning');
      return;
    }

    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Recherche...';
    setFeedback('Recherche du compte ouvrier...', 'info');

    try {
      const base = getRetraiteApiBase();
      const res = await fetch(`${base}/worker-prefill`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ identifier }),
      });
      const json = await res.json().catch(() => ({}));

      if (!res.ok) {
        const error = new Error(json.message || 'Compte ouvrier introuvable.');
        error.status = res.status;
        throw error;
      }

      applyWorkerPrefill(json.data || {});
      setFeedback('Informations ouvrier retrouvées et préremplies. Vérifiez puis continuez.', 'success');
      retraiteNotifyToast('Compte ouvrier retrouvé. Les champs disponibles ont été préremplis.', 'success');
    } catch (error) {
      const type = error.status === 404 || error.status === 422 ? 'warning' : 'error';
      setFeedback(error.message || 'Impossible de préremplir depuis ce compte.', type);
    } finally {
      btn.disabled = false;
      btn.innerHTML = original;
    }
  });
}

function fillAndNotifyField(id, value) {
  if (value === undefined || value === null || value === '') return;
  const field = document.getElementById(id);
  if (!field) return;
  field.value = value;
  field.dispatchEvent(new Event('input', { bubbles: true }));
  field.dispatchEvent(new Event('change', { bubbles: true }));
  if (typeof clearFieldError === 'function') clearFieldError(field);
}

function applyWorkerPrefill(data) {
  fillAndNotifyField('nom', data.nom);
  fillAndNotifyField('prenom', data.prenom);
  fillAndNotifyField('sexe', data.sexe);
  fillAndNotifyField('dateNaissance', data.date_naissance);
  fillAndNotifyField('email', data.email);
  fillAndNotifyField('role', 'Ouvrier');
  fillAndNotifyField('telUrgence', data.telephone_urgence);
  fillAndNotifyField('guardianName', data.guardian_name);
  fillAndNotifyField('guardianPhone', data.guardian_phone);
  fillAndNotifyField('adresse', data.adresse);
  fillAndNotifyField('commune', data.commune);
  fillAndNotifyField('ville', data.ville);
  fillAndNotifyField('eglise', data.eglise_assemblee);
  fillAndNotifyField('departement', data.departement_cellule);
  fillAndNotifyField('observations', data.observation);

  if (data.hebergement_choice) {
    const hebergement = String(data.hebergement_choice).toLowerCase();
    const normalized = hebergement.startsWith('i') || hebergement.startsWith('o') ? 'interne' : 'externe';
    const radio = document.querySelector(`input[name="hebergement"][value="${normalized}"]`);
    if (radio) {
      radio.checked = true;
      radio.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  if (data.telephone) {
    const digits = String(data.telephone).replace(/\D+/g, '');
    if (data.indicatif_telephone) {
      fillAndNotifyField('indicatif', data.indicatif_telephone);
      fillAndNotifyField('telephone', data.telephone);
    } else if (digits.startsWith('243') && digits.length > 3) {
      fillAndNotifyField('indicatif', '+243');
      fillAndNotifyField('telephone', digits.slice(3));
    } else {
      fillAndNotifyField('telephone', data.telephone);
    }
  }
}

/**
 * Affiche le champ texte des observations uniquement si « Oui » est coché.
 *
 * @return {void}
 */
function wireObservationsToggle() {
  const yesRadio = document.getElementById('hasObservationsYes');
  const noRadio = document.getElementById('hasObservationsNo');
  const detailWrap = document.getElementById('observationsDetailWrap');
  const observationsInput = document.getElementById('observations');

  if (!yesRadio || !noRadio || !detailWrap) {
    return;
  }

  const sync = () => {
    const observationsField = App.formFields && App.formFields.observations;
    const fieldVisible = !observationsField || observationsField.is_visible === true;
    const showDetail = fieldVisible && yesRadio.checked;
    detailWrap.classList.toggle('hidden', !showDetail);
    if (!showDetail && observationsInput) {
      observationsInput.value = '';
      observationsInput.removeAttribute('data-required');
    } else if (showDetail && observationsInput && observationsField?.is_required) {
      observationsInput.setAttribute('data-required', '');
    }
  };

  yesRadio.addEventListener('change', sync);
  noRadio.addEventListener('change', sync);
  sync();
}

window.wireObservationsToggle = wireObservationsToggle;

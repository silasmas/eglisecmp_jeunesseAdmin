/* ═══════════════════════════════════════════
   FORM CONFIG (admin → formulaire public)
═══════════════════════════════════════════ */
'use strict';

const REG_STEP_GRID_SELECTORS = {
  0: '#step-0 .fields-grid',
  1: '#step-1 .fields-grid',
  2: '#step-2 .fields-grid.participation-grid, #step-2 .fields-grid',
};

/**
 * Associe une clé de registre au champ DOM (input/select).
 *
 * @param {string} apiKey Clé API (ex. date_naissance)
 * @return {string|null} Identifiant HTML
 */
function registrationFieldInputId(apiKey) {
  const map = {
    date_naissance: 'dateNaissance',
    tel_urgence: 'telUrgence',
    guardian_name: 'guardianName',
    guardian_phone: 'guardianPhone',
  };

  return map[apiKey] || apiKey;
}

/**
 * Met à jour le libellé d'un groupe radio (hébergement).
 *
 * @param {HTMLElement} wrapper Conteneur .field
 * @param {{ label?: string, is_required?: boolean, is_visible?: boolean }} field Config champ
 * @return {void}
 */
function updateRegistrationRadioGroupLabel(wrapper, field) {
  const label = wrapper.querySelector('.field-label');
  if (!label || !field.label) {
    return;
  }

  label.textContent = '';
  label.appendChild(document.createTextNode(field.label));

  if (field.is_visible && field.is_required) {
    label.appendChild(document.createTextNode(' '));
    const requiredSpan = document.createElement('span');
    requiredSpan.className = 'required';
    requiredSpan.textContent = '*';
    label.appendChild(requiredSpan);
    return;
  }

  if (field.is_visible && !field.is_required) {
    label.appendChild(document.createTextNode(' '));
    const optionalSpan = document.createElement('span');
    optionalSpan.className = 'optional';
    optionalSpan.textContent = '(facultatif)';
    label.appendChild(optionalSpan);
  }
}
/**
 * Indique si un champ est requis selon la configuration active.
 *
 * @param {string} key Clé du registre (ex. photo, commune)
 * @return {boolean}
 */
function isRegistrationFieldRequired(key) {
  const field = App.formFields && App.formFields[key];
  if (!field) {
    return false;
  }

  return field.is_visible === true && field.is_required === true;
}

/**
 * Indique si un champ est visible selon la configuration active.
 *
 * @param {string} key Clé du registre
 * @return {boolean}
 */
function isRegistrationFieldVisible(key) {
  const field = App.formFields && App.formFields[key];
  if (!field) {
    return true;
  }

  return field.is_visible === true;
}

/**
 * Met à jour le libellé d'un champ (texte + badge obligatoire/facultatif).
 *
 * @param {HTMLElement} wrapper Conteneur .field
 * @param {{ label?: string, is_required?: boolean, is_visible?: boolean }} field Config champ
 * @return {void}
 */
function updateRegistrationFieldLabel(wrapper, field) {
  const label = wrapper.querySelector('[data-reg-label]') || wrapper.querySelector('.field-label');
  if (!label || !field.label) {
    return;
  }

  const forAttr = label.getAttribute('for');
  label.textContent = '';

  if (forAttr) {
    label.setAttribute('for', forAttr);
  }

  label.appendChild(document.createTextNode(field.label));

  if (field.is_visible && field.is_required) {
    label.appendChild(document.createTextNode(' '));
    const requiredSpan = document.createElement('span');
    requiredSpan.className = 'required';
    requiredSpan.textContent = '*';
    label.appendChild(requiredSpan);
    return;
  }

  if (field.is_visible && !field.is_required) {
    label.appendChild(document.createTextNode(' '));
    const optionalSpan = document.createElement('span');
    optionalSpan.className = 'optional';
    optionalSpan.textContent = '(facultatif)';
    label.appendChild(optionalSpan);
  }
}

/**
 * Met à jour ou crée le texte d'aide sous un champ.
 *
 * @param {HTMLElement} wrapper Conteneur .field
 * @param {string|null|undefined} helperText Texte d'aide
 * @return {void}
 */
function updateRegistrationFieldHelperText(wrapper, helperText) {
  const text = (helperText || '').trim();
  const yesNoAnchor = wrapper.querySelector('[data-reg-yesno]');
  let target = wrapper.querySelector('.field-hint[data-reg-helper]');

  if (!target && yesNoAnchor) {
    target = yesNoAnchor.nextElementSibling?.classList?.contains('field-hint')
      ? yesNoAnchor.nextElementSibling
      : null;
  }

  if (!target) {
    target = wrapper.querySelector('.field-hint:not(.phone-live-feedback):not([id])');
  }

  if (!text) {
    if (target && target.hasAttribute('data-reg-helper')) {
      const fallback = target.getAttribute('data-reg-default-hint');
      if (fallback) {
        target.textContent = fallback;
      }
      target.removeAttribute('data-reg-helper');
    }
    return;
  }

  if (target) {
    if (!target.hasAttribute('data-reg-default-hint')) {
      target.setAttribute('data-reg-default-hint', target.textContent.trim());
    }
    target.textContent = text;
    target.setAttribute('data-reg-helper', '1');
    return;
  }

  const hint = document.createElement('span');
  hint.className = 'field-hint';
  hint.setAttribute('data-reg-helper', '1');
  hint.textContent = text;

  if (yesNoAnchor) {
    yesNoAnchor.insertAdjacentElement('afterend', hint);
    return;
  }

  wrapper.appendChild(hint);
}

/**
 * Réinitialise le champ observations (oui/non + détail) lorsqu'il est masqué par la config.
 *
 * @return {void}
 */
function resetObservationsFieldState() {
  const yesRadio = document.getElementById('hasObservationsYes');
  const noRadio = document.getElementById('hasObservationsNo');
  const detailWrap = document.getElementById('observationsDetailWrap');
  const observationsInput = document.getElementById('observations');

  if (yesRadio) {
    yesRadio.checked = false;
  }
  if (noRadio) {
    noRadio.checked = false;
  }
  if (detailWrap) {
    detailWrap.classList.add('hidden');
  }
  if (observationsInput) {
    observationsInput.value = '';
    observationsInput.removeAttribute('data-required');
  }
}

/**
 * Réordonne les champs configurables dans la grille de chaque étape.
 *
 * @param {Array<object>} fields Liste des champs résolus (API)
 * @return {void}
 */
function reorderRegistrationFieldsDom(fields) {
  const byStep = { 0: [], 1: [], 2: [] };

  fields.forEach((field) => {
    if (field && typeof field.step === 'number' && byStep[field.step]) {
      byStep[field.step].push(field);
    }
  });

  Object.keys(byStep).forEach((stepKey) => {
    const step = Number(stepKey);
    const selector = REG_STEP_GRID_SELECTORS[step];
    const container = document.querySelector(selector);

    if (!container) {
      return;
    }

    const sorted = byStep[step]
      .slice()
      .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));

    const staticChildren = [...container.children].filter((el) => !el.hasAttribute('data-reg-field'));
    const ordered = [];

    sorted.forEach((field) => {
      const el = container.querySelector(`[data-reg-field="${field.key}"]`);
      if (el) {
        ordered.push(el);
      }

      if (step === 1 && field.key === 'guardian_phone') {
        const tutor = staticChildren.find((node) => node.id === 'tutorSameFamilyField');
        if (tutor) {
          ordered.push(tutor);
        }
      }
    });

    staticChildren.forEach((el) => {
      if (el.id === 'tutorSameFamilyField') {
        return;
      }
      ordered.push(el);
    });

    ordered.forEach((el) => container.appendChild(el));
  });
}

/**
 * Applique la configuration des champs reçue depuis l'API événement.
 *
 * @param {{ form_fields?: { fields?: Array<object> } }} ev Payload événement actif
 * @return {void}
 */
function applyRegistrationFormConfig(ev) {
  const payload = (ev && ev.form_fields) || {};
  const fields = payload.fields || [];

  App.formFields = {};

  fields.forEach((field) => {
    if (!field || !field.key) {
      return;
    }

    App.formFields[field.key] = field;

    const wrapper = document.querySelector(`[data-reg-field="${field.key}"]`);
    if (!wrapper) {
      return;
    }

    wrapper.classList.toggle('hidden', field.is_visible !== true);
    wrapper.classList.toggle('full', field.column_span === 'full');

    if (field.key === 'observations' && field.is_visible !== true) {
      resetObservationsFieldState();
    }

    const inputs = wrapper.querySelectorAll('input.field-input, select.field-input, textarea.field-input');
    inputs.forEach((input) => {
      if (field.is_visible && field.is_required) {
        input.setAttribute('data-required', '');
      } else {
        input.removeAttribute('data-required');
      }
    });

    if (field.key === 'telephone') {
      const indicatif = document.getElementById('indicatif');
      if (indicatif) {
        if (field.is_visible && field.is_required) {
          indicatif.setAttribute('data-required', '');
        } else {
          indicatif.removeAttribute('data-required');
        }
      }
    }

    if (field.key === 'observations' && field.is_visible === true && field.is_required) {
      const yesRadio = document.getElementById('hasObservationsYes');
      const observationsInput = document.getElementById('observations');
      if (yesRadio) {
        yesRadio.setAttribute('data-required', '');
      }
      if (observationsInput) {
        observationsInput.setAttribute('data-required', '');
      }
    }

    if (field.key === 'hebergement') {
      updateRegistrationRadioGroupLabel(wrapper, field);
    } else {
      updateRegistrationFieldLabel(wrapper, field);
    }

    updateRegistrationFieldHelperText(wrapper, field.helper_text);
  });

  reorderRegistrationFieldsDom(fields);
  applyRegistrationUiSettings(payload);

  const observationsYes = document.getElementById('hasObservationsYes');
  const observationsNo = document.getElementById('hasObservationsNo');
  if (observationsYes?.checked) {
    observationsYes.dispatchEvent(new Event('change'));
  } else if (observationsNo?.checked) {
    observationsNo.dispatchEvent(new Event('change'));
  }
}

/**
 * Déplace un bloc UI avant ou après la grille de champs d'une étape.
 *
 * @param {HTMLElement|null} blockEl Bloc à repositionner
 * @param {string} anchorSelector Sélecteur de la grille de champs
 * @param {string} position before_fields | after_fields
 * @return {void}
 */
function placeRegistrationUiBlock(blockEl, anchorSelector, position) {
  const anchor = document.querySelector(anchorSelector);

  if (!blockEl || !anchor || !anchor.parentNode) {
    return;
  }

  const parent = anchor.parentNode;

  if (position === 'after_fields') {
    if (anchor.nextSibling) {
      parent.insertBefore(blockEl, anchor.nextSibling);
    } else {
      parent.appendChild(blockEl);
    }
    return;
  }

  parent.insertBefore(blockEl, anchor);
}

/**
 * Réordonne les moyens de paiement selon la configuration admin.
 *
 * @param {Array<string>} order Ordre des modes (mobile_money, card, cash)
 * @return {void}
 */
function reorderPaymentModesDom(order) {
  const container = document.getElementById('paymentModesGroup');

  if (!container || !Array.isArray(order)) {
    return;
  }

  order.forEach((mode) => {
    const label = container.querySelector(`[data-payment-mode="${mode}"]`);
    if (label) {
      container.appendChild(label);
    }
  });
}

/**
 * Applique les blocs configurables (ouvrier, parent, moyens de paiement).
 *
 * @param {{ ui_settings?: object }} payload Payload form_fields
 * @return {void}
 */
function applyRegistrationUiSettings(payload) {
  const ui = (payload && payload.ui_settings) || {};
  const workerPanel = document.getElementById('workerPrefillPanel');
  if (workerPanel) {
    workerPanel.classList.toggle('hidden', ui.worker_prefill?.is_visible === false);
    if (ui.worker_prefill?.is_visible !== false) {
      placeRegistrationUiBlock(
        workerPanel,
        '#step-0 .fields-grid',
        ui.worker_prefill?.position === 'after_fields' ? 'after_fields' : 'before_fields'
      );
    }
  }

  const parentBlock = document.getElementById('parentMultiChildBlock');
  if (parentBlock) {
    parentBlock.classList.toggle('hidden', ui.parent_multi_child?.is_visible === false);
    if (ui.parent_multi_child?.is_visible !== false) {
      placeRegistrationUiBlock(
        parentBlock,
        '#step-1 > .fields-grid',
        ui.parent_multi_child?.position === 'after_fields' ? 'after_fields' : 'before_fields'
      );
    }
  }

  const paymentModes = ui.payment_modes || {};
  document.querySelectorAll('[data-payment-mode]').forEach((label) => {
    const mode = label.getAttribute('data-payment-mode');
    const visible = paymentModes[mode]?.is_visible !== false;
    label.classList.toggle('hidden', !visible);
    if (!visible) {
      const radio = label.querySelector('input[name="paymentMode"]');
      if (radio && radio.checked) {
        radio.checked = false;
        if (typeof togglePaymentSections === 'function') {
          togglePaymentSections(null);
        }
      }
    }
  });

  const order = ui.payment_modes_order || ['mobile_money', 'card', 'cash'];
  reorderPaymentModesDom(order);

  App.uiSettings = ui;

  if (typeof autoSelectSinglePaymentMode === 'function') {
    autoSelectSinglePaymentMode();
  }
}

/**
 * Réapplique téléphone/e-mail selon la coordination contact (canal privilégié, visibilité).
 *
 * @param {object|null|undefined} uiSettings Paramètres ui_settings fusionnés
 * @return {void}
 */
function reapplyContactCoordinationFields(uiSettings) {
  if (!uiSettings || !uiSettings.contact_coordination) {
    return;
  }

  const coord = uiSettings.contact_coordination;
  const preferred = coord.preferred_channel === 'email' ? 'email' : 'phone';

  const patch = {
    telephone: {
      coordVisible: coord.telephone?.is_visible !== false,
      preferredRequired: preferred === 'phone',
    },
    email: {
      coordVisible: coord.email?.is_visible !== false,
      preferredRequired: preferred === 'email',
    },
  };

  Object.keys(patch).forEach((key) => {
    if (!App.formFields[key]) {
      return;
    }

    const field = App.formFields[key];
    const itemVisible = field.is_visible !== false;
    const itemRequired = field.is_required === true;
    const isVisible = itemVisible && patch[key].coordVisible;
    const isRequired = isVisible && patch[key].preferredRequired && itemRequired;

    field.is_visible = isVisible;
    field.is_required = isRequired;

    const wrapper = document.querySelector(`[data-reg-field="${key}"]`);
    if (!wrapper) {
      return;
    }

    wrapper.classList.toggle('hidden', !isVisible);

    const inputs = wrapper.querySelectorAll('input.field-input, select.field-input');
    inputs.forEach((input) => {
      if (isVisible && isRequired) {
        input.setAttribute('data-required', '');
      } else {
        input.removeAttribute('data-required');
      }
    });

    updateRegistrationFieldLabel(wrapper, field);
  });

  const indicatif = document.getElementById('indicatif');
  if (indicatif) {
    const telRequired = App.formFields.telephone?.is_visible && App.formFields.telephone?.is_required;
    if (telRequired) {
      indicatif.setAttribute('data-required', '');
    } else {
      indicatif.removeAttribute('data-required');
    }
  }
}

window.reapplyContactCoordinationFields = reapplyContactCoordinationFields;
window.applyRegistrationFormConfig = applyRegistrationFormConfig;
window.applyRegistrationUiSettings = applyRegistrationUiSettings;
window.isRegistrationFieldRequired = isRegistrationFieldRequired;
window.isRegistrationFieldVisible = isRegistrationFieldVisible;
window.registrationFieldInputId = registrationFieldInputId;
window.reorderRegistrationFieldsDom = reorderRegistrationFieldsDom;

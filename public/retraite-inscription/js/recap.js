/* ═══════════════════════════════════════════
   RECAP GENERATION
═══════════════════════════════════════════ */

/**
 * Champs visibles d'une étape, triés selon la configuration admin.
 *
 * @param {number} step Index d'étape (0–2)
 * @return {Array<object>}
 */
function sortedVisibleFieldsForRecapStep(step) {
  return Object.values(App.formFields || {})
    .filter((field) => field.step === step && field.is_visible)
    .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
}

/**
 * Valeur affichée pour le champ observations (oui/non + détail).
 *
 * @return {string}
 */
function getObservationsRecapValue() {
  const choice = document.querySelector('input[name="hasObservations"]:checked')?.value;
  if (choice === 'no') {
    return 'Non';
  }
  if (choice === 'yes') {
    return val('observations') || 'Oui (sans précision)';
  }

  return val('observations') || '—';
}

/**
 * Construit une ligne de récapitulatif pour un champ configuré.
 *
 * @param {object} field Config champ
 * @param {Set<string>} processedKeys Clés déjà traitées (ex. nom+prenom)
 * @return {[string, string]|null}
 */
function buildRecapRowForField(field, processedKeys) {
  const key = field.key;
  if (processedKeys.has(key)) {
    return null;
  }

  if (key === 'nom' || key === 'prenom') {
    const nomVisible = App.formFields.nom?.is_visible;
    const prenomVisible = App.formFields.prenom?.is_visible;
    if (!nomVisible && !prenomVisible) {
      return null;
    }
    if (key === 'prenom' && nomVisible) {
      return null;
    }
    processedKeys.add('nom');
    processedKeys.add('prenom');
    const label = nomVisible && prenomVisible ? 'Nom complet' : (field.label || 'Nom');
    return [label, `${val('nom')} ${val('prenom')}`.trim() || '—'];
  }

  switch (key) {
    case 'sexe': {
      const sexeLabel = val('sexe') === 'M' ? 'Masculin' : val('sexe') === 'F' ? 'Féminin' : '—';
      return [field.label, sexeLabel];
    }
    case 'date_naissance': {
      let ageStr = '';
      const dob = new Date(val('dateNaissance'));
      if (!isNaN(dob.getTime())) {
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
          age--;
        }
        ageStr = age + ' ans';
      }
      const value = val('dateNaissance')
        ? formatDate(val('dateNaissance')) + (ageStr ? ` (${ageStr})` : '')
        : '—';
      return [field.label, value];
    }
    case 'telephone':
      return [field.label, `${val('indicatif')} ${val('telephone')}`.trim() || '—'];
    case 'email':
      return [field.label, val('email') || '—'];
    case 'photo':
      return [field.label, App.photoDataURL ? '__photo__' : 'Non fournie'];
    case 'tel_urgence':
      return [field.label, val('telUrgence') || '—'];
    case 'guardian_name':
      return [field.label, val('guardianName') || '—'];
    case 'guardian_phone':
      return [field.label, val('guardianPhone') || '—'];
    case 'adresse':
      return [field.label, val('adresse') || '—'];
    case 'commune':
      return [field.label, val('commune') || '—'];
    case 'ville':
      return [field.label, val('ville') || '—'];
    case 'eglise':
      return [field.label, val('eglise') || '—'];
    case 'departement':
      return [field.label, val('departement') || '—'];
    case 'hebergement':
      return [field.label, getHebergementValue() || '—'];
    case 'observations':
      return [field.label, getObservationsRecapValue()];
    default:
      return null;
  }
}

/**
 * Récapitulatif par défaut (sans configuration API chargée).
 *
 * @return {Array<object>}
 */
function buildRetraiteRecapSectionModelsLegacy() {
  const roleValue = val('role') || 'Participant';
  const parentOtpChannel = (typeof getParentOtpChannelFromEvent === 'function' && getParentOtpChannelFromEvent() === 'sms')
    ? 'SMS'
    : 'e-mail';
  const sexeLabel = val('sexe') === 'M' ? 'Masculin' : val('sexe') === 'F' ? 'Féminin' : '';

  let ageStr = '';
  const dob = new Date(val('dateNaissance'));
  if (!isNaN(dob.getTime())) {
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
      age--;
    }
    ageStr = age + ' ans';
  }

  return [
    {
      title: 'Identité',
      icon: 'bi-person',
      editStep: 0,
      rows: [
        ['Nom complet', `${val('nom')} ${val('prenom')}`],
        ['Sexe', sexeLabel],
        ['Date de naissance', val('dateNaissance') ? formatDate(val('dateNaissance')) + (ageStr ? ` (${ageStr})` : '') : '—'],
        ['Rôle', roleValue || '—'],
        ['Photo', App.photoDataURL ? '__photo__' : 'Non fournie'],
      ]
    },
    {
      title: 'Coordonnées',
      icon: 'bi-telephone',
      editStep: 1,
      rows: [
        ['Téléphone', val('indicatif') + ' ' + val('telephone')],
        ['Tél. urgence', val('telUrgence') || '—'],
        ['Nom parent / tuteur', val('guardianName') || '—'],
        ['Tél. parent / tuteur', val('guardianPhone') || '—'],
        [
          'Contact parent/tuteur vérifié',
          (document.getElementById('familyMultiChildCheck')?.checked
            ? (App.parentContactVerified ? `Oui (OTP ${parentOtpChannel} validé)` : `Non (OTP ${parentOtpChannel} non validé)`)
            : 'Non applicable')
        ],
        ['Email', val('email')],
        ['Adresse', [val('adresse'), val('commune'), val('ville')].filter(Boolean).join(', ') || '—'],
      ]
    },
    {
      title: 'Participation',
      icon: 'bi-building',
      editStep: 2,
      rows: [
        ['Église', val('eglise')],
        ['Département', val('departement') || '—'],
        ['Hébergement', getHebergementValue() || '—'],
        ['Observations', getObservationsRecapValue()],
      ]
    }
  ];
}

/**
 * Construit les sections du récapitulatif (ordre et visibilité selon la config admin).
 *
 * @return {Array<object>}
 */
function buildRetraiteRecapSectionModels() {
  const hasConfig = App.formFields && Object.keys(App.formFields).length > 0;
  if (!hasConfig) {
    return buildRetraiteRecapSectionModelsLegacy();
  }

  const parentOtpChannel = (typeof getParentOtpChannelFromEvent === 'function' && getParentOtpChannelFromEvent() === 'sms')
    ? 'SMS'
    : 'e-mail';

  const stepMeta = [
    { step: 0, title: 'Identité', icon: 'bi-person', editStep: 0 },
    { step: 1, title: 'Coordonnées', icon: 'bi-telephone', editStep: 1 },
    { step: 2, title: 'Participation', icon: 'bi-building', editStep: 2 },
  ];

  return stepMeta.map((meta) => {
    const processedKeys = new Set();
    const rows = [];

    sortedVisibleFieldsForRecapStep(meta.step).forEach((field) => {
      const row = buildRecapRowForField(field, processedKeys);
      if (row) {
        rows.push(row);
      }
    });

    if (meta.step === 0) {
      rows.push(['Rôle', val('role') || 'Participant']);
    }

    if (meta.step === 1) {
      rows.push([
        'Contact parent/tuteur vérifié',
        (document.getElementById('familyMultiChildCheck')?.checked
          ? (App.parentContactVerified ? `Oui (OTP ${parentOtpChannel} validé)` : `Non (OTP ${parentOtpChannel} non validé)`)
          : 'Non applicable')
      ]);
    }

    return {
      title: meta.title,
      icon: meta.icon,
      editStep: meta.editStep,
      rows,
    };
  }).filter((section) => section.rows.length > 0);
}

function renderRecapSectionsIntoContainer(container, sections, options) {
  const opts = options || {};
  const showEdit = opts.showEdit !== false;
  container.innerHTML = '';

  sections.forEach(section => {
    const el = document.createElement('div');
    el.className = 'recap-section';
    const editBtn = showEdit
      ? `<button type="button" class="recap-edit-btn" onclick="goToEditStep(${section.editStep})">
          <i class="bi bi-pencil"></i> Modifier
        </button>`
      : '';
    el.innerHTML = `
      <div class="recap-section-header">
        <div class="recap-section-title"><i class="bi ${section.icon}"></i> ${section.title}</div>
        ${editBtn}
      </div>
      ${section.rows.map(([label, value]) => {
        if (value === '__photo__') {
          return `<div class="recap-row"><span class="recap-label">${label}</span><span class="recap-value"><img src="${App.photoDataURL}" class="recap-photo-thumb" alt="Photo"></span></div>`;
        }
        return `<div class="recap-row"><span class="recap-label">${label}</span><span class="recap-value">${escapeHtml(value)}</span></div>`;
      }).join('')}
    `;
    container.appendChild(el);
  });
}

window.buildRetraiteRecapSectionModels = buildRetraiteRecapSectionModels;
window.renderRecapSectionsIntoContainer = renderRecapSectionsIntoContainer;

function generateRecap() {
  /* Nouveau passage sur le récap : l’acceptation du règlement doit être reprise */
  App.policiesModalAccepted = false;

  const sections = buildRetraiteRecapSectionModels();

  const container = document.getElementById('recapContent');
  renderRecapSectionsIntoContainer(container, sections, { showEdit: true });

  /* Reset confirm checkbox */
  document.getElementById('confirmCheck').checked = false;
  document.getElementById('submitBtn').disabled = true;
  if (typeof loadPoliciesForRecap === 'function') loadPoliciesForRecap();
}

function recapUpdateSubmitGate() {
  const confirmCheck = document.getElementById('confirmCheck');
  const submitBtn = document.getElementById('submitBtn');

  const policiesOk =
    !(App.policiesGateRequired === true) || App.policiesModalAccepted === true;

  if (confirmCheck) {
    confirmCheck.disabled = !policiesOk;
    if (!policiesOk) confirmCheck.checked = false;
  }

  if (submitBtn) {
    submitBtn.disabled = !(confirmCheck && confirmCheck.checked && policiesOk);
  }
}

window.recapUpdateSubmitGate = recapUpdateSubmitGate;

function formatDate(dateStr) {
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

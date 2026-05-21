/* ═══════════════════════════════════════════
   RECAP GENERATION
═══════════════════════════════════════════ */

function buildRetraiteRecapSectionModels() {
  const roleValue = val('role') || 'Participant';
  const parentOtpChannel = (typeof getParentOtpChannelFromEvent === 'function' && getParentOtpChannelFromEvent() === 'sms')
    ? 'SMS'
    : 'e-mail';
  const sexeLabel = val('sexe') === 'M' ? 'Masculin' : val('sexe') === 'F' ? 'Féminin' : '';

  /* Calculate age */
  let ageStr = '';
  const dob = new Date(val('dateNaissance'));
  if (!isNaN(dob.getTime())) {
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
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
        ['Observations', val('observations') || '—'],
      ]
    }
  ];
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

/* ═══════════════════════════════════════════
   FLATPICKR DATE PICKER INIT
═══════════════════════════════════════════ */

function initFlatpickr() {
  const minEligibleBirthDate = (() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 15);
    return d;
  })();

  const dateNaissanceInput = document.getElementById('dateNaissance');
  const ageDisplay = document.getElementById('ageDisplay');

  if (!dateNaissanceInput) return;

  if (typeof flatpickr === 'undefined') {
    setTimeout(initFlatpickr, 100);
    return;
  }
  flatpickr(dateNaissanceInput, {
    locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.fr ? 'fr' : 'default',
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'j F Y',
    maxDate: minEligibleBirthDate,
    defaultDate: dateNaissanceInput.value || null,
    disableMobile: true,
    allowInput: false,
    animate: true,
    monthSelectorType: 'dropdown',
    prevArrow: '<svg width="10" height="10" viewBox="0 0 10 10"><path d="M7 1L3 5l4 4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    nextArrow: '<svg width="10" height="10" viewBox="0 0 10 10"><path d="M3 1l4 4-4 4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    onChange: function(selectedDates) {
      if (selectedDates.length === 0) {
        if (ageDisplay) ageDisplay.textContent = '';
        return;
      }
      const dob = selectedDates[0];
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const m = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
      if (age < 15) {
        if (ageDisplay) ageDisplay.textContent = 'Âge minimum requis : 15 ans.';
        showFieldError(dateNaissanceInput);
        return;
      }
      if (ageDisplay) ageDisplay.textContent = `${age} ans`;
      clearFieldError(dateNaissanceInput);
    }
  });
}

function initDateFallback() {
  const dateNaissanceInput = document.getElementById('dateNaissance');
  const ageDisplay = document.getElementById('ageDisplay');

  if (!dateNaissanceInput) return;

  dateNaissanceInput.addEventListener('change', () => {
    const dob = new Date(dateNaissanceInput.value);
    if (isNaN(dob.getTime())) {
      if (ageDisplay) ageDisplay.textContent = '';
      return;
    }
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    if (age < 15) {
      if (ageDisplay) ageDisplay.textContent = 'Âge minimum requis : 15 ans.';
      showFieldError(dateNaissanceInput);
      return;
    }
    if (ageDisplay) ageDisplay.textContent = `${age} ans`;
    clearFieldError(dateNaissanceInput);
  });
}

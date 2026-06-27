/* ═══════════════════════════════════════════
   DATE DE NAISSANCE — masque JJ-MM-AAAA + Flatpickr
═══════════════════════════════════════════ */

'use strict';

const BIRTH_DATE_SEP = '-';
const BIRTH_DATE_MIN_AGE = 15;

/**
 * @param {string} str Chaîne brute
 * @return {string} Chiffres uniquement
 */
function birthDateDigitsOnly(str) {
  return String(str || '').replace(/\D/g, '');
}

/**
 * Formate 8 chiffres en JJ-MM-AAAA pendant la saisie.
 *
 * @param {string} digits Chiffres (max 8)
 * @return {string} Date masquée
 */
function formatBirthDateMask(digits) {
  const d = digits.slice(0, 8);
  let out = d.slice(0, 2);

  if (d.length > 2) {
    out += BIRTH_DATE_SEP + d.slice(2, 4);
  }

  if (d.length > 4) {
    out += BIRTH_DATE_SEP + d.slice(4, 8);
  }

  return out;
}

/**
 * Affiche une date en JJ-MM-AAAA.
 *
 * @param {Date} date Date valide
 * @return {string}
 */
function formatBirthDateDisplay(date) {
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();

  return `${day}${BIRTH_DATE_SEP}${month}${BIRTH_DATE_SEP}${year}`;
}

/**
 * Convertit une date en Y-m-d (API Laravel).
 *
 * @param {Date} date Date valide
 * @return {string}
 */
function toIsoBirthDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

/**
 * Parse JJ-MM-AAAA, JJ/MM/AAAA ou Y-m-d.
 *
 * @param {string|null|undefined} raw Valeur saisie
 * @return {Date|null}
 */
function parseBirthDateString(raw) {
  if (!raw) {
    return null;
  }

  const value = String(raw).trim();

  const iso = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
  if (iso) {
    const date = new Date(Number(iso[1]), Number(iso[2]) - 1, Number(iso[3]));

    return Number.isNaN(date.getTime()) ? null : date;
  }

  const fr = /^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/.exec(value);
  if (fr) {
    const day = Number(fr[1]);
    const month = Number(fr[2]);
    const year = Number(fr[3]);
    const date = new Date(year, month - 1, day);

    if (
      date.getFullYear() !== year
      || date.getMonth() !== month - 1
      || date.getDate() !== day
    ) {
      return null;
    }

    return date;
  }

  return null;
}

/**
 * @param {Date} date Date de naissance
 * @return {number} Âge en années
 */
function computeAgeFromBirthDate(date) {
  const today = new Date();
  let age = today.getFullYear() - date.getFullYear();
  const monthDiff = today.getMonth() - date.getMonth();

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < date.getDate())) {
    age--;
  }

  return age;
}

/**
 * Normalise pour l'API (Y-m-d).
 *
 * @param {string|null|undefined} value Valeur du champ
 * @return {string}
 */
function normalizeDateNaissanceForApi(value) {
  const parsed = parseBirthDateString(value);

  return parsed ? toIsoBirthDate(parsed) : String(value || '').trim();
}

window.parseBirthDateString = parseBirthDateString;
window.normalizeDateNaissanceForApi = normalizeDateNaissanceForApi;
window.formatBirthDateDisplay = formatBirthDateDisplay;
window.computeAgeFromBirthDate = computeAgeFromBirthDate;

/**
 * @param {Date|null} date Date parsée
 * @param {HTMLElement} dateNaissanceInput Champ caché Flatpickr
 * @param {HTMLElement|null} ageDisplay Zone d'âge
 * @return {void}
 */
function updateAgeDisplayFromDate(date, dateNaissanceInput, ageDisplay) {
  if (!date) {
    if (ageDisplay) {
      ageDisplay.textContent = '';
    }

    return;
  }

  const age = computeAgeFromBirthDate(date);

  if (age < BIRTH_DATE_MIN_AGE) {
    if (ageDisplay) {
      ageDisplay.textContent = 'Âge minimum requis : 15 ans.';
    }

    if (typeof showFieldError === 'function') {
      showFieldError(dateNaissanceInput);
    }

    return;
  }

  if (ageDisplay) {
    ageDisplay.textContent = `${age} ans`;
  }

  if (typeof clearFieldError === 'function') {
    clearFieldError(dateNaissanceInput);
  }
}

/**
 * @param {HTMLInputElement} altInput Champ visible
 * @param {object} fp Instance Flatpickr
 * @param {HTMLInputElement} hiddenInput Champ Y-m-d
 * @return {void}
 */
function attachBirthDateMask(altInput, fp, hiddenInput) {
  altInput.setAttribute('inputmode', 'numeric');
  altInput.setAttribute('maxlength', '10');
  altInput.setAttribute('autocomplete', 'bday');
  altInput.setAttribute('placeholder', 'JJ-MM-AAAA');
  altInput.setAttribute('aria-describedby', 'ageDisplay');

  altInput.addEventListener('input', () => {
    const digits = birthDateDigitsOnly(altInput.value);
    const formatted = formatBirthDateMask(digits);
    const caretFromEnd = altInput.value.length - (altInput.selectionStart || 0);

    altInput.value = formatted;

    const nextPos = Math.max(0, formatted.length - caretFromEnd);
    try {
      altInput.setSelectionRange(nextPos, nextPos);
    } catch (e) {
      /* ignore */
    }

    if (digits.length === 0) {
      fp.clear(false);
      updateAgeDisplayFromDate(null, hiddenInput, document.getElementById('ageDisplay'));

      return;
    }

    if (digits.length === 8) {
      const parsed = parseBirthDateString(formatted);
      const maxDate = fp.config.maxDate instanceof Date ? fp.config.maxDate : null;

      if (parsed && (!maxDate || parsed <= maxDate)) {
        fp.setDate(parsed, false);
        hiddenInput.value = toIsoBirthDate(parsed);
        updateAgeDisplayFromDate(parsed, hiddenInput, document.getElementById('ageDisplay'));
      }
    }
  });

  altInput.addEventListener('blur', () => {
    const trimmed = (altInput.value || '').trim();

    if (!trimmed) {
      return;
    }

    const parsed = parseBirthDateString(trimmed);

    if (!parsed) {
      if (typeof showFieldError === 'function') {
        showFieldError(hiddenInput);
      }

      return;
    }

    fp.setDate(parsed, true);
    altInput.value = formatBirthDateDisplay(parsed);
    hiddenInput.value = toIsoBirthDate(parsed);
    updateAgeDisplayFromDate(parsed, hiddenInput, document.getElementById('ageDisplay'));
  });
}

/**
 * Centre le calendrier sur mobile / petits écrans.
 *
 * @param {HTMLElement|null} calendar Conteneur Flatpickr
 * @return {void}
 */
function positionBirthDateCalendar(calendar) {
  if (!calendar) {
    return;
  }

  const isCompact = window.matchMedia('(max-width: 768px)').matches
    || window.matchMedia('(max-height: 500px) and (orientation: landscape)').matches;

  if (!isCompact) {
    calendar.classList.remove('flatpickr-mobile-centered');
    calendar.style.removeProperty('position');
    calendar.style.removeProperty('left');
    calendar.style.removeProperty('top');
    calendar.style.removeProperty('right');
    calendar.style.removeProperty('z-index');

    return;
  }

  calendar.classList.add('flatpickr-mobile-centered');

  requestAnimationFrame(() => {
    const width = calendar.offsetWidth || Math.min(window.innerWidth - 24, 320);
    const height = calendar.offsetHeight || 340;
    const left = Math.max(12, (window.innerWidth - width) / 2);
    const top = Math.max(12, (window.innerHeight - height) / 2);

    calendar.style.position = 'fixed';
    calendar.style.left = `${left}px`;
    calendar.style.top = `${top}px`;
    calendar.style.right = 'auto';
    calendar.style.zIndex = '10050';
  });
}

/**
 * @return {Date} Date max (15 ans minimum)
 */
function minEligibleBirthDate() {
  const d = new Date();
  d.setFullYear(d.getFullYear() - BIRTH_DATE_MIN_AGE);

  return d;
}

/**
 * Initialise Flatpickr sur #dateNaissance.
 *
 * @return {void}
 */
function initFlatpickr() {
  const dateNaissanceInput = document.getElementById('dateNaissance');
  const ageDisplay = document.getElementById('ageDisplay');

  if (!dateNaissanceInput) {
    return;
  }

  if (typeof flatpickr === 'undefined') {
    setTimeout(initFlatpickr, 100);

    return;
  }

  if (dateNaissanceInput._flatpickr) {
    dateNaissanceInput._flatpickr.destroy();
  }

  const initialParsed = parseBirthDateString(dateNaissanceInput.value);

  flatpickr(dateNaissanceInput, {
    locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.fr ? 'fr' : 'default',
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd-m-Y',
    altInputClass: 'field-input flatpickr-input flatpickr-birth-input',
    maxDate: minEligibleBirthDate(),
    defaultDate: initialParsed || dateNaissanceInput.value || null,
    disableMobile: true,
    allowInput: true,
    clickOpens: true,
    appendTo: document.body,
    parseDate: (datestr) => parseBirthDateString(datestr),
    formatDate: (date) => formatBirthDateDisplay(date),
    animate: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    monthSelectorType: 'dropdown',
    prevArrow: '<svg width="10" height="10" viewBox="0 0 10 10"><path d="M7 1L3 5l4 4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    nextArrow: '<svg width="10" height="10" viewBox="0 0 10 10"><path d="M3 1l4 4-4 4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    onReady(_selectedDates, _dateStr, fp) {
      if (fp.altInput) {
        attachBirthDateMask(fp.altInput, fp, dateNaissanceInput);

        if (initialParsed) {
          fp.altInput.value = formatBirthDateDisplay(initialParsed);
        }
      }

      if (initialParsed) {
        updateAgeDisplayFromDate(initialParsed, dateNaissanceInput, ageDisplay);
      }
    },
    onOpen(_selectedDates, _dateStr, fp) {
      positionBirthDateCalendar(fp.calendarContainer);

      if (window.matchMedia('(max-width: 768px)').matches) {
        document.body.classList.add('flatpickr-open-mobile');
      }
    },
    onClose() {
      document.body.classList.remove('flatpickr-open-mobile');
    },
    onChange(selectedDates, _dateStr, fp) {
      if (selectedDates.length === 0) {
        updateAgeDisplayFromDate(null, dateNaissanceInput, ageDisplay);

        return;
      }

      const dob = selectedDates[0];

      if (fp.altInput) {
        fp.altInput.value = formatBirthDateDisplay(dob);
      }

      dateNaissanceInput.value = toIsoBirthDate(dob);
      updateAgeDisplayFromDate(dob, dateNaissanceInput, ageDisplay);
    },
  });

  window.addEventListener('resize', () => {
    const fp = dateNaissanceInput._flatpickr;

    if (fp && fp.isOpen) {
      positionBirthDateCalendar(fp.calendarContainer);
    }
  }, { passive: true });
}

/**
 * Fallback si Flatpickr indisponible.
 *
 * @return {void}
 */
function initDateFallback() {
  const dateNaissanceInput = document.getElementById('dateNaissance');
  const ageDisplay = document.getElementById('ageDisplay');

  if (!dateNaissanceInput || dateNaissanceInput._flatpickr) {
    return;
  }

  dateNaissanceInput.setAttribute('placeholder', 'JJ-MM-AAAA');
  dateNaissanceInput.setAttribute('inputmode', 'numeric');
  dateNaissanceInput.setAttribute('maxlength', '10');

  dateNaissanceInput.addEventListener('input', () => {
    const digits = birthDateDigitsOnly(dateNaissanceInput.value);
    dateNaissanceInput.value = formatBirthDateMask(digits);

    if (digits.length === 8) {
      const parsed = parseBirthDateString(dateNaissanceInput.value);

      if (parsed) {
        dateNaissanceInput.dataset.isoValue = toIsoBirthDate(parsed);
        updateAgeDisplayFromDate(parsed, dateNaissanceInput, ageDisplay);
      }
    }
  });

  dateNaissanceInput.addEventListener('change', () => {
    const parsed = parseBirthDateString(dateNaissanceInput.value);

    if (!parsed) {
      if (ageDisplay) {
        ageDisplay.textContent = '';
      }

      return;
    }

    dateNaissanceInput.dataset.isoValue = toIsoBirthDate(parsed);
    updateAgeDisplayFromDate(parsed, dateNaissanceInput, ageDisplay);
  });
}

/**
 * Synchronise Flatpickr après restauration localStorage / préremplissage.
 *
 * @param {string|null|undefined} rawValue Valeur stockée
 * @return {void}
 */
function syncBirthDateFieldFromValue(rawValue) {
  const input = document.getElementById('dateNaissance');

  if (!input || !rawValue) {
    return;
  }

  const parsed = parseBirthDateString(rawValue);

  if (!parsed) {
    input.dispatchEvent(new Event('change'));

    return;
  }

  if (input._flatpickr) {
    input._flatpickr.setDate(parsed, true);

    if (input._flatpickr.altInput) {
      input._flatpickr.altInput.value = formatBirthDateDisplay(parsed);
    }

    updateAgeDisplayFromDate(parsed, input, document.getElementById('ageDisplay'));

    return;
  }

  input.value = formatBirthDateDisplay(parsed);
  input.dataset.isoValue = toIsoBirthDate(parsed);
  input.dispatchEvent(new Event('change'));
}

window.syncBirthDateFieldFromValue = syncBirthDateFieldFromValue;

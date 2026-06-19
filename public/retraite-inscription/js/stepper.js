/* ═══════════════════════════════════════════
   STEPPER NAVIGATION
═══════════════════════════════════════════ */

function isOuvrierRegistrationRole() {
  const role = (val('role') || 'Participant').toLowerCase();
  return role.includes('ouvrier');
}

function goToStep(n) {
  if (App.registrationOpen !== true) return;
  if (n < 0 || n >= App.totalSteps) return;

  const steps = document.querySelectorAll('.step');
  const currentEl = steps[App.currentStep];
  currentEl.style.opacity = '0';
  currentEl.style.transform = 'translateY(-8px)';

  setTimeout(() => {
    currentEl.classList.remove('active');
    currentEl.style.opacity = '';
    currentEl.style.transform = '';

    App.currentStep = n;
    steps[App.currentStep].classList.add('active');
    updateStepper();
    saveState();

    if (n === 3) {
      generateRecap();
    }
    if (n === 4 && typeof window.onEnterPaymentStep === 'function') {
      window.onEnterPaymentStep();
    }

    if (typeof trackRetraiteFunnelForFormStep === 'function') {
      trackRetraiteFunnelForFormStep(n);
    }

    /* Scroll to top of content on mobile */
    const target = window.innerWidth <= 900
      ? document.getElementById('stepperMobile')
      : document.querySelector('.content-area');
    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 80);
}

function nextStep() {
  if (App.registrationOpen !== true) return;
  if (!validateStep(App.currentStep)) return;
  if (App.currentStep === 3) return;
  if (App.currentStep === 4) return;
  goToStep(App.currentStep + 1);
}

function prevStep() {
  if (App.registrationOpen !== true) return;
  goToStep(App.currentStep - 1);
}

/* Go to specific step (used by recap edit buttons) */
function goToEditStep(n) {
  if (App.registrationOpen !== true) return;
  goToStep(n);
}

/* ─── UPDATE STEPPER UI ─── */
function updateStepper() {
  const stepperItems = document.querySelectorAll('.stepper-item');
  const mobileDots = document.querySelectorAll('.stepper-dot');

  /* Desktop stepper */
  stepperItems.forEach((item, i) => {
    item.classList.remove('active', 'completed');
    if (i === App.currentStep) item.classList.add('active');
    else if (i < App.currentStep) item.classList.add('completed');
    const circle = item.querySelector('.stepper-circle');
    circle.innerHTML = i < App.currentStep
      ? '<i class="bi bi-check"></i>'
      : (i + 1);
  });

  /* Mobile stepper */
  const mobileLabel = document.getElementById('mobileStepLabel');
  const mobileCount = document.getElementById('mobileStepCount');
  const mobileProgress = document.getElementById('mobileProgress');

  mobileLabel.textContent = App.stepLabels[App.currentStep];
  mobileCount.textContent = `Étape ${App.currentStep + 1}/${App.totalSteps}`;
  mobileProgress.style.width = `${((App.currentStep + 1) / App.totalSteps) * 100}%`;
  mobileDots.forEach((dot, i) => {
    dot.classList.remove('active', 'completed');
    if (i === App.currentStep) dot.classList.add('active');
    else if (i < App.currentStep) dot.classList.add('completed');
  });
}

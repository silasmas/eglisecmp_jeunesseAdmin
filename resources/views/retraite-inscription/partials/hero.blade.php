{{-- Bannière héro (style Next.js) — lignes dynamiques conservées pour la logique existante --}}
<header class="hero" id="retraiteHero">
  <div class="hero-content">
    <div class="hero-logo-container">
      <img
        src="{{ asset('retraite-inscription/img/logo.jpg') }}"
        alt="Logo Département de la Jeunesse"
        class="hero-logo"
        width="260"
        height="130"
      />
    </div>
    <h1>Grande Retraite <span>de la Jeunesse</span></h1>
    <p class="hero-sub">
      <strong>Centre Missionnaire Philadelphie</strong> · Département de la Jeunesse
    </p>
    <div id="heroThemeLine" class="hero-meta-line hidden"></div>
    <div id="heroSoldOutBar" class="info-box warning hero-sold-out hidden" style="max-width: 520px; margin: 1rem auto 0;">
      <i class="bi bi-slash-circle"></i>
      <span>Les inscriptions en ligne sont closes : nombre de places maximal atteint.</span>
    </div>
    <p id="heroPlacesLine" class="hero-places-muted hidden" style="max-width: 520px; margin: 1rem auto 0; font-weight:500;"></p>
    <div id="heroAuthPortalLine" class="hero-auth-hint hidden" role="note" aria-label="Notifications et accès portail"></div>
    <div class="hero-divider"></div>
  </div>

  <div class="hero-fade" aria-hidden="true"></div>

  <div class="hero-wave" aria-hidden="true">
    <svg viewBox="0 0 1440 70" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <defs>
        <linearGradient id="heroWaveGrad" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="#FFFAF5" stop-opacity="0.9" />
          <stop offset="25%" stop-color="#FFFAF5" stop-opacity="1" />
          <stop offset="75%" stop-color="#FFFAF5" stop-opacity="1" />
          <stop offset="100%" stop-color="#FFFAF5" stop-opacity="0.9" />
        </linearGradient>
      </defs>
      <path
        d="M0,30 C180,70 360,0 540,35 C720,70 900,10 1080,40 C1260,70 1380,20 1440,30 L1440,70 L0,70 Z"
        fill="url(#heroWaveGrad)"
      />
      <path
        d="M0,50 C200,20 400,65 600,45 C800,25 1000,60 1200,40 C1320,28 1400,50 1440,45 L1440,70 L0,70 Z"
        fill="#FFFAF5"
        fill-opacity="0.5"
      />
    </svg>
  </div>
</header>

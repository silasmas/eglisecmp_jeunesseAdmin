{{-- Écran de chargement (splash) — style projet Next.js de référence --}}
<div id="retraiteGateSplash" class="splash hold" aria-hidden="true">
  <div class="splash-bg"></div>

  <div class="splash-particles" aria-hidden="true">
    @foreach ([
      [12, 18, 0], [22, 72, 0.2], [35, 45, 0.4], [48, 85, 0.1], [55, 28, 0.3],
      [68, 62, 0.5], [75, 15, 0.15], [18, 55, 0.35], [82, 38, 0.25], [40, 8, 0.45],
      [62, 78, 0.55], [28, 88, 0.2], [88, 68, 0.4], [8, 42, 0.3], [52, 52, 0.1],
    ] as $particle)
      <span
        class="splash-particle"
        style="--i: {{ $loop->index }}; top: {{ $particle[0] }}%; left: {{ $particle[1] }}%; animation-delay: {{ $particle[2] }}s;"
      ></span>
    @endforeach
  </div>

  <div class="splash-rings" aria-hidden="true">
    <div class="splash-ring splash-ring-1"></div>
    <div class="splash-ring splash-ring-2"></div>
  </div>

  <div class="splash-logo-wrap">
    <img
      src="{{ asset('retraite-inscription/img/logo.jpg') }}"
      alt="Logo Département de la Jeunesse CMP"
      class="splash-logo"
      width="280"
      height="140"
    />
    <div class="splash-logo-halo"></div>
  </div>

  <div class="splash-text">
    <p class="splash-label">Grande Retraite</p>
    <p class="splash-sub">Jeunesse · CMP</p>
  </div>

  <div class="splash-bar-wrap" aria-hidden="true">
    <div class="splash-bar"></div>
  </div>
</div>

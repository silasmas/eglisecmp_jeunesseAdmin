{{-- Portail : splash au chargement, message si inscriptions fermées --}}
<div id="retraiteGateOverlay" class="retraite-gate-overlay" role="dialog" aria-live="polite" aria-labelledby="retraiteGateTitle">
  @include('retraite-inscription.partials.splash-loader')

  <div id="retraiteGateClosed" class="retraite-gate-closed hidden">
    <div class="retraite-gate-card">
      <div class="retraite-gate-icon"><i class="bi bi-calendar-x"></i></div>
      <h2 id="retraiteGateClosedTitle" class="retraite-gate-heading">Inscriptions non disponibles</h2>
      <p id="retraiteGateClosedText" class="retraite-gate-msg">
        Aucune retraite n’est ouverte aux inscriptions : la date de fin est dépassée ou aucun événement retraite n’est configuré. Contactez le département de la jeunesse si besoin.
      </p>
    </div>
  </div>
</div>

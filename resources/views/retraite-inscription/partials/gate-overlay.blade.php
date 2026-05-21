{{-- Bloque tout le flux si aucun événement retraite actif ou durant chargement --}}
<div id="retraiteGateOverlay" class="retraite-gate-overlay" role="dialog" aria-live="polite" aria-labelledby="retraiteGateTitle">
  <div class="retraite-gate-card">
    <div id="retraiteGateLoading" class="retraite-gate-panel">
      <div class="retraite-gate-spinner" aria-hidden="true"></div>
      <p id="retraiteGateTitle" class="retraite-gate-msg">Chargement des inscriptions…</p>
      <p class="retraite-gate-sub">Merci de patienter quelques instants.</p>
    </div>
    <div id="retraiteGateClosed" class="retraite-gate-panel hidden">
      <div class="retraite-gate-icon"><i class="bi bi-calendar-x"></i></div>
      <h2 id="retraiteGateClosedTitle" class="retraite-gate-heading">Inscriptions non disponibles</h2>
      <p id="retraiteGateClosedText" class="retraite-gate-msg">
        Aucun événement retraite ouvert aux inscriptions en ligne pour le moment. Réessayez plus tard ou contactez le département de la jeunesse.
      </p>
    </div>
  </div>
</div>

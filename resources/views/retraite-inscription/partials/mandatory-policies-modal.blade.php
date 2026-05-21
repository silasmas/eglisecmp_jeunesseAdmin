{{-- Modale règlements obligatoires (étape Récapitulatif) --}}
<div id="mandatoryPoliciesModal" class="retraite-modal hidden" aria-hidden="true">
  <div class="retraite-modal-backdrop" id="mandatoryPoliciesModalBackdrop"></div>
  <div class="retraite-modal-panel" role="dialog" aria-labelledby="mandatoryPoliciesModalTitle" aria-modal="true">
    <div class="retraite-modal-head">
      <h3 id="mandatoryPoliciesModalTitle">Règlement et politiques obligatoires</h3>
      <button type="button" class="retraite-modal-close" id="mandatoryPoliciesModalClose" aria-label="Fermer">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <p class="field-hint retraite-modal-intro">
      Faites défiler le texte jusqu’en bas, puis cochez la case pour confirmer votre acceptation. Sans cette étape, vous ne pouvez pas accéder au paiement.
    </p>
    <div id="policiesModalScroll" class="policies-scroll policies-scroll-modal"></div>
    <div class="retraite-modal-footer">
      <label class="retraite-modal-check">
        <input type="checkbox" id="policiesModalAcceptCheck" disabled>
        <span>J’atteste avoir lu l’ensemble du règlement ci-dessus et j’accepte les conditions qui s’appliquent à mon inscription.</span>
      </label>
      <div class="retraite-modal-buttons">
        <button type="button" class="btn btn-prev" id="btnPoliciesModalDismiss">Annuler</button>
        <button type="button" class="btn btn-submit" id="btnPoliciesModalConfirm" disabled>
          <i class="bi bi-check2-circle"></i> Valider mon acceptation
        </button>
      </div>
    </div>
  </div>
</div>

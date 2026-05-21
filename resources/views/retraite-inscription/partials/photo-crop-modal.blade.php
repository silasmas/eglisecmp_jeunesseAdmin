  <div class="photo-crop-modal hidden" id="photoCropModal" aria-hidden="true">
    <div class="photo-crop-backdrop" id="photoCropBackdrop"></div>
    <div class="photo-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="photoCropTitle">
      <div class="photo-crop-header">
        <h3 id="photoCropTitle">Recadrer la photo</h3>
        <button type="button" class="photo-crop-close" id="photoCropClose" aria-label="Fermer">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="photo-crop-body">
        <img id="photoCropImage" alt="Image a recadrer">
      </div>
      <div class="photo-crop-controls">
        <label for="photoCropZoom">Zoom</label>
        <input type="range" id="photoCropZoom" min="0" max="100" value="0">
      </div>
      <div class="photo-crop-actions">
        <button type="button" class="btn btn-outline" id="photoCropCancel">Annuler</button>
        <button type="button" class="btn btn-next" id="photoCropApply">
          Appliquer le recadrage <i class="bi bi-check2"></i>
        </button>
      </div>
    </div>
  </div>

/**
 * Visualiseur preuve de paiement (modales Filament / Livewire).
 * Délégation d'événements : fonctionne même quand le HTML est injecté dynamiquement.
 */
(function () {
  var MIN_SCALE = 0.35;
  var MAX_SCALE = 3;
  var SCALE_STEP = 0.2;

  /**
   * @param {HTMLElement} viewer Racine du visualiseur
   * @return {void}
   */
  function applyTransform(viewer) {
    var stage = viewer.querySelector('[data-cmp-proof-stage]');
    var zoomLabel = viewer.querySelector('[data-cmp-proof-zoom-label]');

    if (!stage) {
      return;
    }

    var rotation = parseInt(viewer.dataset.rotation || '0', 10);
    var scale = parseFloat(viewer.dataset.scale || '1');
    stage.style.transform = 'rotate(' + rotation + 'deg) scale(' + scale + ')';

    if (zoomLabel) {
      zoomLabel.textContent = Math.round(scale * 100) + '%';
    }
  }

  /**
   * @param {HTMLElement} viewer Racine du visualiseur
   * @param {number} delta Variation de zoom
   * @return {void}
   */
  function changeZoom(viewer, delta) {
    var scale = parseFloat(viewer.dataset.scale || '1');
    scale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, +(scale + delta).toFixed(2)));
    viewer.dataset.scale = String(scale);
    applyTransform(viewer);
  }

  /**
   * @param {HTMLElement} viewer Racine du visualiseur
   * @param {string} action Action demandée
   * @return {void}
   */
  function runAction(viewer, action) {
    var rotation = parseInt(viewer.dataset.rotation || '0', 10);
    var viewport = viewer.querySelector('[data-cmp-proof-viewport]');

    if (action === 'rotate-left') {
      viewer.dataset.rotation = String((rotation - 90 + 360) % 360);
      applyTransform(viewer);
      return;
    }

    if (action === 'rotate-right') {
      viewer.dataset.rotation = String((rotation + 90) % 360);
      applyTransform(viewer);
      return;
    }

    if (action === 'zoom-in') {
      changeZoom(viewer, SCALE_STEP);
      return;
    }

    if (action === 'zoom-out') {
      changeZoom(viewer, -SCALE_STEP);
      return;
    }

    if (action === 'reset') {
      viewer.dataset.rotation = '0';
      viewer.dataset.scale = '1';

      if (viewport) {
        viewport.scrollTop = 0;
        viewport.scrollLeft = 0;
      }

      applyTransform(viewer);
    }
  }

  /**
   * @param {HTMLElement} root Élément racine à initialiser
   * @return {void}
   */
  function initViewer(root) {
    if (!root) {
      return;
    }

    if (root.dataset.rotation === undefined || root.dataset.rotation === '') {
      root.dataset.rotation = '0';
    }

    if (root.dataset.scale === undefined || root.dataset.scale === '') {
      root.dataset.scale = '1';
    }

    applyTransform(root);
  }

  /**
   * @return {void}
   */
  function initAllViewers() {
    document.querySelectorAll('[data-cmp-payment-proof-viewer]').forEach(initViewer);
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-cmp-proof-action]');

    if (!button) {
      return;
    }

    var viewer = button.closest('[data-cmp-payment-proof-viewer]');

    if (!viewer) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    initViewer(viewer);
    runAction(viewer, button.getAttribute('data-cmp-proof-action') || '');
  }, true);

  document.addEventListener('wheel', function (event) {
    var viewport = event.target.closest('[data-cmp-proof-viewport]');

    if (!viewport) {
      return;
    }

    var viewer = viewport.closest('[data-cmp-payment-proof-viewer]');

    if (!viewer) {
      return;
    }

    event.preventDefault();
    initViewer(viewer);
    changeZoom(viewer, event.deltaY < 0 ? SCALE_STEP : -SCALE_STEP);
  }, { capture: true, passive: false });

  document.addEventListener('DOMContentLoaded', initAllViewers);
  document.addEventListener('livewire:navigated', initAllViewers);
  document.addEventListener('livewire:init', function () {
    initAllViewers();

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
      window.Livewire.hook('morph.updated', initAllViewers);
      window.Livewire.hook('element.updated', initAllViewers);
    }
  });

  if (document.readyState !== 'loading') {
    initAllViewers();
  }

  if (typeof MutationObserver !== 'undefined') {
    var observerTimer = null;

    var observer = new MutationObserver(function () {
      if (observerTimer !== null) {
        window.clearTimeout(observerTimer);
      }

      observerTimer = window.setTimeout(initAllViewers, 50);
    });

    observer.observe(document.documentElement, {
      childList: true,
      subtree: true,
    });
  }
})();

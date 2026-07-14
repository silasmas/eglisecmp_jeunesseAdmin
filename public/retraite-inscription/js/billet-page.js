'use strict';

/**
 * Page billet intégrée : onglets, QR/export JPG, PDF documents (prototype retraite-jcmp-inscription).
 */
(function () {
  var TICKET_SIZE = { width: 1603, height: 761 };
  var activeTab = 'billet';

  /**
   * @return {string}
   */
  function resolveTicketAsset(data) {
    var bg = document.querySelector('.retreat-ticket-bg');
    if (bg && bg.src) {
      return bg.src;
    }

    return data.ticketAsset || document.body.dataset.ticketAsset || '';
  }

  /**
   * @param {string} src URL image
   * @return {Promise<HTMLImageElement>}
   */
  function loadImage(src) {
    return new Promise(function (resolve, reject) {
      if (!src) {
        reject(new Error('Image source manquante'));
        return;
      }

      var img = new Image();
      img.onload = function () { resolve(img); };
      img.onerror = function () { reject(new Error('Chargement image impossible')); };
      img.src = src;
    });
  }

  /**
   * @param {CanvasRenderingContext2D} ctx Contexte
   * @param {string} payload Données QR
   * @param {number} x Position X
   * @param {number} y Position Y
   * @param {number} size Taille
   * @return {void}
   */
  function drawFallbackQr(ctx, payload, x, y, size) {
    var cells = 29;
    var cell = size / cells;
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x, y, size, size);
    ctx.fillStyle = '#151515';

    function finder(cx, cy) {
      ctx.fillRect(x + cx * cell, y + cy * cell, cell * 7, cell * 7);
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(x + (cx + 1) * cell, y + (cy + 1) * cell, cell * 5, cell * 5);
      ctx.fillStyle = '#151515';
      ctx.fillRect(x + (cx + 2) * cell, y + (cy + 2) * cell, cell * 3, cell * 3);
    }

    finder(1, 1);
    finder(21, 1);
    finder(1, 21);

    var seed = 0;
    var i;
    for (i = 0; i < payload.length; i += 1) {
      seed = (seed * 31 + payload.charCodeAt(i)) >>> 0;
    }

    var row;
    var col;
    for (row = 1; row < cells - 1; row += 1) {
      for (col = 1; col < cells - 1; col += 1) {
        var inFinder = (col < 9 && row < 9) || (col > 19 && row < 9) || (col < 9 && row > 19);
        if (inFinder) {
          continue;
        }
        seed = (seed * 1664525 + 1013904223) >>> 0;
        if ((seed + row + col) % 3 === 0) {
          ctx.fillRect(x + col * cell, y + row * cell, Math.ceil(cell), Math.ceil(cell));
        }
      }
    }
  }

  /**
   * @param {string} payload Contenu QR
   * @param {number} size Taille
   * @return {Promise<string>}
   */
  async function createQrDataUrl(payload, size) {
    size = size || 300;
    var canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;

    if (window.QRCode && typeof window.QRCode.toCanvas === 'function') {
      try {
        await window.QRCode.toCanvas(canvas, payload, {
          width: size,
          margin: 1,
          errorCorrectionLevel: 'M',
          color: { dark: '#151515', light: '#ffffff' },
        });
        return canvas.toDataURL('image/png');
      } catch (e) { /* fallback */ }
    }

    if (window.QRCode && typeof window.QRCode.toDataURL === 'function') {
      try {
        return await window.QRCode.toDataURL(payload, {
          width: size,
          margin: 1,
          errorCorrectionLevel: 'M',
          color: { dark: '#151515', light: '#ffffff' },
        });
      } catch (e) { /* fallback */ }
    }

    drawFallbackQr(canvas.getContext('2d'), payload, 0, 0, size);
    return canvas.toDataURL('image/png');
  }

  /**
   * @param {CanvasRenderingContext2D} ctx Contexte
   * @param {string} text Texte
   * @param {number} x X
   * @param {number} y Y
   * @param {number} maxWidth Largeur max
   * @param {object} options Options
   * @return {void}
   */
  function drawFittedText(ctx, text, x, y, maxWidth, options) {
    options = options || {};
    var value = String(text || '');
    var fontSize = options.maxSize || 42;
    var minSize = options.minSize || 18;
    var weight = options.weight || 800;
    var family = 'Poppins, Arial, sans-serif';

    ctx.fillStyle = options.color || '#ffffff';
    ctx.textAlign = options.align || 'left';
    ctx.textBaseline = options.baseline || 'alphabetic';

    while (fontSize > minSize) {
      ctx.font = weight + ' ' + fontSize + 'px ' + family;
      if (ctx.measureText(value).width <= maxWidth) {
        break;
      }
      fontSize -= 1;
    }

    ctx.font = weight + ' ' + fontSize + 'px ' + family;
    ctx.fillText(value, x, y);
  }

  /**
   * @param {HTMLElement|null} mount Image QR
   * @param {string} payload URL
   * @return {Promise<void>}
   */
  async function renderTicketQr(mount, payload) {
    if (!mount || !payload) {
      return;
    }

    var dataUrl = await createQrDataUrl(payload, 360);
    mount.src = dataUrl;
    mount.alt = 'Code QR du billet';
  }

  /**
   * @param {object} data Données billet
   * @return {Promise<HTMLCanvasElement>}
   */
  async function renderTicketToCanvas(data) {
    if (document.fonts && document.fonts.ready) {
      try {
        await document.fonts.ready;
      } catch (e) { /* ignore */ }
    }

    var ticketAsset = resolveTicketAsset(data);
    var qrPayload = data.qrUrl || data.code || 'billet-cmp';
    var background = await loadImage(ticketAsset);
    var qrImage = await createQrDataUrl(qrPayload, 340).then(loadImage);

    var canvas = document.createElement('canvas');
    canvas.width = TICKET_SIZE.width;
    canvas.height = TICKET_SIZE.height;
    var ctx = canvas.getContext('2d');

    if (!ctx) {
      throw new Error('Canvas indisponible');
    }

    ctx.drawImage(background, 0, 0, canvas.width, canvas.height);
    drawFittedText(ctx, data.name, 145, 646, 510, { maxSize: 38, minSize: 18, weight: 900, color: '#ffffff' });
    drawFittedText(ctx, data.status, 570, 646, 230, { maxSize: 28, minSize: 15, weight: 800, color: '#f97316' });
    drawFittedText(ctx, data.hebergement, 835, 646, 220, { maxSize: 28, minSize: 15, weight: 800, color: '#ffffff' });

    var qrSize = 292;
    var qrPadding = 16;
    var qrOuterSize = qrSize + qrPadding * 2;
    var qrPanelCenterX = 1366;
    var qrX = qrPanelCenterX - qrSize / 2;
    var qrY = 186;

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(qrX - qrPadding, qrY - qrPadding, qrOuterSize, qrOuterSize);
    ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);
    drawFittedText(ctx, data.code, qrPanelCenterX, qrY + qrSize + 58, qrOuterSize + 50, {
      maxSize: 24,
      minSize: 12,
      weight: 800,
      color: '#ffffff',
      align: 'center',
    });

    return canvas;
  }

  /**
   * @param {HTMLElement|null} root Racine ticket
   * @return {void}
   */
  function fitTicketPreviewText(root) {
    if (!root) {
      return;
    }

    root.querySelectorAll('.retreat-ticket-fit').forEach(function (el) {
      el.style.removeProperty('--ticket-fit-size');
      var size = parseFloat(getComputedStyle(el).fontSize);

      while (el.scrollWidth > el.clientWidth && size > 7) {
        size -= 0.5;
        el.style.setProperty('--ticket-fit-size', size + 'px');
      }
    });
  }

  /**
   * @param {string} tab Onglet actif
   * @return {void}
   */
  function setActiveTab(tab) {
    activeTab = tab;

    document.querySelectorAll('[data-billet-tab]').forEach(function (item) {
      item.classList.toggle('is-active', item.getAttribute('data-billet-tab') === tab);
    });

    document.querySelectorAll('[data-billet-panel]').forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-billet-panel') === tab);
    });

    var bar = document.getElementById('billetActionsBar');
    if (bar) {
      bar.classList.remove('is-tab-billet', 'is-tab-reglement', 'is-tab-objets');
      bar.classList.add('is-tab-' + tab);
    }
  }

  /**
   * @return {void}
   */
  function initTabs() {
    document.querySelectorAll('[data-billet-tab]').forEach(function (tab) {
      tab.addEventListener('click', function () {
        setActiveTab(tab.getAttribute('data-billet-tab') || 'billet');
      });
    });
  }

  /**
   * @return {void}
   */
  function patchDocumentAssetPaths() {
    if (!window.RetreatDocuments || !window.RetreatDocuments.identity) {
      return;
    }

    var base = (document.body.dataset.publicBase || '').replace(/\/$/, '');
    var identity = window.RetreatDocuments.identity;

    if (base) {
      identity.logo = base + '/retraite-inscription/img/logo.jpg';
    }
  }

  /**
   * @return {void}
   */
  function mountDocumentPanels() {
    patchDocumentAssetPaths();

    if (window.RetreatBilletDocumentPanels) {
      window.RetreatBilletDocumentPanels.mountReglement('reglementDocumentContent');
      window.RetreatBilletDocumentPanels.mountObjets('objetsDocumentContent');
    }
  }

  /**
   * @param {object} data Données billet
   * @return {void}
   */
  function initTicketDownload(data) {
    var btn = document.getElementById('downloadTicketBtn');
    if (!btn) {
      return;
    }

    btn.addEventListener('click', async function () {
      var original = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> Génération…';

      try {
        var canvas = await renderTicketToCanvas(data);
        await new Promise(function (resolve, reject) {
          canvas.toBlob(function (blob) {
            if (!blob) {
              reject(new Error('Export JPG impossible'));
              return;
            }

            var link = document.createElement('a');
            link.download = 'billet_retraite_' + (data.slug || 'participant') + '.jpg';
            link.href = URL.createObjectURL(blob);
            link.click();
            setTimeout(function () { URL.revokeObjectURL(link.href); }, 500);
            resolve();
          }, 'image/jpeg', 0.95);
        });
      } catch (err) {
        console.error(err);
        alert('Erreur lors de la génération du billet.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  }

  /**
   * @return {void}
   */
  function initPdfDownload() {
    var btn = document.getElementById('downloadPdfBtn');
    if (!btn) {
      return;
    }

    btn.addEventListener('click', async function () {
      var isObjets = activeTab === 'objets';
      var pdfType = isObjets ? 'items' : 'rules';
      var staticPdf = isObjets ? document.body.dataset.objetsPdf : document.body.dataset.reglementPdf;
      var fileName = isObjets ? 'objets_grande_retraite.pdf' : 'reglement_grande_retraite.pdf';

      if (staticPdf) {
        var staticLink = document.createElement('a');
        staticLink.href = staticPdf;
        staticLink.download = fileName;
        staticLink.target = '_blank';
        staticLink.rel = 'noopener';
        staticLink.click();
        return;
      }

      if (window.RetreatDocumentPdf && typeof window.RetreatDocumentPdf.download === 'function') {
        var original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> Génération…';

        try {
          await window.RetreatDocumentPdf.download(pdfType);
        } catch (err) {
          console.error(err);
          alert('Impossible de générer le PDF pour le moment.');
        } finally {
          btn.disabled = false;
          btn.innerHTML = original;
        }
        return;
      }

      alert('Impossible de générer le PDF pour le moment.');
    });
  }

  /**
   * @return {void}
   */
  function initPrintButton() {
    var btn = document.getElementById('billetPrintBtn');
    if (!btn) {
      return;
    }

    btn.addEventListener('click', function () {
      window.print();
    });
  }

  /**
   * @return {void}
   */
  function bootstrap() {
    initTabs();
    initPrintButton();
    mountDocumentPanels();

    var payload = document.getElementById('billetPayload');
    if (!payload) {
      setActiveTab('billet');
      return;
    }

    var data;
    try {
      data = JSON.parse(payload.textContent || '{}');
    } catch (e) {
      setActiveTab('billet');
      return;
    }

    renderTicketQr(document.getElementById('ticketQrImage'), data.qrUrl || data.code);
    fitTicketPreviewText(document.getElementById('ticketPreview'));
    initTicketDownload(data);
    initPdfDownload();
    setActiveTab('billet');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();

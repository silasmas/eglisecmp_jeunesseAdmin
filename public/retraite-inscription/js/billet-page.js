'use strict';

/**
 * Page billet : onglets, QR participant, export JPG/PDF, impression.
 */
(function () {
  var TICKET_SIZE = { width: 1603, height: 761 };
  var activeTab = 'billet';

  /**
   * @param {number} ms Délai maximum
   * @return {Promise<object|null>}
   */
  function waitForQrLibrary(ms) {
    ms = ms || 8000;

    return new Promise(function (resolve) {
      var elapsed = 0;

      function check() {
        if (window.QRCode && typeof window.QRCode.toDataURL === 'function') {
          resolve(window.QRCode);
          return;
        }

        if (elapsed >= ms) {
          resolve(null);
          return;
        }

        elapsed += 50;
        setTimeout(check, 50);
      }

      check();
    });
  }

  /**
   * @param {number} ms Délai maximum
   * @return {Promise<object|null>}
   */
  function waitForHtml2Pdf(ms) {
    ms = ms || 8000;

    return new Promise(function (resolve) {
      var elapsed = 0;

      function check() {
        if (window.html2pdf) {
          resolve(window.html2pdf);
          return;
        }

        if (elapsed >= ms) {
          resolve(null);
          return;
        }

        elapsed += 50;
        setTimeout(check, 50);
      }

      check();
    });
  }

  /**
   * @param {string} src URL de l'image
   * @return {Promise<HTMLImageElement>}
   */
  function loadImage(src) {
    return new Promise(function (resolve, reject) {
      if (!src) {
        reject(new Error('Image source manquante'));
        return;
      }

      fetch(src, { credentials: 'same-origin' })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('fetch failed');
          }

          return response.blob();
        })
        .then(function (blob) {
          var objectUrl = URL.createObjectURL(blob);
          var img = new Image();

          img.onload = function () {
            URL.revokeObjectURL(objectUrl);
            resolve(img);
          };

          img.onerror = function () {
            URL.revokeObjectURL(objectUrl);
            reject(new Error('decode failed'));
          };

          img.src = objectUrl;
        })
        .catch(function () {
          var fallback = new Image();
          fallback.onload = function () { resolve(fallback); };
          fallback.onerror = function () { reject(new Error('load failed: ' + src)); };
          fallback.src = src;
        });
    });
  }

  /**
   * @param {CanvasRenderingContext2D} ctx Contexte canvas
   * @param {string} text Texte à dessiner
   * @param {number} x Position X
   * @param {number} y Position Y
   * @param {number} maxWidth Largeur max
   * @param {object} options Options de style
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
   * @param {string} payload Contenu QR
   * @param {number} size Taille
   * @return {Promise<string>}
   */
  async function createQrDataUrl(payload, size) {
    size = size || 300;
    var QRCodeLib = await waitForQrLibrary();

    if (!QRCodeLib || !payload) {
      return '';
    }

    return QRCodeLib.toDataURL(payload, {
      width: size,
      margin: 1,
      errorCorrectionLevel: 'M',
      color: { dark: '#151515', light: '#ffffff' },
    });
  }

  /**
   * @param {HTMLElement|null} mount Image QR
   * @param {string} payload URL encodée
   * @return {Promise<void>}
   */
  async function renderTicketQr(mount, payload) {
    if (!mount || !payload) {
      return;
    }

    var dataUrl = await createQrDataUrl(payload, 360);
    if (!dataUrl) {
      return;
    }

    mount.src = dataUrl;
    mount.alt = 'QR code billet retraite';
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

    var ticketAsset = data.ticketAsset || document.body.dataset.ticketAsset || '';
    var background = await loadImage(ticketAsset);
    var qrDataUrl = await createQrDataUrl(data.qrUrl, 340);

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

    if (qrDataUrl) {
      var qrImage = await loadImage(qrDataUrl);
      ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);
    }

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
   * @param {HTMLElement|null} root Conteneur ticket
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

    document.querySelectorAll('[data-actions-for]').forEach(function (group) {
      var isActive = group.getAttribute('data-actions-for') === tab;
      group.classList.toggle('is-active', isActive);
      group.hidden = !isActive;
    });
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
  function initPrintButtons() {
    document.querySelectorAll('[data-billet-print]').forEach(function (button) {
      button.addEventListener('click', function () {
        window.print();
      });
    });
  }

  /**
   * @param {HTMLElement} element Zone à exporter
   * @param {string} filename Nom du fichier
   * @param {HTMLButtonElement|null} button Bouton déclencheur
   * @return {Promise<void>}
   */
  async function downloadElementPdf(element, filename, button) {
    var html2pdfLib = await waitForHtml2Pdf();

    if (!element || !html2pdfLib) {
      alert('Impossible de générer le PDF pour le moment.');
      return;
    }

    var original = button ? button.innerHTML : '';
    if (button) {
      button.disabled = true;
      button.innerHTML = 'Génération…';
    }

    try {
      await html2pdfLib()
        .set({
          margin: [8, 8, 8, 8],
          filename: filename,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: {
            scale: 2,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
          },
          jsPDF: {
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait',
          },
          pagebreak: { mode: ['avoid-all', 'css', 'legacy'] },
        })
        .from(element)
        .save();
    } catch (err) {
      alert('Erreur lors de la génération du PDF.');
    } finally {
      if (button) {
        button.disabled = false;
        button.innerHTML = original;
      }
    }
  }

  /**
   * @param {string} slug Slug participant
   * @return {void}
   */
  function initPdfDownloads(slug) {
    document.querySelectorAll('[data-billet-download-pdf]').forEach(function (button) {
      button.addEventListener('click', async function () {
        var kind = button.getAttribute('data-billet-download-pdf') || 'document';
        var areaId = kind === 'objets' ? 'objetsPrintArea' : 'reglementPrintArea';
        var element = document.getElementById(areaId);

        if (!element) {
          alert('Contenu introuvable pour le PDF.');
          return;
        }

        await downloadElementPdf(
          element,
          kind + '_retraite_' + (slug || 'participant') + '.pdf',
          button,
        );
      });
    });
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
      btn.innerHTML = 'Génération…';

      try {
        var canvas = await renderTicketToCanvas(data);

        await new Promise(function (resolve, reject) {
          canvas.toBlob(function (blob) {
            if (!blob) {
              reject(new Error('Blob vide'));
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

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();
    initPrintButtons();

    var payload = document.getElementById('billetPayload');
    if (!payload) {
      return;
    }

    var data;
    try {
      data = JSON.parse(payload.textContent || '{}');
    } catch (e) {
      return;
    }

    var slug = data.slug || document.body.dataset.participantSlug || 'participant';

    renderTicketQr(document.getElementById('ticketQrImage'), data.qrUrl);
    fitTicketPreviewText(document.getElementById('ticketPreview'));
    initTicketDownload(data);
    initPdfDownloads(slug);
    setActiveTab('billet');
  });
})();

'use strict';

/**
 * Page billet : onglets, QR participant, export JPG.
 */
(function () {
  const TICKET_ASSET = document.body.dataset.ticketAsset || '';
  const TICKET_SIZE = { width: 1603, height: 761 };

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

  function loadImage(src) {
    return new Promise(function (resolve, reject) {
      var img = new Image();
      img.onload = function () { resolve(img); };
      img.onerror = reject;
      img.src = src;
    });
  }

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

  async function createQrDataUrl(payload, size) {
    size = size || 300;

    if (window.QRCode && typeof window.QRCode.toDataURL === 'function') {
      return window.QRCode.toDataURL(payload, {
        width: size,
        margin: 1,
        errorCorrectionLevel: 'M',
        color: { dark: '#151515', light: '#ffffff' },
      });
    }

    return '';
  }

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

  async function renderTicketToCanvas(data) {
    if (document.fonts && document.fonts.ready) {
      try {
        await document.fonts.ready;
      } catch (e) { /* ignore */ }
    }

    var background = await loadImage(TICKET_ASSET);
    var qrImage = await createQrDataUrl(data.qrUrl, 340).then(loadImage);

    var canvas = document.createElement('canvas');
    canvas.width = TICKET_SIZE.width;
    canvas.height = TICKET_SIZE.height;
    var ctx = canvas.getContext('2d');
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

  function initTabs() {
    var tabs = document.querySelectorAll('[data-billet-tab]');
    var panels = document.querySelectorAll('[data-billet-panel]');

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = tab.getAttribute('data-billet-tab');
        tabs.forEach(function (item) { item.classList.toggle('is-active', item === tab); });
        panels.forEach(function (panel) {
          panel.classList.toggle('is-active', panel.getAttribute('data-billet-panel') === target);
        });
      });
    });
  }

  function initDownload(data) {
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
        await new Promise(function (resolve) {
          canvas.toBlob(function (blob) {
            if (!blob) {
              resolve();
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
        alert('Erreur lors de la génération du billet.');
      } finally {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTabs();

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

    renderTicketQr(document.getElementById('ticketQrImage'), data.qrUrl);
    fitTicketPreviewText(document.getElementById('ticketPreview'));
    initDownload(data);
  });
})();

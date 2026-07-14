/* ═══════════════════════════════════════════
   TICKET RENDERING & EXPORT
═══════════════════════════════════════════ */
'use strict';

const TICKET_ASSET = 'assets/billet-grande-retraite.jpg';
const TICKET_SIZE = { width: 1603, height: 761 };

function getTicketParticipant() {
  const currentId = sessionStorage.getItem('retraite_current_participant_id');
  const participants = typeof readParticipants === 'function' ? readParticipants() : [];
  const current = participants.find((item) => item.id === currentId);
  if (current) return current;
  if (participants[0]) return participants[0];

  return {
    id: 'RET-0000',
    prenom: 'Nom',
    nom: 'du participant',
    sexe: 'M',
    category: 'participant',
    hebergement: '—',
    eglise: '—',
    telephone: '',
  };
}

function getTicketCode(participant) {
  const seed = [
    participant.id,
    participant.prenom,
    participant.nom,
    participant.email,
    participant.telephone,
  ].filter(Boolean).join('|') || `${Date.now()}`;
  let hash = 0;
  for (let i = 0; i < seed.length; i += 1) {
    hash = ((hash << 5) - hash + seed.charCodeAt(i)) | 0;
  }
  return `GRJ-${Math.abs(hash).toString().slice(0, 6).padStart(6, '0')}`;
}

function getTicketPayload(participant) {
  return [
    'GRJ2026',
    getTicketCode(participant),
    getParticipantBadgeName(participant) || getParticipantFullName(participant),
    getParticipantBadgeCategoryLabel(participant),
    participant.hebergement || '—',
  ].join('|');
}

function ticketEscapeHtml(text) {
  return badgeEscapeHtml(String(text || ''));
}

function loadTicketImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });
}

function drawTicketFittedText(ctx, text, x, y, maxWidth, options = {}) {
  const value = String(text || '');
  let fontSize = options.maxSize || 42;
  const minSize = options.minSize || 18;
  const weight = options.weight || 800;
  const family = 'Poppins, Arial, sans-serif';
  ctx.fillStyle = options.color || '#ffffff';
  ctx.textAlign = options.align || 'left';
  ctx.textBaseline = options.baseline || 'alphabetic';

  while (fontSize > minSize) {
    ctx.font = `${weight} ${fontSize}px ${family}`;
    if (ctx.measureText(value).width <= maxWidth) break;
    fontSize -= 1;
  }

  ctx.font = `${weight} ${fontSize}px ${family}`;
  ctx.fillText(value, x, y);
}

function drawFallbackQr(ctx, payload, x, y, size) {
  const cells = 29;
  const cell = size / cells;
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(x, y, size, size);
  ctx.fillStyle = '#151515';
  const finder = (cx, cy) => {
    ctx.fillRect(x + cx * cell, y + cy * cell, cell * 7, cell * 7);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x + (cx + 1) * cell, y + (cy + 1) * cell, cell * 5, cell * 5);
    ctx.fillStyle = '#151515';
    ctx.fillRect(x + (cx + 2) * cell, y + (cy + 2) * cell, cell * 3, cell * 3);
  };
  finder(1, 1);
  finder(21, 1);
  finder(1, 21);

  let seed = 0;
  for (let i = 0; i < payload.length; i += 1) seed = (seed * 31 + payload.charCodeAt(i)) >>> 0;
  for (let row = 1; row < cells - 1; row += 1) {
    for (let col = 1; col < cells - 1; col += 1) {
      const inFinder = (col < 9 && row < 9) || (col > 19 && row < 9) || (col < 9 && row > 19);
      if (inFinder) continue;
      seed = (seed * 1664525 + 1013904223) >>> 0;
      if ((seed + row + col) % 3 === 0) {
        ctx.fillRect(x + col * cell, y + row * cell, Math.ceil(cell), Math.ceil(cell));
      }
    }
  }
}

async function createTicketQrDataUrl(payload, size = 300) {
  const canvas = document.createElement('canvas');
  canvas.width = size;
  canvas.height = size;

  if (window.QRCode && typeof window.QRCode.toCanvas === 'function') {
    await window.QRCode.toCanvas(canvas, payload, {
      width: size,
      margin: 1,
      errorCorrectionLevel: 'M',
      color: {
        dark: '#151515',
        light: '#ffffff',
      },
    });
  } else {
    drawFallbackQr(canvas.getContext('2d'), payload, 0, 0, size);
  }

  return canvas.toDataURL('image/png');
}

function getTicketViewModel(participant) {
  const name = getParticipantBadgeName(participant) || getParticipantFullName(participant) || 'Nom du participant';
  const role = getParticipantBadgeCategoryLabel(participant);
  const hebergement = participant.hebergement || '—';
  const code = getTicketCode(participant);
  return {
    name,
    role,
    hebergement,
    code,
    payload: getTicketPayload({ ...participant, id: code }),
  };
}

function fitTicketPreviewText(root) {
  const target = typeof root === 'string' ? document.getElementById(root) : root;
  if (!target) return;
  target.querySelectorAll('.retreat-ticket-fit').forEach((el) => {
    el.style.removeProperty('--ticket-fit-size');
    let size = parseFloat(getComputedStyle(el).fontSize);
    while (el.scrollWidth > el.clientWidth && size > 7) {
      size -= 0.5;
      el.style.setProperty('--ticket-fit-size', `${size}px`);
    }
  });
}

async function renderTicketPreview(target, participant) {
  const root = typeof target === 'string' ? document.getElementById(target) : target;
  if (!root) return;
  const data = getTicketViewModel(participant);
  const qr = await createTicketQrDataUrl(data.payload, 360);
  root.__ticketParticipant = { ...participant };
  root.innerHTML = `
    <div class="retreat-ticket">
      <img class="retreat-ticket-bg" src="${TICKET_ASSET}" alt="">
      <div class="retreat-ticket-info">
        <div>
          <span class="retreat-ticket-label">Noms</span>
          <strong class="retreat-ticket-fit">${ticketEscapeHtml(data.name)}</strong>
        </div>
        <div>
          <span class="retreat-ticket-label">Statut</span>
          <strong class="retreat-ticket-fit">${ticketEscapeHtml(data.role)}</strong>
        </div>
        <div>
          <span class="retreat-ticket-label">Hébergement</span>
          <strong class="retreat-ticket-fit">${ticketEscapeHtml(data.hebergement)}</strong>
        </div>
      </div>
      <img class="retreat-ticket-qr" src="${qr}" alt="Code QR du billet">
      <div class="retreat-ticket-qr-code">${ticketEscapeHtml(data.code)}</div>
    </div>
  `;
  requestAnimationFrame(() => fitTicketPreviewText(root));
}

async function renderTicketToCanvas(participant) {
  if (document.fonts && document.fonts.ready) {
    try {
      await document.fonts.ready;
    } catch (e) { /* ignore */ }
  }

  const data = getTicketViewModel(participant);
  const [background, qrImage] = await Promise.all([
    loadTicketImage(TICKET_ASSET),
    createTicketQrDataUrl(data.payload, 340).then(loadTicketImage),
  ]);

  const canvas = document.createElement('canvas');
  canvas.width = TICKET_SIZE.width;
  canvas.height = TICKET_SIZE.height;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(background, 0, 0, canvas.width, canvas.height);

  drawTicketFittedText(ctx, data.name, 145, 646, 510, {
    maxSize: 38,
    minSize: 18,
    weight: 900,
    color: '#ffffff',
  });
  drawTicketFittedText(ctx, data.role, 570, 646, 230, {
    maxSize: 28,
    minSize: 15,
    weight: 800,
    color: '#f97316',
  });
  drawTicketFittedText(ctx, data.hebergement, 835, 646, 220, {
    maxSize: 28,
    minSize: 15,
    weight: 800,
    color: '#ffffff',
  });

  const qrSize = 292;
  const qrPadding = 16;
  const qrOuterSize = qrSize + qrPadding * 2;
  const qrPanelCenterX = 1366;
  const qrX = qrPanelCenterX - qrSize / 2;
  const qrY = 186;
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(qrX - qrPadding, qrY - qrPadding, qrOuterSize, qrOuterSize);
  ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);
  ctx.strokeStyle = 'rgba(255,255,255,0.22)';
  ctx.lineWidth = 2;
  ctx.strokeRect(qrX - qrPadding, qrY - qrPadding, qrOuterSize, qrOuterSize);
  drawTicketFittedText(ctx, data.code, qrPanelCenterX, qrY + qrSize + 58, qrOuterSize + 50, {
    maxSize: 24,
    minSize: 12,
    weight: 800,
    color: '#ffffff',
    align: 'center',
  });

  return canvas;
}

async function downloadTicket() {
  const root = document.getElementById('ticketPreview');
  const btn = document.getElementById('downloadTicketBtn');
  const participant = root.__ticketParticipant || getTicketParticipant();
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Génération...';
  btn.disabled = true;

  try {
    const canvas = await renderTicketToCanvas(participant);
    const name = getBadgeExportName(getParticipantFullName(participant) || getParticipantBadgeName(participant));
    await new Promise((resolve) => {
      canvas.toBlob((blob) => {
        if (!blob) {
          resolve();
          return;
        }
        const link = document.createElement('a');
        link.download = `billet_retraite_${name}.jpg`;
        link.href = URL.createObjectURL(blob);
        link.click();
        setTimeout(() => URL.revokeObjectURL(link.href), 500);
        resolve();
      }, 'image/jpeg', 0.95);
    });
  } catch (err) {
    alert('Erreur lors de la génération du billet.');
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const participant = getTicketParticipant();
  renderTicketPreview('ticketPreview', participant);
  const downloadBtn = document.getElementById('downloadTicketBtn');
  if (downloadBtn) downloadBtn.addEventListener('click', downloadTicket);
});

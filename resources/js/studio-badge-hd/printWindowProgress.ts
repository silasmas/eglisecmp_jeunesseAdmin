export interface PrintWindowProgressMeta {
  current?: number;
  total?: number;
}

const PROGRESS_STYLES = `
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
    font-family: system-ui, sans-serif;
    background: #f8fafc;
    color: #0f172a;
  }
  .print-progress-card {
    width: min(420px, calc(100vw - 2rem));
    padding: 1.5rem 1.35rem;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 16px 48px rgba(15, 23, 42, 0.12);
  }
  .print-progress-card h1 {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
  }
  #print-status {
    font-size: 0.86rem;
    color: #475569;
    margin-bottom: 1rem;
    line-height: 1.45;
  }
  .print-progress-track {
    height: 10px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
  }
  #print-progress-fill {
    height: 100%;
    width: 0%;
    border-radius: inherit;
    background: linear-gradient(90deg, #c01420, #e85d68);
    transition: width 0.2s ease;
  }
  .print-progress-meta {
    display: flex;
    justify-content: space-between;
    margin-top: 0.45rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748b;
  }
  #print-meta:empty { display: none; }
  .print-progress-hint {
    margin-top: 0.85rem;
    font-size: 0.74rem;
    line-height: 1.4;
    color: #94a3b8;
  }
`;

/**
 * Affiche l'écran de progression dans la fenêtre d'impression (au clic, synchrone).
 */
export function showPrintWindowLoading(printWindow: Window): void {
  try {
    printWindow.document.open();
    printWindow.document.write(`<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>Impression badges — CMP Jeunesse</title>
  <style>${PROGRESS_STYLES}</style>
</head>
<body>
  <div class="print-progress-card">
    <h1>Préparation de l&apos;impression</h1>
    <p id="print-status">Démarrage…</p>
    <div class="print-progress-track" aria-hidden="true">
      <div id="print-progress-fill"></div>
    </div>
    <div class="print-progress-meta">
      <span id="print-percent">0%</span>
      <span id="print-meta"></span>
    </div>
    <p class="print-progress-hint">Conseil : dans la boîte d&apos;impression, désactivez «&nbsp;En-têtes et pieds de page&nbsp;» pour un badge par feuille.</p>
  </div>
</body>
</html>`);
    printWindow.document.close();
    updatePrintWindowProgress(printWindow, 0, 'Démarrage…');
  } catch {
    // Fenêtre inaccessible.
  }
}

/**
 * Met à jour la barre de progression dans la fenêtre d'impression.
 */
export function updatePrintWindowProgress(
  printWindow: Window | null,
  percent: number,
  statusLabel: string,
  meta?: PrintWindowProgressMeta,
): void {
  if (!printWindow || printWindow.closed) {
    return;
  }

  try {
    const doc = printWindow.document;
    const safePercent = Math.min(100, Math.max(0, Math.round(percent)));
    const fill = doc.getElementById('print-progress-fill');
    const status = doc.getElementById('print-status');
    const percentEl = doc.getElementById('print-percent');
    const metaEl = doc.getElementById('print-meta');

    if (fill) {
      fill.style.width = `${safePercent}%`;
    }

    if (status) {
      status.textContent = statusLabel;
    }

    if (percentEl) {
      percentEl.textContent = `${safePercent}%`;
    }

    if (metaEl) {
      if (meta?.total && meta.total > 1 && meta.current !== undefined) {
        metaEl.textContent = `${meta.current} / ${meta.total} badge${meta.total > 1 ? 's' : ''}`;
      } else {
        metaEl.textContent = '';
      }
    }
  } catch {
    // Ignore si la fenêtre n'est plus scriptable.
  }
}

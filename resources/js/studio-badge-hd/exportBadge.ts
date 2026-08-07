import {
  canvasToDataUrl,
  captureBadgeElement,
  preloadCaptureEngine,
  type CapturePurpose,
} from './captureBadge';
import { showPrintWindowLoading, updatePrintWindowProgress } from './printWindowProgress';
import type { ExportFormat } from './types';

export type { ExportFormat };
export { preloadCaptureEngine, updatePrintWindowProgress };

export interface PrintPagePayload {
  dataUrl: string;
  label: string;
}

const CANCEL_MAX_PERCENT = 40;

/**
 * Indique si l'utilisateur peut encore annuler selon la progression.
 */
export function isExportCancellable(percent: number): boolean {
  return percent <= CANCEL_MAX_PERCENT;
}

/**
 * Capture le badge affiché dans un canvas haute résolution.
 */
export async function captureBadgeCanvas(
  element: HTMLElement,
  purpose: CapturePurpose = 'export',
): Promise<HTMLCanvasElement> {
  return captureBadgeElement(element, purpose);
}

/**
 * Télécharge le badge au format PNG ou PDF.
 */
export async function downloadBadgeExport(
  canvas: HTMLCanvasElement,
  filenameBase: string,
  format: ExportFormat,
): Promise<void> {
  const safeName = filenameBase.replace(/[^\w\-]+/g, '_');
  const pngDataUrl = canvasToDataUrl(canvas, 'export');

  if (format === 'png' || format === 'zip') {
    if (format === 'zip') {
      await downloadBulkBadgesZip([{ canvas, filenameBase: safeName }], safeName);

      return;
    }

    const link = document.createElement('a');
    link.download = `${safeName}.png`;
    link.href = pngDataUrl;
    link.click();

    return;
  }

  const { jsPDF } = await import('jspdf');
  const width = canvas.width;
  const height = canvas.height;
  const pdf = new jsPDF({
    orientation: width > height ? 'landscape' : 'portrait',
    unit: 'px',
    format: [width, height],
    compress: true,
  });

  pdf.addImage(pngDataUrl, 'PNG', 0, 0, width, height);
  pdf.save(`${safeName}.pdf`);
}

export interface BulkExportItem {
  canvas: HTMLCanvasElement;
  filenameBase: string;
}

const BULK_PNG_DELAY_MS = 400;

/**
 * Télécharge plusieurs badges en un seul PDF (une page par badge).
 */
export async function downloadBulkBadgesPdf(
  items: BulkExportItem[],
  archiveName = 'badges-cmp',
): Promise<void> {
  if (items.length === 0) {
    return;
  }

  const { jsPDF } = await import('jspdf');
  let pdf: InstanceType<typeof jsPDF> | null = null;

  items.forEach((item, index) => {
    const pngDataUrl = canvasToDataUrl(item.canvas, 'export');
    const width = item.canvas.width;
    const height = item.canvas.height;
    const orientation = width > height ? 'landscape' : 'portrait';

    if (index === 0) {
      pdf = new jsPDF({
        orientation,
        unit: 'px',
        format: [width, height],
        compress: true,
      });
      pdf.addImage(pngDataUrl, 'PNG', 0, 0, width, height);

      return;
    }

    if (!pdf) {
      return;
    }

    pdf.addPage([width, height], orientation);
    pdf.addImage(pngDataUrl, 'PNG', 0, 0, width, height);
  });

  pdf?.save(`${archiveName.replace(/[^\w\-]+/g, '_')}.pdf`);
}

/**
 * Télécharge plusieurs badges PNG (délai entre chaque pour éviter le blocage navigateur).
 */
export async function downloadBulkBadgesPng(items: BulkExportItem[]): Promise<void> {
  for (let index = 0; index < items.length; index += 1) {
    const item = items[index];

    await downloadBadgeExport(item.canvas, item.filenameBase, 'png');

    if (index < items.length - 1) {
      await new Promise<void>(resolve => {
        window.setTimeout(resolve, BULK_PNG_DELAY_MS);
      });
    }
  }
}

/**
 * Convertit un data URL en Blob.
 *
 * @param dataUrl Data URL PNG/JPEG
 * @returns Blob correspondant
 */
function dataUrlToBlob(dataUrl: string): Blob {
  const [header, base64] = dataUrl.split(',');
  const mime = header.match(/:(.*?);/)?.[1] || 'image/png';
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);

  for (let index = 0; index < binary.length; index += 1) {
    bytes[index] = binary.charCodeAt(index);
  }

  return new Blob([bytes], { type: mime });
}

/**
 * Télécharge plusieurs badges dans une archive ZIP.
 *
 * @param items Badges rendus
 * @param archiveName Nom du fichier ZIP
 * @returns void
 */
export async function downloadBulkBadgesZip(
  items: BulkExportItem[],
  archiveName = 'badges-cmp',
): Promise<void> {
  if (items.length === 0) {
    return;
  }

  const JSZip = (await import('jszip')).default;
  const zip = new JSZip();

  items.forEach((item, index) => {
    const safeName = item.filenameBase.replace(/[^\w\-]+/g, '_') || `badge-${index + 1}`;
    const pngDataUrl = canvasToDataUrl(item.canvas, 'export');
    zip.file(`${safeName}.png`, dataUrlToBlob(pngDataUrl));
  });

  const blob = await zip.generateAsync({ type: 'blob' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `${archiveName.replace(/[^\w\-]+/g, '_')}.zip`;
  link.click();
  window.setTimeout(() => URL.revokeObjectURL(link.href), 1000);
}

/**
 * Ouvre une fenêtre d'impression de façon synchrone (pendant le clic utilisateur).
 */
export function createPrintPreviewWindow(): Window | null {
  const printWindow = window.open('', '_blank');

  if (!printWindow) {
    return null;
  }

  showPrintWindowLoading(printWindow);

  return printWindow;
}

const escapeHtml = (value: string): string => value
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;');

/**
 * Construit le document HTML complet pour l'impression (une page par badge).
 */
function buildPrintHtmlDocument(pages: PrintPagePayload[]): string {
  const body = pages.map((page, index) => `
    <section class="print-page${index === pages.length - 1 ? ' print-page--last' : ''}">
      <img src="${page.dataUrl}" alt="${escapeHtml(page.label)}" />
    </section>
  `).join('');

  return `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>Badge CMP</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page {
      size: A4 portrait;
      margin: 0;
    }
    html, body {
      width: 100%;
      margin: 0;
      padding: 0;
      background: #fff;
    }
    .print-page {
      width: 210mm;
      height: 297mm;
      max-height: 297mm;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      page-break-after: always;
      break-after: page;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .print-page--last {
      page-break-after: auto;
      break-after: auto;
    }
    .print-page img {
      display: block;
      max-width: 210mm;
      max-height: 297mm;
      width: auto;
      height: auto;
      object-fit: contain;
    }
    @media print {
      html, body {
        width: 210mm;
        height: auto;
      }
      .print-page {
        width: 210mm;
        height: 297mm;
        margin: 0;
        padding: 0;
      }
      .print-page img {
        max-width: 210mm;
        max-height: 297mm;
      }
      body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>
  ${body}
</body>
</html>`;
}

/**
 * Lance window.print() une fois les images chargées dans le document cible.
 */
function triggerPrintWhenImagesReady(targetWindow: Window): void {
  const triggerPrint = (): void => {
    targetWindow.focus();
    targetWindow.print();
  };

  const doc = targetWindow.document;
  const images = Array.from(doc.images);

  if (images.length === 0) {
    window.setTimeout(triggerPrint, 120);

    return;
  }

  let loadedCount = 0;

  const onImageReady = (): void => {
    loadedCount += 1;

    if (loadedCount >= images.length) {
      window.setTimeout(triggerPrint, 100);
    }
  };

  images.forEach(image => {
    if (image.complete) {
      onImageReady();
    } else {
      image.onload = onImageReady;
      image.onerror = onImageReady;
    }
  });
}

/**
 * Impression via iframe cachée (secours si la fenêtre popup est inaccessible).
 */
function printViaHiddenIframe(html: string): void {
  const iframe = document.createElement('iframe');
  iframe.setAttribute('title', 'Aperçu impression badges');
  iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
  document.body.appendChild(iframe);

  const iframeWindow = iframe.contentWindow;

  if (!iframeWindow) {
    document.body.removeChild(iframe);
    throw new Error('Impossible d\'ouvrir l\'aperçu d\'impression.');
  }

  iframeWindow.document.open();
  iframeWindow.document.write(html);
  iframeWindow.document.close();

  triggerPrintWhenImagesReady(iframeWindow);

  const cleanup = (): void => {
    if (iframe.parentNode) {
      document.body.removeChild(iframe);
    }
  };

  iframeWindow.addEventListener('afterprint', cleanup, { once: true });
  window.setTimeout(cleanup, 60000);
}

/**
 * Charge le HTML dans la fenêtre via Blob URL (fiable pour les grosses images base64).
 */
function navigatePrintWindowToHtml(printWindow: Window, html: string): void {
  const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
  const blobUrl = URL.createObjectURL(blob);
  let didStartPrint = false;

  const onLoaded = (): void => {
    if (didStartPrint) {
      return;
    }

    didStartPrint = true;
    URL.revokeObjectURL(blobUrl);
    triggerPrintWhenImagesReady(printWindow);
  };

  printWindow.addEventListener('load', onLoaded, { once: true });
  printWindow.location.replace(blobUrl);

  window.setTimeout(() => {
    if (!didStartPrint && printWindow.document.readyState === 'complete') {
      onLoaded();
    }
  }, 400);
}

export interface WritePrintPreviewOptions {
  onBeforePrint?: () => void;
}

/**
 * Remplit la fenêtre d'impression et lance l'aperçu navigateur (une page par badge).
 */
export function writePrintPreview(
  printWindow: Window | null,
  pages: PrintPagePayload[],
  options: WritePrintPreviewOptions = {},
): void {
  if (pages.length === 0) {
    return;
  }

  options.onBeforePrint?.();

  const html = buildPrintHtmlDocument(pages);

  if (!printWindow || printWindow.closed) {
    printViaHiddenIframe(html);

    return;
  }

  try {
    navigatePrintWindowToHtml(printWindow, html);
  } catch {
    try {
      printWindow.document.open();
      printWindow.document.write(html);
      printWindow.document.close();
      triggerPrintWhenImagesReady(printWindow);
    } catch {
      printViaHiddenIframe(html);
    }
  }
}

/**
 * Ouvre l'aperçu d'impression (fenêtre + contenu).
 */
export function openPrintPreview(pages: PrintPagePayload[]): void {
  const printWindow = createPrintPreviewWindow();

  if (!printWindow) {
    writePrintPreview(null, pages);

    return;
  }

  writePrintPreview(printWindow, pages);
}

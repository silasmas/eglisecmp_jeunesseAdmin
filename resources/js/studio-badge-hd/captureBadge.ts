type Html2CanvasModule = typeof import('html2canvas');

let html2canvasModule: Html2CanvasModule['default'] | null = null;
let html2canvasLoadPromise: Promise<Html2CanvasModule['default']> | null = null;

const CAPTURE_SCALE_SCREEN = 1.25;
const CAPTURE_SCALE_EXPORT = 1.5;

export type CapturePurpose = 'print' | 'export';

/**
 * Précharge html2canvas au montage du studio pour accélérer la première capture.
 */
export function preloadCaptureEngine(): void {
  void loadHtml2Canvas();
}

/**
 * Charge le module html2canvas une seule fois.
 */
async function loadHtml2Canvas(): Promise<Html2CanvasModule['default']> {
  if (html2canvasModule) {
    return html2canvasModule;
  }

  if (!html2canvasLoadPromise) {
    html2canvasLoadPromise = import('html2canvas').then(module => {
      html2canvasModule = module.default;

      return html2canvasModule;
    });
  }

  return html2canvasLoadPromise;
}

/**
 * Attend que toutes les images du badge soient chargées dans le DOM.
 */
export async function waitForBadgeImages(element: HTMLElement | null): Promise<void> {
  if (!element) {
    return;
  }

  const images = Array.from(element.querySelectorAll('img'));

  await Promise.all(images.map(image => new Promise<void>(resolve => {
    if (image.complete && image.naturalWidth > 0) {
      resolve();

      return;
    }

    const finish = (): void => resolve();

    image.addEventListener('load', finish, { once: true });
    image.addEventListener('error', finish, { once: true });

    if (image.complete) {
      finish();
    }
  })));
}

/**
 * Capture le badge affiché dans un canvas (échelle adaptée au usage).
 */
export async function captureBadgeElement(
  element: HTMLElement,
  purpose: CapturePurpose = 'export',
): Promise<HTMLCanvasElement> {
  await waitForBadgeImages(element);

  const html2canvas = await loadHtml2Canvas();
  const scale = purpose === 'print' ? CAPTURE_SCALE_SCREEN : CAPTURE_SCALE_EXPORT;

  return html2canvas(element, {
    backgroundColor: '#ffffff',
    scale,
    useCORS: true,
    allowTaint: false,
    logging: false,
    imageTimeout: 12000,
    removeContainer: true,
    onclone: (clonedDocument: Document) => {
      const clonedBadge = clonedDocument.querySelector('.badge-template');

      if (!clonedBadge) {
        return;
      }

      clonedBadge.querySelectorAll('img').forEach(image => {
        image.style.opacity = '1';
        image.style.filter = 'none';
        image.style.visibility = 'visible';
      });

      clonedDocument.querySelectorAll('.badge-layer-skeleton, .badge-coords-indicator').forEach(node => {
        node.remove();
      });
    },
  });
}

/**
 * Convertit un canvas en data URL (JPEG plus léger pour l'impression).
 */
export function canvasToDataUrl(canvas: HTMLCanvasElement, purpose: CapturePurpose): string {
  if (purpose === 'print') {
    return canvas.toDataURL('image/jpeg', 0.92);
  }

  return canvas.toDataURL('image/png');
}

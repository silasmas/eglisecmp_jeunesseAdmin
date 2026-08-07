import { isExportCancellable } from './exportBadge';

/**
 * Attend deux frames pour que l'overlay affiche 0 % avant la suite du travail.
 */
export function waitNextPaint(): Promise<void> {
  return new Promise(resolve => {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => resolve());
    });
  });
}

/**
 * Calcule le pourcentage pour un job simple (une seule étape).
 */
export function singleStepPercent(step: 'init' | 'prepare' | 'capture' | 'captured' | 'process' | 'done'): number {
  switch (step) {
    case 'init':
      return 0;
    case 'prepare':
      return 5;
    case 'capture':
      return 10;
    case 'captured':
      return 65;
    case 'process':
      return 85;
    case 'done':
      return 100;
    default:
      return 0;
  }
}

/**
 * Calcule le pourcentage linéaire pour un export / impression groupé.
 */
export function bulkLinearPercent(completedItems: number, total: number, phaseOffset = 0): number {
  if (total <= 0) {
    return 0;
  }

  const slice = 100 / total;
  const value = (completedItems + phaseOffset) * slice;

  return Math.min(99, Math.max(0, Math.round(value)));
}

/**
 * @deprecated Utiliser bulkLinearPercent pour une progression lisible dès 0 %.
 */
export function bulkStepPercent(
  index: number,
  total: number,
  phase: 'start' | 'rendered' | 'captured' | 'saved',
): number {
  const phaseOffsets: Record<typeof phase, number> = {
    start: 0,
    rendered: 0.4,
    captured: 0.75,
    saved: 1,
  };

  return bulkLinearPercent(index, total, phaseOffsets[phase] * 0.25);
}

/**
 * Indique si l'annulation reste possible selon le pourcentage courant.
 */
export function jobCancelableAt(percent: number): boolean {
  return isExportCancellable(percent);
}

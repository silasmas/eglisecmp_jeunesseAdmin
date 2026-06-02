import { useEffect, useState } from 'react';
import type { Participant } from './types';

const MIN_SKELETON_MS = 80;

/**
 * Indique si la prévisualisation doit afficher un skeleton (changement de participant ou chargement des images).
 */
export function useBadgePreviewReady(
  activeParticipant: Participant | null,
  templateImage: string,
): boolean {
  const [isPreviewLoading, setIsPreviewLoading] = useState(false);

  useEffect(() => {
    if (!activeParticipant) {
      setIsPreviewLoading(false);

      return;
    }

    setIsPreviewLoading(true);
    let cancelled = false;
    const startedAt = Date.now();

    const preloadImage = (src: string): Promise<void> => new Promise(resolve => {
      if (!src) {
        resolve();

        return;
      }

      const img = new Image();
      img.crossOrigin = 'anonymous';

      const finish = (): void => resolve();

      img.onload = finish;
      img.onerror = finish;
      img.src = src;

      if (img.complete) {
        finish();
      }
    });

    Promise.all([
      preloadImage(templateImage),
      activeParticipant.photoDataURL
        ? preloadImage(activeParticipant.photoDataURL)
        : Promise.resolve(),
    ]).then(() => {
      if (cancelled) {
        return;
      }

      const elapsed = Date.now() - startedAt;
      const remain = Math.max(0, MIN_SKELETON_MS - elapsed);

      window.setTimeout(() => {
        if (!cancelled) {
          requestAnimationFrame(() => {
            if (!cancelled) {
              setIsPreviewLoading(false);
            }
          });
        }
      }, remain);
    });

    return () => {
      cancelled = true;
    };
  }, [activeParticipant?.id, activeParticipant?.photoDataURL, templateImage]);

  return isPreviewLoading;
}

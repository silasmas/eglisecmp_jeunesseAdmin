import { useCallback, useRef, type RefObject } from 'react';
import { waitForBadgeImages } from './captureBadge';
import { waitNextPaint } from './exportProgress';

const POLL_INTERVAL_MS = 25;
const MAX_WAIT_MS = 6000;

/**
 * Attend que le participant demandé soit affiché et que la prévisualisation soit prête.
 */
export function useWaitForPreviewReady(
  isPreviewLoading: boolean,
  activeParticipantId: string,
  badgeRef: RefObject<HTMLDivElement | null>,
): (expectedParticipantId: string) => Promise<void> {
  const isPreviewLoadingRef = useRef(isPreviewLoading);
  const activeParticipantIdRef = useRef(activeParticipantId);

  isPreviewLoadingRef.current = isPreviewLoading;
  activeParticipantIdRef.current = activeParticipantId;

  return useCallback(async (expectedParticipantId: string) => {
    const startedAt = Date.now();
    const deadline = startedAt + MAX_WAIT_MS;

    while (activeParticipantIdRef.current !== expectedParticipantId && Date.now() < deadline) {
      await new Promise<void>(resolve => {
        window.setTimeout(resolve, POLL_INTERVAL_MS);
      });
    }

    let sawLoading = false;

    while (Date.now() < deadline) {
      if (isPreviewLoadingRef.current) {
        sawLoading = true;
      }

      if (sawLoading && !isPreviewLoadingRef.current) {
        break;
      }

      if (!sawLoading && activeParticipantIdRef.current === expectedParticipantId) {
        const elapsed = Date.now() - startedAt;

        if (elapsed >= 100) {
          break;
        }
      }

      await new Promise<void>(resolve => {
        window.setTimeout(resolve, POLL_INTERVAL_MS);
      });
    }

    await waitNextPaint();
    await waitForBadgeImages(badgeRef.current);
    await waitNextPaint();
  }, [badgeRef]);
}

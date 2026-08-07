import { useCallback, useRef, useState } from 'react';
import { canvasToDataUrl, preloadCaptureEngine } from './captureBadge';
import {
  createPrintPreviewWindow,
  downloadBadgeExport,
  downloadBulkBadgesPdf,
  downloadBulkBadgesPng,
  downloadBulkBadgesZip,
  isExportCancellable,
  updatePrintWindowProgress,
  writePrintPreview,
  type BulkExportItem,
  type PrintPagePayload,
} from './exportBadge';
import {
  bulkLinearPercent,
  jobCancelableAt,
  singleStepPercent,
  waitNextPaint,
} from './exportProgress';
import { renderParticipantBadgeCanvas, type RenderBadgeCanvasOptions } from './renderBadgeCanvas';
import type { ExportFormat, ExportJobState, Participant } from './types';

const POPUP_BLOCKED_MESSAGE = 'Autorisez les fenêtres popup pour ce site (icône dans la barre d\'adresse), puis réessayez.';

export type BadgeExportContext = Omit<RenderBadgeCanvasOptions, 'assetBaseUrl'> & {
  participant: Participant;
};

interface UseBadgeExportJobOptions {
  participants: Participant[];
  activeParticipant: Participant | null;
  exportFormat: ExportFormat;
  assetBaseUrl: string;
  /** Résout layout + styles pour un participant (global ou override individuel). */
  resolveExportContext: (participant: Participant) => BadgeExportContext;
  setCaptureMode: (enabled: boolean) => void;
}

/**
 * Gère export, impression et overlay de progression via le moteur canvas HD.
 */
export function useBadgeExportJob({
  participants,
  activeParticipant,
  exportFormat,
  assetBaseUrl,
  resolveExportContext,
  setCaptureMode,
}: UseBadgeExportJobOptions) {
  const [exportJob, setExportJob] = useState<ExportJobState | null>(null);
  const cancelRequestedRef = useRef(false);
  const printWindowRef = useRef<Window | null>(null);

  const isExportLocked = exportJob !== null && !exportJob.finished;

  const shouldAbort = useCallback((percent: number): boolean => {
    return cancelRequestedRef.current && isExportCancellable(percent);
  }, []);

  const patchJob = useCallback((patch: Partial<ExportJobState>) => {
    setExportJob(prev => (prev ? { ...prev, ...patch } : prev));
  }, []);

  const syncPrintWindow = useCallback((
    percent: number,
    statusLabel: string,
    meta?: { current?: number; total?: number },
  ) => {
    updatePrintWindowProgress(printWindowRef.current, percent, statusLabel, meta);
  }, []);

  const setProgress = useCallback((
    percent: number,
    statusLabel: string,
    extra: Partial<ExportJobState> = {},
    printMeta?: { current?: number; total?: number },
  ) => {
    patchJob({
      percent,
      statusLabel,
      cancelable: jobCancelableAt(percent),
      ...extra,
    });

    if (printWindowRef.current) {
      syncPrintWindow(percent, statusLabel, printMeta ?? {
        current: extra.current,
        total: extra.total,
      });
    }
  }, [patchJob, syncPrintWindow]);

  const beginJob = useCallback((
    kind: ExportJobState['kind'],
    mode: ExportJobState['mode'],
    total: number,
    statusLabel: string,
  ) => {
    cancelRequestedRef.current = false;
    setCaptureMode(true);
    preloadCaptureEngine();
    setExportJob({
      kind,
      mode,
      current: 0,
      total,
      percent: 0,
      cancelable: true,
      statusLabel,
    });
    syncPrintWindow(0, statusLabel, { current: 0, total });
  }, [setCaptureMode, syncPrintWindow]);

  const finishJob = useCallback((patch: Partial<ExportJobState> = {}) => {
    const finalPercent = patch.percent ?? 100;
    const finalLabel = patch.statusLabel ?? 'Terminé.';

    if (printWindowRef.current && !printWindowRef.current.closed && !patch.error) {
      syncPrintWindow(finalPercent, finalLabel);
    }

    setExportJob(prev => (prev
      ? {
        ...prev,
        ...patch,
        percent: finalPercent,
        finished: true,
        cancelable: false,
      }
      : prev));

    printWindowRef.current = null;
    setCaptureMode(false);

    window.setTimeout(() => {
      setExportJob(null);
    }, patch.cancelled || patch.error ? 3200 : 1800);
  }, [setCaptureMode, syncPrintWindow]);

  /**
   * Génère le canvas HD d'un participant sans basculer l'aperçu DOM.
   */
  const renderParticipantCanvas = useCallback(async (participant: Participant) => {
    const context = resolveExportContext(participant);

    return renderParticipantBadgeCanvas(participant, {
      assetBaseUrl,
      layout: context.layout,
      nameFontCss: context.nameFontCss,
      nameColor: context.nameColor,
      numberColor: context.numberColor,
      photoShape: context.photoShape,
      showPhoto: context.showPhoto,
      showWorkshop: context.showWorkshop,
      showChambre: context.showChambre,
      categoryStyle: context.categoryStyle,
    });
  }, [assetBaseUrl, resolveExportContext]);

  const exportCurrentBadge = useCallback(async () => {
    if (!activeParticipant || exportJob) {
      return;
    }

    beginJob('download', 'single', 1, 'Préparation de l\'export…');
    await waitNextPaint();
    setProgress(0, 'Préparation de l\'export…');

    try {
      if (shouldAbort(0)) {
        finishJob({ cancelled: true, statusLabel: 'Export annulé.' });

        return;
      }

      setProgress(singleStepPercent('prepare'), 'Initialisation…');

      if (shouldAbort(singleStepPercent('prepare'))) {
        finishJob({ cancelled: true, statusLabel: 'Export annulé.' });

        return;
      }

      setProgress(singleStepPercent('capture'), 'Rendu haute résolution…');

      const canvas = await renderParticipantCanvas(activeParticipant);

      if (shouldAbort(singleStepPercent('capture'))) {
        finishJob({ cancelled: true, statusLabel: 'Export annulé.' });

        return;
      }

      setProgress(singleStepPercent('captured'), `Génération du fichier ${exportFormat.toUpperCase()}…`);

      await downloadBadgeExport(
        canvas,
        `badge-${activeParticipant.prenom}-${activeParticipant.nom}`,
        exportFormat,
      );

      finishJob({ statusLabel: 'Export terminé.', percent: singleStepPercent('done') });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erreur lors de l\'export.';
      finishJob({ error: message, statusLabel: message });
    }
  }, [
    activeParticipant,
    exportJob,
    beginJob,
    shouldAbort,
    setProgress,
    renderParticipantCanvas,
    exportFormat,
    finishJob,
  ]);

  const exportSelectedBadges = useCallback(async (selectedIds: string[]) => {
    if (selectedIds.length === 0 || exportJob) {
      return;
    }

    const total = selectedIds.length;
    const exportItems: BulkExportItem[] = [];

    beginJob('download', 'bulk', total, 'Préparation de l\'export groupé…');
    await waitNextPaint();
    setProgress(0, 'Préparation de l\'export groupé…', { current: 0, total });

    try {
      for (let index = 0; index < selectedIds.length; index += 1) {
        const id = selectedIds[index];
        const percentStart = bulkLinearPercent(index, total, 0);

        if (shouldAbort(percentStart)) {
          finishJob({ cancelled: true, statusLabel: 'Export annulé par l\'utilisateur.' });

          return;
        }

        const participant = participants.find(item => item.id === id);

        setProgress(
          percentStart,
          participant
            ? `Rendu ${index + 1} / ${total} — ${participant.prenom} ${participant.nom}`
            : `Rendu ${index + 1} / ${total}`,
          { current: index, total },
        );

        if (!participant) {
          continue;
        }

        const percentCapture = bulkLinearPercent(index, total, 0.45);

        if (shouldAbort(percentCapture)) {
          finishJob({ cancelled: true, statusLabel: 'Export annulé par l\'utilisateur.' });

          return;
        }

        setProgress(
          percentCapture,
          `Rendu HD ${index + 1} / ${total}…`,
          { current: index, total },
        );

        const canvas = await renderParticipantCanvas(participant);
        exportItems.push({
          canvas,
          filenameBase: `badge-${participant.prenom}-${participant.nom}`,
        });

        setProgress(
          bulkLinearPercent(index + 1, total, 0),
          `Badge ${index + 1} / ${total} généré`,
          { current: index + 1, total },
        );
      }

      if (exportItems.length === 0) {
        finishJob({ error: 'Aucun badge généré.', statusLabel: 'Échec de l\'export groupé.' });

        return;
      }

      setProgress(90, `Génération ${exportFormat.toUpperCase()} (${exportItems.length} badge(s))…`, {
        current: exportItems.length,
        total,
      });

      if (exportFormat === 'pdf') {
        await downloadBulkBadgesPdf(exportItems, `badges-cmp-${exportItems.length}`);
      } else if (exportFormat === 'zip') {
        await downloadBulkBadgesZip(exportItems, `badges-cmp-${exportItems.length}`);
      } else {
        await downloadBulkBadgesPng(exportItems);
      }

      finishJob({
        statusLabel: `${exportItems.length} badge(s) exporté(s) en ${exportFormat.toUpperCase()}.`,
        percent: 100,
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Erreur lors de l\'export groupé.';
      finishJob({ error: message, statusLabel: message });
    }
  }, [
    exportJob,
    beginJob,
    participants,
    setProgress,
    shouldAbort,
    renderParticipantCanvas,
    exportFormat,
    finishJob,
  ]);

  const printCurrentBadge = useCallback(async () => {
    if (!activeParticipant || exportJob) {
      return;
    }

    const printWindow = createPrintPreviewWindow();
    printWindowRef.current = printWindow;

    beginJob('print', 'single', 1, 'Préparation de l\'impression…');
    await waitNextPaint();
    setProgress(0, 'Préparation de l\'impression…', {}, { current: 0, total: 1 });

    if (!printWindow) {
      finishJob({
        error: POPUP_BLOCKED_MESSAGE,
        statusLabel: 'Fenêtre d\'impression bloquée.',
      });

      return;
    }

    try {
      if (shouldAbort(0)) {
        printWindow.close();
        finishJob({ cancelled: true, statusLabel: 'Impression annulée.' });

        return;
      }

      setProgress(singleStepPercent('prepare'), 'Initialisation de l\'aperçu…', {}, { current: 0, total: 1 });

      if (shouldAbort(singleStepPercent('prepare'))) {
        printWindow.close();
        finishJob({ cancelled: true, statusLabel: 'Impression annulée.' });

        return;
      }

      setProgress(singleStepPercent('capture'), 'Rendu haute résolution…', {}, { current: 0, total: 1 });

      const canvas = await renderParticipantCanvas(activeParticipant);

      if (shouldAbort(singleStepPercent('capture'))) {
        printWindow.close();
        finishJob({ cancelled: true, statusLabel: 'Impression annulée.' });

        return;
      }

      const pages: PrintPagePayload[] = [{
        dataUrl: canvasToDataUrl(canvas, 'print'),
        label: `${activeParticipant.prenom} ${activeParticipant.nom}`,
      }];

      setProgress(singleStepPercent('process'), 'Chargement de l\'aperçu d\'impression…', {}, { current: 1, total: 1 });

      writePrintPreview(printWindow, pages, {
        onBeforePrint: () => {
          syncPrintWindow(95, 'Ouverture de la boîte d\'impression…', { current: 1, total: 1 });
        },
      });

      finishJob({ statusLabel: 'Aperçu d\'impression ouvert.', percent: singleStepPercent('done') });
    } catch (error) {
      printWindow.close();
      const message = error instanceof Error ? error.message : 'Erreur lors de l\'impression.';
      finishJob({ error: message, statusLabel: message });
    }
  }, [
    activeParticipant,
    exportJob,
    beginJob,
    shouldAbort,
    setProgress,
    renderParticipantCanvas,
    finishJob,
    syncPrintWindow,
  ]);

  const printSelectedBadges = useCallback(async (selectedIds: string[]) => {
    if (selectedIds.length === 0 || exportJob) {
      return;
    }

    const printWindow = createPrintPreviewWindow();
    printWindowRef.current = printWindow;
    const pages: PrintPagePayload[] = [];
    const total = selectedIds.length;

    beginJob('print', 'bulk', total, 'Préparation de l\'impression groupée…');
    await waitNextPaint();
    setProgress(0, 'Préparation de l\'impression groupée…', { current: 0, total }, { current: 0, total });

    if (!printWindow) {
      finishJob({
        error: POPUP_BLOCKED_MESSAGE,
        statusLabel: 'Fenêtre d\'impression bloquée.',
      });

      return;
    }

    try {
      for (let index = 0; index < selectedIds.length; index += 1) {
        const id = selectedIds[index];
        const percentStart = bulkLinearPercent(index, total, 0);

        if (shouldAbort(percentStart)) {
          printWindow.close();
          finishJob({ cancelled: true, statusLabel: 'Impression annulée par l\'utilisateur.' });

          return;
        }

        const participant = participants.find(item => item.id === id);

        setProgress(
          percentStart,
          participant
            ? `Rendu ${index + 1} / ${total} — ${participant.prenom} ${participant.nom}`
            : `Rendu ${index + 1} / ${total}`,
          { current: index, total },
          { current: index, total },
        );

        if (!participant) {
          continue;
        }

        const percentCapture = bulkLinearPercent(index, total, 0.45);

        if (shouldAbort(percentCapture)) {
          printWindow.close();
          finishJob({ cancelled: true, statusLabel: 'Impression annulée par l\'utilisateur.' });

          return;
        }

        setProgress(
          percentCapture,
          `Rendu HD ${index + 1} / ${total}…`,
          { current: index, total },
          { current: index, total },
        );

        const canvas = await renderParticipantCanvas(participant);
        pages.push({
          dataUrl: canvasToDataUrl(canvas, 'print'),
          label: `${participant.prenom} ${participant.nom}`,
        });

        setProgress(
          bulkLinearPercent(index + 1, total, 0),
          `Badge ${index + 1} / ${total} généré`,
          { current: index + 1, total },
          { current: index + 1, total },
        );
      }

      if (pages.length === 0) {
        printWindow.close();
        finishJob({ error: 'Aucun badge n\'a pu être généré.', statusLabel: 'Échec de la génération.' });

        return;
      }

      if (shouldAbort(39)) {
        printWindow.close();
        finishJob({ cancelled: true, statusLabel: 'Impression annulée.' });

        return;
      }

      setProgress(
        90,
        `Ouverture de l\'aperçu (${pages.length} page${pages.length > 1 ? 's' : ''})…`,
        { current: pages.length, total },
        { current: pages.length, total },
      );

      writePrintPreview(printWindow, pages, {
        onBeforePrint: () => {
          syncPrintWindow(95, 'Ouverture de la boîte d\'impression…', { current: pages.length, total });
        },
      });

      finishJob({
        statusLabel: `Aperçu d\'impression : ${pages.length} page${pages.length > 1 ? 's' : ''}.`,
        percent: 100,
      });
    } catch (error) {
      printWindow.close();
      const message = error instanceof Error ? error.message : 'Erreur lors de l\'impression groupée.';
      finishJob({ error: message, statusLabel: message });
    }
  }, [
    exportJob,
    beginJob,
    participants,
    setProgress,
    shouldAbort,
    renderParticipantCanvas,
    finishJob,
    syncPrintWindow,
  ]);

  const requestCancelExport = useCallback(() => {
    setExportJob(prev => {
      if (!prev || !isExportCancellable(prev.percent) || prev.finished) {
        return prev;
      }

      cancelRequestedRef.current = true;

      return {
        ...prev,
        statusLabel: 'Annulation en cours…',
      };
    });

    syncPrintWindow(
      exportJob?.percent ?? 0,
      'Annulation en cours…',
      { current: exportJob?.current, total: exportJob?.total },
    );
  }, [exportJob, syncPrintWindow]);

  const dismissExportJob = useCallback(() => {
    cancelRequestedRef.current = false;
    setExportJob(null);
  }, []);

  return {
    exportJob,
    isExportLocked,
    exportCurrentBadge,
    exportSelectedBadges,
    printCurrentBadge,
    printSelectedBadges,
    requestCancelExport,
    dismissExportJob,
  };
}

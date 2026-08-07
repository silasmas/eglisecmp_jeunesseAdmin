import type { ExportJobState } from './types';
import { isExportCancellable } from './exportBadge';

interface ExportJobOverlayProps {
  job: ExportJobState;
  onCancel: () => void;
  onDismiss: () => void;
}

/**
 * Overlay bloquant pendant export / impression avec barre de progression.
 */
export default function ExportJobOverlay({ job, onCancel, onDismiss }: ExportJobOverlayProps) {
  const canCancel = !job.finished && isExportCancellable(job.percent);

  const title = job.kind === 'print'
    ? job.mode === 'bulk' ? 'Impression en cours' : 'Préparation de l\'impression'
    : job.mode === 'bulk' ? 'Export en cours' : 'Export du badge';

  return (
    <div className="badge-export-overlay" role="dialog" aria-modal="true" aria-labelledby="badge-export-overlay-title">
      <div className="badge-export-overlay-card">
        <h2 id="badge-export-overlay-title">{title}</h2>
        <p className="badge-export-overlay-status">{job.statusLabel}</p>

        <div className="badge-export-progress-track" aria-hidden="true">
          <div
            className="badge-export-progress-fill"
            style={{ width: `${Math.min(100, Math.max(0, job.percent))}%` }}
          />
        </div>

        <div className="badge-export-progress-meta">
          <span>{job.percent}%</span>
          {job.total > 1 && (
            <span>
              {job.current} / {job.total} badge{job.total > 1 ? 's' : ''}
            </span>
          )}
        </div>

        {!job.finished && canCancel && (
          <p className="badge-export-overlay-hint">
            Vous pouvez annuler tant que la progression n&apos;a pas dépassé 40 %.
          </p>
        )}

        {!job.finished && !canCancel && (
          <p className="badge-export-overlay-hint badge-export-overlay-hint--warn">
            Annulation impossible : plus de 40 % de la progression est atteinte.
          </p>
        )}

        {job.finished && job.cancelled && (
          <p className="badge-export-overlay-hint badge-export-overlay-hint--warn">
            Opération annulée.
          </p>
        )}

        {job.finished && job.error && (
          <p className="badge-export-overlay-hint badge-export-overlay-hint--warn">
            {job.error}
          </p>
        )}

        {job.finished && !job.cancelled && !job.error && (
          <p className="badge-export-overlay-hint badge-export-overlay-hint--ok">
            {job.kind === 'print' ? 'Aperçu d\'impression ouvert.' : 'Téléchargement terminé.'}
          </p>
        )}

        <div className="badge-export-overlay-actions">
          {canCancel && (
            <button type="button" className="badge-tool-btn badge-export-cancel-btn" onClick={onCancel}>
              Annuler
            </button>
          )}
          {job.finished && (
            <button type="button" className="badge-tool-btn primary" onClick={onDismiss}>
              Fermer
            </button>
          )}
        </div>
      </div>
    </div>
  );
}

import { createRoot } from 'react-dom/client';
import BadgeStudioApp from './BadgeStudioApp';
import StudioBadgeErrorBoundary from './StudioBadgeErrorBoundary';

const rootEl = document.getElementById('studio-badge-root');

if (rootEl) {
  const templateUrl = rootEl.dataset.templateUrl || '/assets/studio-badge/badge-participant.png';
  const apiParticipants = rootEl.dataset.apiParticipants || '';

  createRoot(rootEl).render(
    <StudioBadgeErrorBoundary>
      <BadgeStudioApp templateUrl={templateUrl} apiParticipantsUrl={apiParticipants} />
    </StudioBadgeErrorBoundary>,
  );
}

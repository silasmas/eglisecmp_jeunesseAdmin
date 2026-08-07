import { createRoot } from 'react-dom/client';
import BadgeStudioApp from './BadgeStudioApp';
import StudioBadgeErrorBoundary from './StudioBadgeErrorBoundary';

const rootEl = document.getElementById('studio-badge-root');

if (rootEl) {
  const templateUrl = rootEl.dataset.templateUrl || '/assets/studio-badge/composants/fond-badge.png';
  const assetBaseUrl = rootEl.dataset.assetBaseUrl || '/assets/studio-badge';
  const apiParticipants = rootEl.dataset.apiParticipants || '';
  const sessionEventName = rootEl.dataset.sessionEventName || '';
  const sessionUserName = rootEl.dataset.sessionUserName || '';

  createRoot(rootEl).render(
    <StudioBadgeErrorBoundary>
      <BadgeStudioApp
        templateUrl={templateUrl}
        assetBaseUrl={assetBaseUrl}
        apiParticipantsUrl={apiParticipants}
        sessionEventName={sessionEventName}
        sessionUserName={sessionUserName}
      />
    </StudioBadgeErrorBoundary>,
  );
}

import type { ApiParticipant, ParticipantsResponse, StudioSessionContext } from './types';

export interface FetchParticipantsParams {
  search?: string;
  chambre?: string;
  atelier?: string;
  paiementValide?: boolean | null;
}

export interface FetchParticipantsResult {
  participants: ApiParticipant[];
  eventName: string | null;
  total: number;
}

/**
 * Charge le contexte de session (utilisateur + édition courante).
 *
 * @param apiUrl URL /studio-badge/api/session
 * @returns Contexte session
 */
export async function fetchStudioSession(apiUrl: string): Promise<StudioSessionContext> {
  const response = await fetch(apiUrl, {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  });

  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(
      typeof body.message === 'string' ? body.message : 'Session studio indisponible.',
    );
  }

  return (await response.json()) as StudioSessionContext;
}

/**
 * Charge tous les participants (pagination automatique) depuis l'API studio badges.
 * Respecte la session web (cookie) et la retraite opérationnelle côté serveur.
 */
export async function fetchAllParticipants(
  apiUrl: string,
  params: FetchParticipantsParams = {},
): Promise<FetchParticipantsResult> {
  const all: ApiParticipant[] = [];
  let page = 1;
  let lastPage = 1;
  let eventName: string | null = null;
  let total = 0;

  do {
    const url = new URL(apiUrl, window.location.origin);
    url.searchParams.set('page', String(page));
    url.searchParams.set('per_page', '200');
    if (params.search) {
      url.searchParams.set('search', params.search);
    }
    if (params.chambre && params.chambre !== 'all') {
      url.searchParams.set('chambre', params.chambre);
    }
    if (params.atelier && params.atelier !== 'all') {
      url.searchParams.set('atelier', params.atelier);
    }
    if (params.paiementValide === true) {
      url.searchParams.set('paiement_valide', '1');
    } else if (params.paiementValide === false) {
      url.searchParams.set('paiement_valide', '0');
    }

    const response = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    if (response.status === 401 || response.status === 419) {
      throw new Error('Session expirée. Reconnectez-vous depuis le tableau de bord admin.');
    }

    if (!response.ok) {
      const body = await response.json().catch(() => ({}));
      throw new Error(
        typeof body.message === 'string' ? body.message : 'Impossible de charger les participants.',
      );
    }

    const json = (await response.json()) as ParticipantsResponse;
    all.push(...json.data);
    lastPage = json.meta.last_page;
    total = json.meta.total;
    if (json.meta.event_name) {
      eventName = json.meta.event_name;
    }
    page += 1;
  } while (page <= lastPage);

  return {
    participants: all,
    eventName,
    total,
  };
}

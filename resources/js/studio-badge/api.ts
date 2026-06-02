import type { ApiParticipant, ParticipantsResponse } from './types';

export interface FetchParticipantsParams {
  search?: string;
  chambre?: string;
  atelier?: string;
  paiementValide?: boolean | null;
}

/**
 * Charge tous les participants (pagination automatique) depuis l'API studio badges.
 */
export async function fetchAllParticipants(
  apiUrl: string,
  params: FetchParticipantsParams = {},
): Promise<ApiParticipant[]> {
  const all: ApiParticipant[] = [];
  let page = 1;
  let lastPage = 1;

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

    if (!response.ok) {
      const body = await response.json().catch(() => ({}));
      throw new Error(
        typeof body.message === 'string' ? body.message : 'Impossible de charger les participants.',
      );
    }

    const json = (await response.json()) as ParticipantsResponse;
    all.push(...json.data);
    lastPage = json.meta.last_page;
    page += 1;
  } while (page <= lastPage);

  return all;
}

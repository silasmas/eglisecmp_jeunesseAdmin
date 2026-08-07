export type BadgeElement = 'photo' | 'name' | 'atelier' | 'chambre';
export type PhotoShape = 'circle' | 'square';
export type ExportFormat = 'png' | 'pdf' | 'zip';
export type BadgeFrameStyle = 'classic' | 'gradient' | 'ribbon' | 'thin' | 'corners';
export type ExportJobKind = 'download' | 'print';
export type ExportJobMode = 'single' | 'bulk';
export type EditScope = 'global' | 'participant';

export interface LayoutItem {
  x: number;
  y: number;
  w: number;
  h: number;
  font: number;
}

export interface Participant {
  id: string;
  prenom: string;
  nom: string;
  photoColor: string;
  photoDataURL?: string;
  chambre: string;
  atelier: number;
  source?: string;
  /** Sexe brut (M/F/homme/femme…) pour libellés catégorie. */
  sexe?: string | null;
  /** Rôle métier pour catégorie badge. */
  role?: string | null;
  /** Clé catégorie normalisée (optionnelle). */
  category?: string | null;
}

export interface ApiParticipant {
  id: string;
  prenom: string;
  nom: string;
  photoUrl: string | null;
  chambre: string;
  atelier: number;
  paiementValide: boolean;
  source: string;
  sexe?: string | null;
  role?: string | null;
}

export interface ParticipantsResponse {
  data: ApiParticipant[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    event_id?: number | string | null;
    event_name?: string | null;
  };
}

/** Contexte session renvoyé par /studio-badge/api/session. */
export interface StudioSessionContext {
  user: {
    id: number | string | null;
    name: string | null;
    email: string | null;
  };
  event: {
    id: number | string;
    name: string;
  } | null;
  participants_total: number;
}

/** État de l'overlay d'export / impression (progression et annulation). */
export interface ExportJobState {
  kind: ExportJobKind;
  mode: ExportJobMode;
  current: number;
  total: number;
  percent: number;
  cancelable: boolean;
  statusLabel: string;
  finished?: boolean;
  cancelled?: boolean;
  error?: string;
}

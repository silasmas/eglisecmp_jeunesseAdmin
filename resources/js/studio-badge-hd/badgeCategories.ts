export type BadgeCategoryKey =
  | 'participant'
  | 'accueil'
  | 'intercession'
  | 'securite'
  | 'restauration'
  | 'technique'
  | 'hygiene'
  | 'crea'
  | 'sante'
  | 'event'
  | 'encadrants';

export type BadgeCategoryStyle = import('./types').BadgeFrameStyle;

/** Styles de cadre disponibles dans le studio. */
export const BADGE_CATEGORY_STYLES: Record<BadgeCategoryStyle, { label: string }> = {
  classic: { label: 'Classique' },
  gradient: { label: 'Dégradé' },
  ribbon: { label: 'Ruban' },
  thin: { label: 'Bordure fine' },
  corners: { label: 'Équerres' },
};

export interface BadgeCategory {
  label: string;
  color: string;
  style: BadgeCategoryStyle;
}

const DEFAULT_STYLE: BadgeCategoryStyle = 'classic';

/** Catalogue des catégories / rôles affichés sur le badge. */
export const BADGE_CATEGORIES: Record<BadgeCategoryKey, BadgeCategory> = {
  participant: { label: 'Participant(e)', color: '#4B5563', style: DEFAULT_STYLE },
  accueil: { label: 'Accueil', color: '#2563EB', style: DEFAULT_STYLE },
  intercession: { label: 'Intercession', color: '#7C3AED', style: DEFAULT_STYLE },
  securite: { label: 'Sécurité', color: '#DC2626', style: DEFAULT_STYLE },
  restauration: { label: 'Restauration', color: '#F97316', style: DEFAULT_STYLE },
  technique: { label: 'Technique', color: '#0F766E', style: DEFAULT_STYLE },
  hygiene: { label: 'Hygiène', color: '#06B6D4', style: DEFAULT_STYLE },
  crea: { label: 'CREA', color: '#DB2777', style: DEFAULT_STYLE },
  sante: { label: 'Santé', color: '#16A34A', style: DEFAULT_STYLE },
  event: { label: 'Event', color: '#EAB308', style: DEFAULT_STYLE },
  encadrants: { label: 'Encadrant(e)', color: '#991B1B', style: DEFAULT_STYLE },
};

/**
 * Normalise un rôle métier vers une clé de catégorie badge.
 *
 * @param value Rôle ou libellé libre
 * @returns Clé de catégorie
 */
export function normalizeCategoryKey(value: string | null | undefined): BadgeCategoryKey {
  const raw = String(value || '').toLowerCase();

  if (raw.includes('accueil')) {
    return 'accueil';
  }
  if (raw.includes('intercession')) {
    return 'intercession';
  }
  if (raw.includes('sécurité') || raw.includes('securite')) {
    return 'securite';
  }
  if (raw.includes('restauration')) {
    return 'restauration';
  }
  if (raw.includes('technique')) {
    return 'technique';
  }
  if (raw.includes('hygiène') || raw.includes('hygiene')) {
    return 'hygiene';
  }
  if (raw.includes('crea') || raw.includes('créa')) {
    return 'crea';
  }
  if (raw.includes('santé') || raw.includes('sante')) {
    return 'sante';
  }
  if (raw.includes('event') || raw.includes('événement') || raw.includes('evenement')) {
    return 'event';
  }
  if (raw.includes('encadr') || raw.includes('ouvrier') || raw.includes('staff') || raw.includes('responsable')) {
    return 'encadrants';
  }

  return 'participant';
}

/**
 * Retourne la définition d'une catégorie.
 *
 * @param key Clé catégorie
 * @returns Métadonnées affichage
 */
export function getBadgeCategory(key: string | null | undefined): BadgeCategory {
  const normalized = normalizeCategoryKey(key);

  return BADGE_CATEGORIES[normalized] ?? BADGE_CATEGORIES.participant;
}

/**
 * Libellé catégorie adapté au sexe (Participant / Participante…).
 *
 * @param categoryKey Clé catégorie
 * @param sexe Sexe brut (M/F/homme/femme…)
 * @returns Libellé à afficher
 */
export function getCategoryLabelForParticipant(
  categoryKey: string | null | undefined,
  sexe: string | null | undefined,
): string {
  const key = normalizeCategoryKey(categoryKey);
  const sexeNorm = String(sexe || '').toLowerCase();
  const isFemale = ['f', 'femme', 'female', 'féminin', 'feminin'].includes(sexeNorm);

  if (key === 'participant') {
    return isFemale ? 'Participante' : 'Participant';
  }

  if (key === 'encadrants') {
    return isFemale ? 'Encadrante' : 'Encadrant';
  }

  return getBadgeCategory(key).label;
}

/** Surcharge locale du titre badge (studio uniquement, non persistée en base). */
export interface BadgeTitleOverride {
  categoryKey: BadgeCategoryKey;
  /** Vide = libellé automatique selon catégorie + sexe. */
  customLabel: string;
}

/**
 * Résout la catégorie et le libellé affiché (auto ou override studio).
 *
 * @param roleOrCategory Rôle API / catégorie
 * @param sexe Sexe participant
 * @param override Surcharge studio optionnelle
 */
export function resolveBadgeTitle(
  roleOrCategory: string | null | undefined,
  sexe: string | null | undefined,
  override?: BadgeTitleOverride | null,
): { categoryKey: BadgeCategoryKey; label: string } {
  const categoryKey = override?.categoryKey ?? normalizeCategoryKey(roleOrCategory);
  const custom = String(override?.customLabel || '').trim();

  return {
    categoryKey,
    label: custom !== ''
      ? custom
      : getCategoryLabelForParticipant(categoryKey, sexe),
  };
}

/** Options du sélecteur de titre (clé → libellé catalogue). */
export function badgeCategorySelectOptions(): Array<{ value: BadgeCategoryKey; label: string }> {
  return (Object.keys(BADGE_CATEGORIES) as BadgeCategoryKey[]).map(key => ({
    value: key,
    label: BADGE_CATEGORIES[key].label,
  }));
}

/**
 * Assombrit ou éclaircit une couleur hex.
 *
 * @param hex Couleur #RRGGBB
 * @param percent Négatif = plus sombre
 * @returns Couleur résultante
 */
export function shadeBadgeColor(hex: string, percent: number): string {
  const value = String(hex || '').replace('#', '');

  if (!/^[0-9a-fA-F]{6}$/.test(value)) {
    return hex;
  }

  const num = Number.parseInt(value, 16);
  const target = percent < 0 ? 0 : 255;
  const ratio = Math.min(1, Math.abs(percent) / 100);
  const channel = (shift: number): number => {
    const base = (num >> shift) & 255;

    return Math.round((target - base) * ratio + base);
  };
  const toHex = (component: number): string => component.toString(16).padStart(2, '0');

  return `#${toHex(channel(16))}${toHex(channel(8))}${toHex(channel(0))}`;
}

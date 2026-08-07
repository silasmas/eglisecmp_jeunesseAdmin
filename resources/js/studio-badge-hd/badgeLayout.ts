import { BADGE_RATIO_H, BADGE_RATIO_W } from './badgeAssets';
import type { BadgeElement, LayoutItem, PhotoShape } from './types';

export type BadgeFontKey =
  | 'poppins'
  | 'montserrat'
  | 'bebas'
  | 'anton'
  | 'oswald'
  | 'archivo'
  | 'bungee'
  | 'playfair';

export interface BadgeFontDefinition {
  label: string;
  family: string;
  weight: number;
}

/** Polices disponibles pour le nom (alignées badgecmp). */
export const BADGE_FONTS: Record<BadgeFontKey, BadgeFontDefinition> = {
  poppins: { label: 'Poppins Bold', family: "'Poppins', Arial, sans-serif", weight: 800 },
  montserrat: { label: 'Montserrat Black', family: "'Montserrat', Arial, sans-serif", weight: 900 },
  bebas: { label: 'Bebas Neue', family: "'Bebas Neue', Impact, sans-serif", weight: 400 },
  anton: { label: 'Anton', family: "'Anton', Impact, sans-serif", weight: 400 },
  oswald: { label: 'Oswald', family: "'Oswald', Arial, sans-serif", weight: 700 },
  archivo: { label: 'Archivo Black', family: "'Archivo Black', Arial, sans-serif", weight: 400 },
  bungee: { label: 'Bungee', family: "'Bungee', Impact, sans-serif", weight: 400 },
  playfair: { label: 'Playfair Display', family: "'Playfair Display', Georgia, serif", weight: 800 },
};

export interface CanvasLayoutItem {
  x: number;
  y: number;
  w: number;
  scale?: number;
  font?: BadgeFontKey;
  color?: string;
  shape?: 'round' | 'square';
}

export type CanvasLayout = Record<'photo' | 'name' | 'category' | 'atelier' | 'chambre', CanvasLayoutItem>;

/**
 * Retourne la définition de police pour une clé.
 *
 * @param key Clé police
 * @returns Définition canvas / CSS
 */
export function getBadgeFont(key: string | null | undefined): BadgeFontDefinition {
  if (key && key in BADGE_FONTS) {
    return BADGE_FONTS[key as BadgeFontKey];
  }

  return BADGE_FONTS.poppins;
}

/**
 * Mappe une valeur CSS de police du studio vers une clé badgecmp.
 *
 * @param cssFamily Valeur CSS (FONT_OPTIONS)
 * @returns Clé police canvas
 */
export function resolveFontKeyFromCss(cssFamily: string): BadgeFontKey {
  const value = cssFamily.toLowerCase();

  if (value.includes('montserrat')) {
    return 'montserrat';
  }
  if (value.includes('bebas')) {
    return 'bebas';
  }
  if (value.includes('anton')) {
    return 'anton';
  }
  if (value.includes('oswald')) {
    return 'oswald';
  }
  if (value.includes('archivo')) {
    return 'archivo';
  }
  if (value.includes('bungee')) {
    return 'bungee';
  }
  if (value.includes('playfair')) {
    return 'playfair';
  }

  return 'poppins';
}

/**
 * Hauteur d'un élément en % de la hauteur du badge (modèle badgecmp).
 *
 * @param element Nom d'élément
 * @param widthPct Largeur en %
 * @returns Hauteur en %
 */
export function getBadgeElementHeightPct(element: string, widthPct: number): number {
  if (element === 'photo') {
    return widthPct * (BADGE_RATIO_W / BADGE_RATIO_H);
  }

  if (element === 'name') {
    return 8.1;
  }

  if (element === 'category') {
    return 3.8;
  }

  return widthPct * (491 / 426) * (BADGE_RATIO_W / BADGE_RATIO_H);
}

/**
 * Convertit le layout studio (coin haut-gauche) vers le modèle canvas (centre X).
 *
 * @param studioLayout Layout édité dans le studio React
 * @param options Style courant (police, couleurs, forme photo)
 * @returns Layout prêt pour renderBadgeCanvas
 */
export function studioLayoutToCanvasLayout(
  studioLayout: Record<BadgeElement, LayoutItem>,
  options: {
    nameFontCss: string;
    nameColor: string;
    numberColor: string;
    photoShape: PhotoShape;
  },
): CanvasLayout {
  const fontKey = resolveFontKeyFromCss(options.nameFontCss);
  const photoShape = options.photoShape === 'circle' ? 'round' : 'square';

  const toCentered = (item: LayoutItem, scaleFromFont = 100): CanvasLayoutItem => ({
    x: item.x + item.w / 2,
    y: item.y,
    w: item.w,
    scale: Math.max(30, Math.min(300, (item.font / 34) * scaleFromFont)),
  });

  const name = toCentered(studioLayout.name, 100);
  const atelier = toCentered(studioLayout.atelier, 100);
  const chambre = toCentered(studioLayout.chambre, 100);
  const photo = toCentered(studioLayout.photo, 100);

  return {
    photo: {
      ...photo,
      w: studioLayout.photo.w,
      shape: photoShape,
    },
    name: {
      ...name,
      font: fontKey,
      color: options.nameColor,
    },
    category: {
      x: 50,
      y: studioLayout.name.y + studioLayout.name.h + 1.2,
      w: 39.6,
      scale: 100,
    },
    atelier: {
      ...atelier,
      color: options.numberColor,
    },
    chambre: {
      ...chambre,
      color: options.numberColor,
    },
  };
}

/**
 * Layout de base badgecmp converti en layout studio (pour INITIAL_LAYOUT).
 *
 * @returns Positions par défaut calées sur fond-badge.png
 */
export function getStudioInitialLayoutFromBadgecmp(): Record<BadgeElement, LayoutItem> {
  const photoW = 34;
  const photoH = getBadgeElementHeightPct('photo', photoW);
  const nameW = 70.2;
  const nameH = 8.1;
  const assignW = 17.3;
  const assignH = getBadgeElementHeightPct('atelier', assignW);

  return {
    photo: {
      x: Number((50 - photoW / 2).toFixed(1)),
      y: 32.65,
      w: photoW,
      h: Number(photoH.toFixed(1)),
      font: 42,
    },
    name: {
      x: Number((50 - nameW / 2).toFixed(1)),
      y: 58.2,
      w: nameW,
      h: nameH,
      font: 34,
    },
    atelier: {
      x: Number((38.75 - assignW / 2).toFixed(1)),
      y: 72.75,
      w: assignW,
      h: Number(assignH.toFixed(1)),
      font: 30,
    },
    chambre: {
      x: Number((61.25 - assignW / 2).toFixed(1)),
      y: 72.75,
      w: assignW,
      h: Number(assignH.toFixed(1)),
      font: 30,
    },
  };
}

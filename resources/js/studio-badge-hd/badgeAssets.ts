/**
 * Chemins publics des composants badge issus de badgecmp.
 *
 * @param assetBaseUrl Préfixe public (ex. /assets/studio-badge)
 * @returns URLs des images de composition
 */
export function getBadgeComponentUrls(assetBaseUrl: string): {
  background: string;
  nameBanner: string;
  atelierBanner: string;
  chambreBanner: string;
} {
  const base = assetBaseUrl.replace(/\/$/, '');

  return {
    background: `${base}/composants/fond-badge.png`,
    nameBanner: `${base}/composants/nom-badge.png`,
    atelierBanner: `${base}/composants/Atelier.png`,
    chambreBanner: `${base}/composants/Chambre.png`,
  };
}

/** Ratio badge A4 300 dpi (moteur badgecmp). */
export const BADGE_RATIO_W = 2480;
export const BADGE_RATIO_H = 3508;

/** Tailles de texte de base en % de la largeur du badge. */
export const BADGE_TEXT_BASE = {
  name: 4.35,
  category: 2.9,
  assignment: 5.1,
} as const;

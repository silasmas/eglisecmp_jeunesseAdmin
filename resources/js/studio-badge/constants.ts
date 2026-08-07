import { getStudioInitialLayoutFromBadgecmp } from './badgeLayout';
import type { BadgeElement, LayoutItem } from './types';

export const COLORS = ['#8B3A2B', '#255C63', '#9A6A1F', '#4C3B8F', '#7A2848', '#1F6B45', '#2E4C7E'];

export const FONT_OPTIONS = [
  { label: 'Poppins Bold', value: 'Poppins, system-ui, sans-serif' },
  { label: 'Montserrat Black', value: 'Montserrat, Poppins, Arial, sans-serif' },
  { label: 'Bebas Neue', value: 'Bebas Neue, Impact, sans-serif' },
  { label: 'Anton', value: 'Anton, Impact, sans-serif' },
  { label: 'Oswald', value: 'Oswald, Arial Narrow, sans-serif' },
  { label: 'Archivo Black', value: 'Archivo Black, Arial, sans-serif' },
  { label: 'Roboto Condensed', value: 'Roboto Condensed, Arial Narrow, sans-serif' },
  { label: 'Inter Black', value: 'Inter, Arial, sans-serif' },
  { label: 'Raleway ExtraBold', value: 'Raleway, Poppins, sans-serif' },
  { label: 'Playfair Display', value: 'Playfair Display, Georgia, serif' },
  { label: 'Bungee', value: 'Bungee, Impact, sans-serif' },
  { label: 'Impact', value: 'Impact, Haettenschweiler, Arial Narrow Bold, sans-serif' },
  { label: 'Arial Black', value: 'Arial Black, Arial, sans-serif' },
];

export const PHOTO_SHAPES = [
  { label: 'Rond', value: 'circle' as const },
  { label: 'Carré', value: 'square' as const },
];

export const TEXT_COLORS = [
  { label: 'Noir', value: '#1c1c1c' },
  { label: 'Blanc', value: '#ffffff' },
  { label: 'Rouge', value: '#c01420' },
  { label: 'Or', value: '#c9973e' },
  { label: 'Bleu', value: '#1a3a6b' },
];

/** Positions par défaut calées sur fond-badge.png (moteur badgecmp). */
export const INITIAL_LAYOUT: Record<BadgeElement, LayoutItem> = getStudioInitialLayoutFromBadgecmp();

export const ELEMENT_LABELS: Record<BadgeElement, string> = {
  photo: 'Photo',
  name: 'Nom complet',
  atelier: 'Atelier',
  chambre: 'Chambre',
};

export const ELEMENT_ICONS: Record<BadgeElement, string> = {
  photo: '📷',
  name: '✏️',
  atelier: '🎨',
  chambre: '🏠',
};

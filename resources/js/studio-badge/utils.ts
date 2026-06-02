import { COLORS } from './constants';
import type { ApiParticipant, BadgeElement, LayoutItem, Participant } from './types';
import type { CSSProperties } from 'react';

/**
 * Convertit un participant API en modèle UI du studio.
 */
export function mapApiParticipant(item: ApiParticipant, index: number): Participant {
  const seed = item.id.split('').reduce((sum, char) => sum + char.charCodeAt(0), 0) + index;

  return {
    id: item.id,
    prenom: item.prenom || 'Participant',
    nom: item.nom || 'CMP',
    photoColor: COLORS[seed % COLORS.length],
    photoDataURL: item.photoUrl || undefined,
    chambre: item.chambre || '—',
    atelier: item.atelier > 0 ? item.atelier : 0,
    source: item.source,
  };
}

export function getInitials(participant: Participant): string {
  return `${participant.prenom.charAt(0)}${participant.nom.charAt(0)}`.toUpperCase();
}

export function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}

export function itemStyle(item: LayoutItem, badgeWidth: number): CSSProperties {
  const scale = badgeWidth / 1000;

  return {
    left: `${item.x}%`,
    top: `${item.y}%`,
    width: `${item.w}%`,
    height: `${item.h}%`,
    fontSize: `${item.font * scale}px`,
  };
}

export function copyLayout(source: Record<BadgeElement, LayoutItem>): Record<BadgeElement, LayoutItem> {
  return {
    photo: { ...source.photo },
    name: { ...source.name },
    atelier: { ...source.atelier },
    chambre: { ...source.chambre },
  };
}

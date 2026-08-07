import type { CSSProperties } from 'react';
import { itemStyle } from './utils';
import type { LayoutItem } from './types';

interface BadgeLayerSkeletonProps {
  layout: LayoutItem;
  badgeWidth: number;
  variant?: 'photo' | 'text' | 'number';
  photoShape?: 'circle' | 'square';
}

/**
 * Placeholder animé pendant le chargement d'un calque du badge.
 */
export default function BadgeLayerSkeleton({
  layout,
  badgeWidth,
  variant = 'text',
  photoShape = 'square',
}: BadgeLayerSkeletonProps) {
  const shapeClass = variant === 'photo' ? `photo-shape-${photoShape}` : '';

  return (
    <div
      className={`badge-layer badge-layer-skeleton ${shapeClass}`.trim()}
      style={itemStyle(layout, badgeWidth)}
      aria-hidden="true"
    >
      <span className={`badge-skeleton-block badge-skeleton-block--${variant}`} />
    </div>
  );
}

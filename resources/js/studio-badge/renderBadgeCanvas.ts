import { BADGE_RATIO_H, BADGE_RATIO_W, BADGE_TEXT_BASE, getBadgeComponentUrls } from './badgeAssets';
import {
  getBadgeCategory,
  getCategoryLabelForParticipant,
  normalizeCategoryKey,
  shadeBadgeColor,
  type BadgeCategoryStyle,
} from './badgeCategories';
import {
  getBadgeFont,
  studioLayoutToCanvasLayout,
  type CanvasLayout,
} from './badgeLayout';
import type { BadgeElement, LayoutItem, Participant, PhotoShape } from './types';

export interface RenderBadgeCanvasOptions {
  /** Préfixe public des assets studio-badge */
  assetBaseUrl: string;
  layout: Record<BadgeElement, LayoutItem>;
  nameFontCss: string;
  nameColor: string;
  numberColor: string;
  photoShape: PhotoShape;
  showPhoto?: boolean;
  showWorkshop?: boolean;
  showChambre?: boolean;
  categoryStyle?: BadgeCategoryStyle;
}

const imageCache = new Map<string, HTMLImageElement>();

/**
 * Charge une image (avec cache mémoire) pour le rendu canvas.
 *
 * @param src URL de l'image
 * @returns Image décodée
 */
async function loadBadgeImage(src: string): Promise<HTMLImageElement> {
  const cached = imageCache.get(src);

  if (cached && cached.complete && cached.naturalWidth > 0) {
    return cached;
  }

  const img = await new Promise<HTMLImageElement>((resolve, reject) => {
    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.onload = () => resolve(image);
    image.onerror = () => reject(new Error(`Impossible de charger l'image: ${src}`));
    image.src = src;
  });

  imageCache.set(src, img);

  return img;
}

/**
 * Dessine une image en mode cover dans un rectangle.
 */
function drawCoverImage(
  ctx: CanvasRenderingContext2D,
  img: HTMLImageElement,
  x: number,
  y: number,
  width: number,
  height: number,
): void {
  const scale = Math.max(width / img.naturalWidth, height / img.naturalHeight);
  const drawWidth = img.naturalWidth * scale;
  const drawHeight = img.naturalHeight * scale;
  const drawX = x + (width - drawWidth) / 2;
  const drawY = y + (height - drawHeight) / 2;
  ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
}

/**
 * Trace un rectangle aux coins arrondis.
 */
function drawRoundRect(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  width: number,
  height: number,
  radius: number,
): void {
  const r = Math.min(radius, width / 2, height / 2);
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.lineTo(x + width - r, y);
  ctx.quadraticCurveTo(x + width, y, x + width, y + r);
  ctx.lineTo(x + width, y + height - r);
  ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
  ctx.lineTo(x + r, y + height);
  ctx.quadraticCurveTo(x, y + height, x, y + height - r);
  ctx.lineTo(x, y + r);
  ctx.quadraticCurveTo(x, y, x + r, y);
  ctx.closePath();
}

/**
 * Trace une pastille arrondie uniquement en bas.
 */
function drawBottomPill(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  width: number,
  height: number,
  radius: number,
): void {
  const r = Math.min(radius, width / 2, height);
  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.lineTo(x + width, y);
  ctx.lineTo(x + width, y + height - r);
  ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
  ctx.lineTo(x + r, y + height);
  ctx.quadraticCurveTo(x, y + height, x, y + height - r);
  ctx.closePath();
}

/**
 * Dessine un texte centré ajusté à la largeur disponible.
 */
function drawFittedText(
  ctx: CanvasRenderingContext2D,
  text: string,
  x: number,
  y: number,
  width: number,
  height: number,
  options: {
    maxSize: number;
    minSize?: number;
    weight?: number;
    family?: string;
    color?: string;
    yOffset?: number;
  },
): void {
  const value = String(text || '');
  let fontSize = options.maxSize || 96;
  const minSize = options.minSize || 34;
  const weight = options.weight || 800;
  const family = options.family || 'Poppins, Arial, sans-serif';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = options.color || '#ffffff';

  while (fontSize > minSize) {
    ctx.font = `${weight} ${fontSize}px ${family}`;
    if (ctx.measureText(value).width <= width) {
      break;
    }
    fontSize -= 2;
  }

  ctx.font = `${weight} ${fontSize}px ${family}`;
  ctx.fillText(value, x + width / 2, y + height / 2 + (options.yOffset || 0));
}

/**
 * Placeholder avatar si aucune photo n'est disponible.
 */
function drawAvatarPlaceholder(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  diameter: number,
): void {
  const radius = diameter / 2;
  ctx.save();
  ctx.beginPath();
  ctx.arc(x + radius, y + radius, radius, 0, Math.PI * 2);
  ctx.clip();
  ctx.fillStyle = '#252525';
  ctx.fillRect(x, y, diameter, diameter);
  ctx.fillStyle = '#8f8f8f';
  ctx.beginPath();
  ctx.arc(x + radius, y + diameter * 0.36, diameter * 0.16, 0, Math.PI * 2);
  ctx.fill();
  ctx.beginPath();
  ctx.ellipse(x + radius, y + diameter * 0.74, diameter * 0.28, diameter * 0.17, 0, 0, Math.PI * 2);
  ctx.fill();
  ctx.restore();
}

/**
 * Rend un badge participant en canvas haute résolution (moteur badgecmp).
 *
 * @param participant Participant studio
 * @param options Layout, styles et assets
 * @returns Canvas prêt à l'export / impression
 */
export async function renderParticipantBadgeCanvas(
  participant: Participant,
  options: RenderBadgeCanvasOptions,
): Promise<HTMLCanvasElement> {
  if (document.fonts?.ready) {
    try {
      await document.fonts.ready;
    } catch {
      /* ignore */
    }
  }

  const categoryKey = normalizeCategoryKey(participant.role || participant.category);
  const category = getBadgeCategory(categoryKey);
  const badgeStyle: BadgeCategoryStyle = options.categoryStyle || category.style;
  const showPhoto = options.showPhoto !== false;
  const isEncadrant = categoryKey === 'encadrants';
  const showWorkshop = options.showWorkshop !== false;
  const showChambre = !isEncadrant && options.showChambre !== false;
  const showAssignments = showWorkshop || showChambre;
  const fullName = `${participant.prenom} ${participant.nom}`.trim() || 'Nom du participant';
  const categoryLabel = getCategoryLabelForParticipant(categoryKey, participant.sexe);
  const atelierValue = showWorkshop && participant.atelier > 0 ? String(participant.atelier) : '';
  const chambreValue = showChambre && participant.chambre && participant.chambre !== '—'
    ? participant.chambre
    : '';

  const layout: CanvasLayout = studioLayoutToCanvasLayout(options.layout, {
    nameFontCss: options.nameFontCss,
    nameColor: options.nameColor,
    numberColor: options.numberColor,
    photoShape: options.photoShape,
  });
  const nameFont = getBadgeFont(layout.name.font);
  const assets = getBadgeComponentUrls(options.assetBaseUrl);

  const badgeWidth = BADGE_RATIO_W;
  const badgeHeight = BADGE_RATIO_H;
  const padding = 150;
  const canvas = document.createElement('canvas');
  canvas.width = badgeWidth + padding * 2;
  canvas.height = badgeHeight + padding * 2;
  const ctx = canvas.getContext('2d');

  if (!ctx) {
    throw new Error('Canvas 2D indisponible.');
  }

  const x0 = padding;
  const y0 = padding;

  const [background, nameBanner, atelierBanner, chambreBanner, photo] = await Promise.all([
    loadBadgeImage(assets.background),
    loadBadgeImage(assets.nameBanner),
    loadBadgeImage(assets.atelierBanner),
    loadBadgeImage(assets.chambreBanner),
    showPhoto && participant.photoDataURL
      ? loadBadgeImage(participant.photoDataURL).catch(() => null)
      : Promise.resolve(null),
  ]);

  const categoryDark = shadeBadgeColor(category.color, -38);
  const makeCategoryGradient = (gx: number, gy: number, gw: number, gh: number) => {
    const gradient = ctx.createLinearGradient(gx, gy, gx + gw, gy + gh);
    gradient.addColorStop(0, category.color);
    gradient.addColorStop(1, categoryDark);

    return gradient;
  };

  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.drawImage(background, x0, y0, badgeWidth, badgeHeight);

  const isClassic = badgeStyle === 'classic';

  if (isClassic) {
    ctx.save();
    ctx.globalAlpha = 0.08;
    ctx.globalCompositeOperation = 'multiply';
    ctx.fillStyle = category.color;
    ctx.fillRect(x0, y0, badgeWidth, badgeHeight);
    ctx.restore();

    const borderX = x0 + badgeWidth * 0.0365;
    const borderY = y0 + badgeHeight * 0.0285;
    const borderW = badgeWidth * (1 - 0.0365 * 2);
    const borderH = badgeHeight * (1 - 0.0285 * 2);
    const borderOffset = 68;
    ctx.strokeStyle = category.color;
    ctx.lineWidth = 124;
    ctx.strokeRect(
      borderX - borderOffset,
      borderY - borderOffset,
      borderW + borderOffset * 2,
      borderH + borderOffset * 2,
    );
  }

  if (badgeStyle === 'gradient') {
    const baselineW = badgeWidth * 0.6;
    const baselineH = badgeHeight * 0.0055;
    const baselineX = x0 + (badgeWidth - baselineW) / 2;
    const baselineY = y0 + badgeHeight * (1 - 0.014) - baselineH;
    ctx.fillStyle = makeCategoryGradient(baselineX, baselineY, baselineW, 0);
    drawRoundRect(ctx, baselineX, baselineY, baselineW, baselineH, baselineH / 2);
    ctx.fill();
  }

  if (showPhoto) {
    const photoD = badgeWidth * (layout.photo.w / 100);
    const photoX = x0 + badgeWidth * (layout.photo.x / 100) - photoD / 2;
    const photoY = y0 + badgeHeight * (layout.photo.y / 100);
    const photoBorder = Math.round(photoD * 0.045);
    const isSquare = layout.photo.shape === 'square';
    const squareRadius = photoD * 0.1;
    const clipPhotoShape = (): void => {
      ctx.beginPath();
      if (isSquare) {
        drawRoundRect(ctx, photoX, photoY, photoD, photoD, squareRadius);
      } else {
        ctx.arc(photoX + photoD / 2, photoY + photoD / 2, photoD / 2, 0, Math.PI * 2);
      }
    };

    ctx.save();
    clipPhotoShape();
    ctx.clip();
    ctx.fillStyle = '#252525';
    ctx.fillRect(photoX, photoY, photoD, photoD);

    if (photo) {
      drawCoverImage(
        ctx,
        photo,
        photoX + photoBorder,
        photoY + photoBorder,
        photoD - photoBorder * 2,
        photoD - photoBorder * 2,
      );
    } else {
      drawAvatarPlaceholder(ctx, photoX + photoBorder, photoY + photoBorder, photoD - photoBorder * 2);
    }

    ctx.restore();
    ctx.strokeStyle = category.color;
    ctx.lineWidth = photoBorder;
    ctx.beginPath();
    if (isSquare) {
      drawRoundRect(
        ctx,
        photoX + photoBorder / 2,
        photoY + photoBorder / 2,
        photoD - photoBorder,
        photoD - photoBorder,
        squareRadius,
      );
    } else {
      ctx.arc(
        photoX + photoD / 2,
        photoY + photoD / 2,
        photoD / 2 - photoBorder / 2,
        0,
        Math.PI * 2,
      );
    }
    ctx.stroke();
  }

  const nameW = badgeWidth * (layout.name.w / 100);
  const nameX = x0 + badgeWidth * (layout.name.x / 100) - nameW / 2;
  const nameY = y0 + badgeHeight * (layout.name.y / 100);
  const nameH = badgeHeight * 0.081;
  const nameMax = badgeWidth * (BADGE_TEXT_BASE.name / 100) * ((layout.name.scale || 100) / 100);
  ctx.drawImage(nameBanner, nameX, nameY, nameW, nameH);
  drawFittedText(ctx, fullName, nameX + nameW * 0.085, nameY + nameH * 0.18, nameW * 0.83, nameH * 0.6, {
    maxSize: Math.round(nameMax),
    minSize: 40,
    weight: nameFont.weight,
    family: nameFont.family,
    color: layout.name.color || '#ffffff',
  });

  const catW = badgeWidth * (layout.category.w / 100);
  const catX = x0 + badgeWidth * (layout.category.x / 100) - catW / 2;
  const catY = y0 + badgeHeight * (layout.category.y / 100);
  const catH = badgeHeight * 0.038;
  const catMax = badgeWidth * (BADGE_TEXT_BASE.category / 100) * ((layout.category.scale || 100) / 100);
  ctx.fillStyle = isClassic ? category.color : makeCategoryGradient(catX, catY, catW, catH * 3);
  drawBottomPill(ctx, catX, catY, catW, catH, catH / 2);
  ctx.fill();
  drawFittedText(ctx, categoryLabel, catX + catW * 0.08, catY, catW * 0.84, catH, {
    maxSize: Math.round(catMax),
    minSize: 34,
    weight: 800,
    color: '#ffffff',
  });

  if (showAssignments) {
    const drawAssignment = (
      banner: HTMLImageElement,
      value: string,
      itemLayout: CanvasLayout['atelier'],
    ): void => {
      if (!value) {
        return;
      }

      const assignmentW = badgeWidth * (itemLayout.w / 100);
      const assignmentH = assignmentW * (491 / 426);
      const assignmentX = x0 + badgeWidth * (itemLayout.x / 100) - assignmentW / 2;
      const assignmentY = y0 + badgeHeight * (itemLayout.y / 100);
      const maxSize = badgeWidth * (BADGE_TEXT_BASE.assignment / 100) * ((itemLayout.scale || 100) / 100);
      ctx.drawImage(banner, assignmentX, assignmentY, assignmentW, assignmentH);
      drawFittedText(
        ctx,
        value,
        assignmentX + assignmentW * 0.17,
        assignmentY + assignmentH * 0.384,
        assignmentW * 0.66,
        assignmentH * 0.3,
        {
          maxSize: Math.round(maxSize),
          minSize: 44,
          weight: 900,
          color: itemLayout.color || '#373737',
        },
      );
    };

    if (showWorkshop) {
      drawAssignment(atelierBanner, atelierValue, layout.atelier);
    }
    if (showChambre) {
      drawAssignment(chambreBanner, chambreValue, layout.chambre);
    }
  }

  if (badgeStyle === 'thin') {
    const inset = badgeWidth * 0.035;
    const fx = x0 + inset;
    const fy = y0 + badgeHeight * 0.035;
    const fw = badgeWidth - inset * 2;
    const fh = badgeHeight - badgeHeight * 0.035 * 2;
    ctx.strokeStyle = category.color;
    ctx.lineWidth = badgeWidth * 0.011;
    drawRoundRect(ctx, fx, fy, fw, fh, badgeWidth * 0.028);
    ctx.stroke();
  } else if (badgeStyle === 'corners') {
    const inset = badgeWidth * 0.04;
    const armX = badgeWidth * 0.15;
    const armY = badgeHeight * 0.11;
    const thick = badgeWidth * 0.022;
    ctx.strokeStyle = category.color;
    ctx.lineWidth = thick;
    ctx.lineCap = 'round';
    const bracket = (cx: number, cy: number, dirX: number, dirY: number): void => {
      ctx.beginPath();
      ctx.moveTo(cx, cy + dirY * armY);
      ctx.lineTo(cx, cy);
      ctx.lineTo(cx + dirX * armX, cy);
      ctx.stroke();
    };
    bracket(x0 + inset, y0 + inset, 1, 1);
    bracket(x0 + badgeWidth - inset, y0 + inset, -1, 1);
    bracket(x0 + inset, y0 + badgeHeight - inset, 1, -1);
    bracket(x0 + badgeWidth - inset, y0 + badgeHeight - inset, -1, -1);
    ctx.lineCap = 'butt';
  } else if (badgeStyle === 'ribbon') {
    const side = badgeWidth * 0.42;
    const cornerX = x0 + badgeWidth;
    const cornerY = y0;
    const inner = side * 0.5;
    const outer = side * 0.86;
    ctx.save();
    ctx.beginPath();
    ctx.moveTo(cornerX - inner, cornerY);
    ctx.lineTo(cornerX - outer, cornerY);
    ctx.lineTo(cornerX, cornerY + outer);
    ctx.lineTo(cornerX, cornerY + inner);
    ctx.closePath();
    const grad = ctx.createLinearGradient(cornerX - outer, cornerY, cornerX, cornerY + outer);
    grad.addColorStop(0, category.color);
    grad.addColorStop(1, categoryDark);
    ctx.fillStyle = grad;
    ctx.fill();
    const centerDist = (inner + outer) / 2;
    ctx.translate(cornerX - centerDist / 2, cornerY + centerDist / 2);
    ctx.rotate(Math.PI / 4);
    const bandWidth = (outer - inner) / Math.SQRT2;
    drawFittedText(ctx, category.label.toUpperCase(), -side / 2, -bandWidth / 2, side, bandWidth, {
      maxSize: Math.round(bandWidth * 0.5),
      minSize: 24,
      weight: 800,
      color: '#ffffff',
    });
    ctx.restore();
  }

  return canvas;
}

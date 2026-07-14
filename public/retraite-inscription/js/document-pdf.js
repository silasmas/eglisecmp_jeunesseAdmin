/* ═══════════════════════════════════════════
   VECTOR PDF GENERATION FOR RETREAT DOCUMENTS
═══════════════════════════════════════════ */
'use strict';

(function initRetreatDocumentPdf() {
  const COLORS = {
    ink: [37, 25, 27],
    muted: [111, 102, 103],
    faint: [246, 244, 241],
    border: [230, 224, 218],
    accent: [183, 121, 47],
    accentLight: [244, 233, 218],
    danger: [198, 40, 40],
    success: [46, 125, 50],
    paper: [255, 255, 255],
  };

  const PAGE = { width: 210, height: 297, margin: 16 };
  const HEADER_HEIGHT = 44;
  const iconCache = new Map();

  function ensurePdf() {
    if (!window.jspdf || !window.jspdf.jsPDF) {
      alert('Le module PDF est encore en chargement. Veuillez réessayer.');
      return null;
    }
    return new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
  }

  function loadImageDataUrl(src) {
    return fetch(src)
      .then((response) => {
        if (!response.ok) throw new Error(`Image introuvable: ${src}`);
        return response.blob();
      })
      .then((blob) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      }))
      .catch(() => null);
  }

  function imageSizeFromDataUrl(dataUrl) {
    return new Promise((resolve) => {
      if (!dataUrl) {
        resolve(null);
        return;
      }
      const img = new Image();
      img.onload = () => resolve({ width: img.naturalWidth || img.width, height: img.naturalHeight || img.height });
      img.onerror = () => resolve(null);
      img.src = dataUrl;
    });
  }

  function svgToPngDataUrl(svg, size = 192) {
    return new Promise((resolve) => {
      const img = new Image();
      const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, size, size);
        ctx.drawImage(img, 0, 0, size, size);
        URL.revokeObjectURL(url);
        resolve(canvas.toDataURL('image/png'));
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        resolve(null);
      };
      img.src = url;
    });
  }

  async function getPdfIconDataUrl(key, prohibited) {
    const cacheKey = `${key}:${prohibited ? 'no' : 'ok'}`;
    if (iconCache.has(cacheKey)) return iconCache.get(cacheKey);

    const renderer = window.RetreatDocumentIllustrations;
    if (!renderer || typeof renderer.illustrationSvg !== 'function') return null;

    const strokeColor = prohibited ? '#6F6667' : '#2E7D32';
    let svg = renderer.illustrationSvg(key, strokeColor);
    if (prohibited) {
      const overlay = '<circle cx="48" cy="48" r="35" fill="none" stroke="#C62828" stroke-width="4"/><line x1="23" y1="73" x2="73" y2="23" stroke="#C62828" stroke-width="4.6" stroke-linecap="round"/>';
      svg = svg.replace('</svg>', `${overlay}</svg>`);
    }
    const dataUrl = await svgToPngDataUrl(svg, 224);
    iconCache.set(cacheKey, dataUrl);
    return dataUrl;
  }

  function setColor(pdf, color) {
    pdf.setTextColor(color[0], color[1], color[2]);
  }

  function fill(pdf, color) {
    pdf.setFillColor(color[0], color[1], color[2]);
  }

  function stroke(pdf, color) {
    pdf.setDrawColor(color[0], color[1], color[2]);
  }

  function withOpacity(pdf, opacity, draw) {
    const GState = pdf.GState || (window.jspdf && window.jspdf.GState);
    if (typeof GState !== 'function' || typeof pdf.setGState !== 'function') {
      draw();
      return;
    }
    pdf.setGState(new GState({ opacity }));
    draw();
    pdf.setGState(new GState({ opacity: 1 }));
  }

  function write(pdf, text, x, y, options = {}) {
    pdf.setFont('helvetica', options.style || 'normal');
    pdf.setFontSize(options.size || 10);
    setColor(pdf, options.color || COLORS.ink);
    const content = Array.isArray(text)
      ? text.map((line) => String(line || ''))
      : String(text || '');
    pdf.text(content, x, y, options.pdfOptions || {});
  }

  function lines(pdf, text, width, size = 10) {
    pdf.setFontSize(size);
    return String(text || '')
      .split('\n')
      .flatMap((part) => pdf.splitTextToSize(part, width));
  }

  function drawLocationPin(pdf, x, y, scale = 1) {
    stroke(pdf, COLORS.accent);
    fill(pdf, COLORS.accent);
    pdf.setLineWidth(0.55 * scale);
    pdf.circle(x, y - 1.7 * scale, 1.65 * scale, 'S');
    pdf.circle(x, y - 1.7 * scale, 0.45 * scale, 'F');
    pdf.line(x, y, x, y + 2.2 * scale);
  }

  function coverImage(pdf, dataUrl, x, y, width, height, naturalSize) {
    if (!dataUrl || !naturalSize || !naturalSize.width || !naturalSize.height) return false;
    const sourceRatio = naturalSize.width / naturalSize.height;
    const targetRatio = width / height;
    let drawW = width;
    let drawH = height;
    if (sourceRatio > targetRatio) {
      drawH = height;
      drawW = height * sourceRatio;
    } else {
      drawW = width;
      drawH = width / sourceRatio;
    }
    pdf.addImage(dataUrl, dataUrl.startsWith('data:image/png') ? 'PNG' : 'JPEG', x - (drawW - width) / 2, y - (drawH - height) / 2, drawW, drawH);
    return true;
  }

  function containRect(naturalSize, maxWidth, maxHeight) {
    if (!naturalSize || !naturalSize.width || !naturalSize.height) return { width: maxWidth, height: maxHeight };
    const ratio = naturalSize.width / naturalSize.height;
    let width = maxWidth;
    let height = width / ratio;
    if (height > maxHeight) {
      height = maxHeight;
      width = height * ratio;
    }
    return { width, height };
  }

  function drawDocumentHeader(pdf, doc, pageNumber, totalPages, assets) {
    const identity = window.RetreatDocuments.identity;
    const headerAssets = assets || {};
    const hasBackground = coverImage(pdf, headerAssets.background, 0, 0, PAGE.width, HEADER_HEIGHT, headerAssets.backgroundSize);
    fill(pdf, [33, 23, 26]);
    if (!hasBackground) {
      pdf.rect(0, 0, PAGE.width, HEADER_HEIGHT, 'F');
    } else {
      fill(pdf, [26, 16, 24]);
      withOpacity(pdf, 0.84, () => pdf.rect(0, 0, PAGE.width, HEADER_HEIGHT, 'F'));
      fill(pdf, [79, 44, 36]);
      withOpacity(pdf, 0.38, () => pdf.rect(0, 0, PAGE.width, HEADER_HEIGHT, 'F'));
    }
    fill(pdf, COLORS.accent);
    pdf.rect(0, HEADER_HEIGHT - 0.8, PAGE.width, 0.8, 'F');
    fill(pdf, COLORS.paper);
    pdf.rect(0, HEADER_HEIGHT, PAGE.width, PAGE.height - HEADER_HEIGHT, 'F');

    if (headerAssets.logo) {
      const logoBox = containRect(headerAssets.logoSize, 58, 19);
      pdf.addImage(headerAssets.logo, 'PNG', PAGE.margin, 12.2, logoBox.width, logoBox.height);
    } else {
      fill(pdf, COLORS.accentLight);
      stroke(pdf, COLORS.accent);
      pdf.roundedRect(PAGE.margin, 11, 44, 18, 3, 3, 'FD');
      write(pdf, 'CMP · JEUNESSE', PAGE.margin + 22, 21.6, {
        size: 7,
        style: 'bold',
        color: COLORS.ink,
        pdfOptions: { align: 'center' },
      });
    }

    const infoX = PAGE.width - PAGE.margin;
    const infoW = 82;
    write(pdf, identity.organization, infoX, 10.8, {
      size: 7,
      style: 'bold',
      color: [255, 255, 255],
      pdfOptions: { align: 'right' },
    });
    write(pdf, lines(pdf, identity.address.replace(' et Shaumba,', '\net Shaumba,'), infoW, 5.2), infoX, 16.3, {
      size: 5.2,
      color: [225, 217, 210],
      pdfOptions: { align: 'right', lineHeightFactor: 1.18 },
    });
    write(pdf, identity.phonePrimary, infoX, 28.4, {
      size: 5.8,
      color: [225, 217, 210],
      pdfOptions: { align: 'right' },
    });
    write(pdf, lines(pdf, `${identity.event}\n${identity.theme}`, infoW, 5.8), infoX, 33.5, {
      size: 5.8,
      color: [242, 210, 164],
      pdfOptions: { align: 'right', lineHeightFactor: 1.12 },
    });

    write(pdf, doc.title, PAGE.margin, 58, { size: 20, style: 'bold', color: COLORS.ink });
    write(pdf, doc.subtitle || '', PAGE.margin, 65, { size: 9.5, color: COLORS.muted });
    write(pdf, identity.dates, PAGE.margin, 72, { size: 8.2, color: COLORS.accent, style: 'bold' });
    drawLocationPin(pdf, PAGE.margin + 52, 71.4, 0.9);
    write(pdf, identity.location, PAGE.margin + 56, 72, { size: 8.2, color: COLORS.accent, style: 'bold' });
    stroke(pdf, COLORS.border);
    pdf.line(PAGE.margin, 78, PAGE.width - PAGE.margin, 78);

    drawFooter(pdf, doc.title, pageNumber, totalPages);
  }

  function drawFooter(pdf, label, pageNumber, totalPages) {
    stroke(pdf, COLORS.border);
    pdf.line(PAGE.margin, PAGE.height - 13, PAGE.width - PAGE.margin, PAGE.height - 13);
    write(pdf, `Grande Retraite des Jeunes — ${label} | Page ${pageNumber} sur ${totalPages}`, PAGE.margin, PAGE.height - 7, {
      size: 7.2,
      color: COLORS.muted,
    });
  }

  function drawRuleArticle(pdf, article, y) {
    const x = PAGE.margin;
    const contentX = x + 18;
    const maxWidth = PAGE.width - PAGE.margin - contentX;
    write(pdf, String(article.number).padStart(2, '0'), x, y, { size: 9, style: 'bold', color: COLORS.accent });
    write(pdf, `ARTICLE ${article.number}`, contentX, y, { size: 9.8, style: 'bold', color: COLORS.ink });
    y += 5.2;

    (article.paragraphs || []).forEach((paragraph) => {
      const wrapped = lines(pdf, paragraph, maxWidth, 9.1);
      write(pdf, wrapped, contentX, y, { size: 9.1, color: COLORS.muted, pdfOptions: { lineHeightFactor: 1.42 } });
      y += wrapped.length * 4.7 + 1.8;
    });

    (article.bulletPoints || []).forEach((point) => {
      const wrapped = lines(pdf, point, maxWidth - 4, 8.8);
      fill(pdf, COLORS.accent);
      pdf.circle(contentX + 1.2, y - 1.3, 0.7, 'F');
      write(pdf, wrapped, contentX + 4.2, y, { size: 8.8, color: COLORS.muted, pdfOptions: { lineHeightFactor: 1.36 } });
      y += wrapped.length * 4.3 + 0.8;
    });

    return y + 3.2;
  }

  async function drawRulesPdf() {
    const docs = window.RetreatDocuments;
    const doc = docs.rules;
    const pdf = ensurePdf();
    if (!pdf) return null;
    const [logoDataUrl, backgroundDataUrl] = await Promise.all([
      loadImageDataUrl(docs.identity.logo),
      loadImageDataUrl(docs.identity.headerBackground),
    ]);
    const [logoSize, backgroundSize] = await Promise.all([
      imageSizeFromDataUrl(logoDataUrl),
      imageSizeFromDataUrl(backgroundDataUrl),
    ]);
    const headerAssets = { logo: logoDataUrl, logoSize, background: backgroundDataUrl, backgroundSize };

    const pageOne = doc.articles.filter((article) => article.number <= doc.pageBreakAfterArticle);
    const pageTwo = doc.articles.filter((article) => article.number > doc.pageBreakAfterArticle);

    drawDocumentHeader(pdf, doc, 1, 2, headerAssets);
    fill(pdf, COLORS.accentLight);
    pdf.roundedRect(PAGE.margin, 84, PAGE.width - PAGE.margin * 2, 20, 2.5, 2.5, 'F');
    write(pdf, lines(pdf, doc.preamble, PAGE.width - PAGE.margin * 2 - 10, 9.4), PAGE.margin + 5, 90.5, {
      size: 9.4,
      color: COLORS.ink,
      pdfOptions: { lineHeightFactor: 1.35 },
    });
    let y = 113;
    pageOne.forEach((article) => { y = drawRuleArticle(pdf, article, y); });

    pdf.addPage();
    drawFooter(pdf, doc.title, 2, 2);
    y = 24;
    pageTwo.forEach((article) => { y = drawRuleArticle(pdf, article, y); });
    write(pdf, doc.conclusion, PAGE.width / 2, Math.min(y + 5, 270), {
      size: 10.2,
      style: 'bold',
      color: COLORS.accent,
      pdfOptions: { align: 'center' },
    });

    return pdf;
  }

  function drawPdfItemIcon(pdf, key, x, y, prohibited) {
    const cx = x + 12;
    const cy = y + 12;
    const accent = prohibited ? COLORS.danger : COLORS.success;

    if (prohibited) {
      stroke(pdf, COLORS.border);
      fill(pdf, COLORS.paper);
      pdf.circle(cx, cy, 12, 'FD');
    }
    stroke(pdf, accent);
    pdf.setLineWidth(0.8);

    if (['phone', 'tablet'].includes(key)) {
      pdf.roundedRect(cx - 4.5, cy - 7, 9, 14, 1.5, 1.5, 'S');
      pdf.line(cx - 2, cy + 4.5, cx + 2, cy + 4.5);
    } else if (key === 'mattress') {
      pdf.roundedRect(cx - 8, cy - 4, 16, 9, 2, 2, 'S');
      pdf.line(cx - 8, cy, cx + 8, cy);
    } else if (key === 'bucket') {
      pdf.line(cx - 6, cy - 4, cx + 6, cy - 4);
      pdf.line(cx - 5, cy - 4, cx - 3, cy + 7);
      pdf.line(cx + 5, cy - 4, cx + 3, cy + 7);
      pdf.line(cx - 3, cy + 7, cx + 3, cy + 7);
    } else if (key === 'bible' || key === 'notebook' || key === 'document' || key === 'receipt') {
      pdf.rect(cx - 6, cy - 7, 12, 14, 'S');
      pdf.line(cx - 2, cy - 3, cx + 4, cy - 3);
      pdf.line(cx - 2, cy + 1, cx + 4, cy + 1);
    } else if (key === 'toothbrush' || key === 'pen' || key === 'sharp') {
      pdf.line(cx - 7, cy + 7, cx + 7, cy - 7);
      pdf.line(cx + 4, cy - 7, cx + 8, cy - 7);
    } else if (key === 'valuables') {
      pdf.lines([[6, 0], [-6, 8], [-6, -8], [12, 0]], cx - 6, cy - 3);
    } else if (key === 'money') {
      pdf.rect(cx - 8, cy - 5, 16, 10, 'S');
      pdf.circle(cx, cy, 3, 'S');
    } else {
      pdf.roundedRect(cx - 7, cy - 5, 14, 10, 3, 3, 'S');
    }

    if (prohibited) {
      stroke(pdf, COLORS.danger);
      pdf.setLineWidth(1.1);
      pdf.circle(cx, cy, 13, 'S');
      pdf.line(cx - 9, cy + 9, cx + 9, cy - 9);
    }
  }

  async function drawItemGrid(pdf, title, items, startY, prohibited) {
    const x0 = PAGE.margin;
    const gap = 9;
    const colW = (PAGE.width - PAGE.margin * 2 - gap) / 2;
    let y = startY;

    write(pdf, title, x0, y, { size: 12, style: 'bold', color: prohibited ? COLORS.danger : COLORS.ink });
    y += 7;

    for (let index = 0; index < items.length; index += 2) {
      const pair = items.slice(index, index + 2).map((item, col) => {
        const textWidth = colW - 26;
        const labelLines = lines(pdf, item.label, textWidth, 8.2);
        const descriptionLines = item.description ? lines(pdf, item.description, textWidth, 6.9) : [];
        const rowHeight = Math.max(22.5, labelLines.length * 3.95 + descriptionLines.length * 3.35 + 7);
        return { item, col, labelLines, descriptionLines, rowHeight };
      });
      const pairHeight = Math.max(...pair.map((entry) => entry.rowHeight));

      for (const entry of pair) {
        const x = x0 + entry.col * (colW + gap);
        const itemY = y;
        const iconDataUrl = await getPdfIconDataUrl(entry.item.illustration, prohibited);
        if (iconDataUrl) {
          pdf.addImage(iconDataUrl, 'PNG', x, itemY + 0.4, 19, 19);
        } else {
          drawPdfItemIcon(pdf, entry.item.illustration, x, itemY, prohibited);
        }
        write(pdf, entry.labelLines, x + 25, itemY + 6.6, {
          size: 8.2,
          style: 'bold',
          color: COLORS.ink,
          pdfOptions: { lineHeightFactor: 1.22 },
        });
        if (entry.descriptionLines.length) {
          write(pdf, entry.descriptionLines, x + 25, itemY + 7.2 + entry.labelLines.length * 3.85, {
            size: 6.9,
            color: COLORS.muted,
            pdfOptions: { lineHeightFactor: 1.18 },
          });
        }
        stroke(pdf, COLORS.border);
        pdf.setLineWidth(0.25);
        pdf.line(x, itemY + pairHeight, x + colW, itemY + pairHeight);
      }

      y += pairHeight + 3.2;
    }

    return y + 4;
  }

  async function drawItemsPdf() {
    const docs = window.RetreatDocuments;
    const doc = docs.items;
    const pdf = ensurePdf();
    if (!pdf) return null;
    const [logoDataUrl, backgroundDataUrl] = await Promise.all([
      loadImageDataUrl(docs.identity.logo),
      loadImageDataUrl(docs.identity.headerBackground),
    ]);
    const [logoSize, backgroundSize] = await Promise.all([
      imageSizeFromDataUrl(logoDataUrl),
      imageSizeFromDataUrl(backgroundDataUrl),
    ]);
    const headerAssets = { logo: logoDataUrl, logoSize, background: backgroundDataUrl, backgroundSize };

    drawDocumentHeader(pdf, doc, 1, 2, headerAssets);
    let y = 86;
    await drawItemGrid(pdf, 'À apporter', doc.required, y, false);

    pdf.addPage();
    drawFooter(pdf, doc.title, 2, 2);
    y = await drawItemGrid(pdf, 'À ne pas apporter', doc.prohibited, 24, true);

    fill(pdf, COLORS.accentLight);
    pdf.roundedRect(PAGE.margin, y + 3, PAGE.width - PAGE.margin * 2, 40, 2.5, 2.5, 'F');
    write(pdf, 'IMPORTANT', PAGE.margin + 5, y + 11, { size: 9.4, style: 'bold', color: COLORS.accent });
    let noteY = y + 17;
    doc.notice.forEach((note) => {
      const noteLines = lines(pdf, note, PAGE.width - PAGE.margin * 2 - 15, 7.8);
      fill(pdf, COLORS.accent);
      pdf.circle(PAGE.margin + 6, noteY - 1.2, 0.7, 'F');
      write(pdf, noteLines, PAGE.margin + 10, noteY, {
        size: 7.8,
        color: COLORS.ink,
        pdfOptions: { lineHeightFactor: 1.28 },
      });
      noteY += noteLines.length > 2 ? 14 : (noteLines.length > 1 ? 10.5 : 6.2);
    });

    return pdf;
  }

  async function download(type) {
    const docs = window.RetreatDocuments;
    const doc = docs && docs[type];
    let pdf = null;
    if (type === 'rules') pdf = await drawRulesPdf();
    if (type === 'items') pdf = await drawItemsPdf();
    if (pdf && doc) pdf.save(doc.pdfFileName);
  }

  window.RetreatDocumentPdf = { download };
}());

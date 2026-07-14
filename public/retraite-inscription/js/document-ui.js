/* ═══════════════════════════════════════════
   DOCUMENT WEB RENDERING
═══════════════════════════════════════════ */
'use strict';

const DOC_SVG_STROKE = 'currentColor';

function docEscape(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function illustrationSvg(key, strokeColor = DOC_SVG_STROKE) {
  const common = `viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"`;
  const path = {
    receipt: `<path d="M28 18h40v60l-7-4-6 4-7-4-7 4-6-4-7 4V18Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M38 34h20M38 46h20M38 58h14" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    mattress: `<path d="M16 42c0-8 7-14 15-14h34c8 0 15 6 15 14v20H16V42Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M16 52h64M30 28v34M52 28v34" stroke="${strokeColor}" stroke-width="3" opacity=".55"/>`,
    sheets: `<path d="M22 30 48 18l26 12-26 12-26-12Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M22 45 48 57l26-12M22 60l26 12 26-12" stroke="${strokeColor}" stroke-width="4" stroke-linejoin="round"/>`,
    clothes: `<path d="M35 22h26l11 12-9 10-5-4v34H38V40l-5 4-9-10 11-12Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4" stroke-linejoin="round"/><path d="M40 23c2 6 14 6 16 0" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    bucket: `<path d="M28 36h40l-5 38H33l-5-38Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M34 36c1-12 27-12 28 0" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/><path d="M36 50h24" stroke="${strokeColor}" stroke-width="3" opacity=".55"/>`,
    soap: `<rect x="22" y="38" width="52" height="28" rx="14" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M39 50h18" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/><circle cx="62" cy="29" r="5" stroke="${strokeColor}" stroke-width="3"/><circle cx="72" cy="20" r="3" stroke="${strokeColor}" stroke-width="3"/>`,
    toothbrush: `<path d="M22 68 62 28" stroke="${strokeColor}" stroke-width="7" stroke-linecap="round"/><path d="M62 28h15v14H62V28Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M67 24v18M73 24v18" stroke="${strokeColor}" stroke-width="3" stroke-linecap="round"/>`,
    toothpaste: `<path d="M28 58 56 30l12 12-28 28H28V58Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4" stroke-linejoin="round"/><path d="M58 28 70 16l10 10-12 12" stroke="${strokeColor}" stroke-width="4"/><path d="M39 55 53 41" stroke="${strokeColor}" stroke-width="3" opacity=".55"/>`,
    utensils: `<path d="M32 20v56M24 20v20c0 5 4 9 8 9s8-4 8-9V20M62 20v56M62 20c10 6 12 22 0 32" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    bible: `<path d="M28 20h38a8 8 0 0 1 8 8v46H32a10 10 0 0 1-10-10V30a10 10 0 0 1 10-10Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M34 36h22M45 28v24M34 64h40" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    notebook: `<path d="M30 18h42v60H30a8 8 0 0 1-8-8V26a8 8 0 0 1 8-8Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M34 32h26M34 44h26M34 56h20M26 30h-6M26 44h-6M26 58h-6" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    pen: `<path d="M22 72 28 54 62 20l14 14-34 34-20 4Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4" stroke-linejoin="round"/><path d="M56 26 70 40M28 54l14 14" stroke="${strokeColor}" stroke-width="4"/>`,
    deodorant: `<path d="M34 34h28v40H34V34Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M39 22h18l5 12H34l5-12Z" stroke="${strokeColor}" stroke-width="4"/><path d="M42 52h12" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    phone: `<rect x="30" y="16" width="36" height="64" rx="7" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M43 24h10M45 70h6" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    tablet: `<rect x="22" y="18" width="52" height="60" rx="7" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M45 68h6" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    money: `<path d="M20 32h56v34H20V32Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><circle cx="48" cy="49" r="10" stroke="${strokeColor}" stroke-width="4"/><path d="M28 42h6M62 56h6" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    valuables: `<path d="M24 34h48l-24 34-24-34Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4" stroke-linejoin="round"/><path d="M34 34 48 68M62 34 48 68M34 34l8-10h12l8 10" stroke="${strokeColor}" stroke-width="3" stroke-linejoin="round"/>`,
    document: `<path d="M30 16h26l14 14v50H30V16Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M56 16v16h14M40 46h20M40 58h16" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    capsule: `<path d="M30 66c-7-7-7-18 0-25l12-12c7-7 18-7 25 0s7 18 0 25L55 66c-7 7-18 7-25 0Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M42 30 66 54" stroke="${strokeColor}" stroke-width="4"/>`,
    food: `<path d="M32 74h32l4-36H28l4 36Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4"/><path d="M30 38h36l-5-14H35l-5 14ZM38 50h20" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
    sharp: `<path d="M25 72 70 27c4-4 8 1 5 5L32 77l-7-5Z" fill="#F8F0E6" stroke="${strokeColor}" stroke-width="4" stroke-linejoin="round"/><path d="M22 69 17 79M32 77l-10 5" stroke="${strokeColor}" stroke-width="4" stroke-linecap="round"/>`,
  };

  return `<svg class="document-item-svg" ${common}>${path[key] || path.document}</svg>`;
}

function renderItem(item, prohibited) {
  return `
    <li class="document-item ${prohibited ? 'is-prohibited' : ''}">
      <div class="document-item-art">
        ${illustrationSvg(item.illustration)}
        ${prohibited ? '<span class="document-prohibited-mark" aria-hidden="true"></span>' : ''}
      </div>
      <div>
        <strong>${docEscape(item.label)}</strong>
        ${item.description ? `<span>${docEscape(item.description)}</span>` : ''}
      </div>
    </li>
  `;
}

function renderRules(doc, container) {
  container = container || document.getElementById('documentContent');
  if (!container) {
    return;
  }

  container.innerHTML = `
    <p class="document-preamble">${docEscape(doc.preamble)}</p>
    <div class="document-article-list">
      ${doc.articles.map((article) => `
        <article class="document-rule-article">
          <div class="document-rule-number">${String(article.number).padStart(2, '0')}</div>
          <div>
            <h2>Article ${article.number}</h2>
            ${(article.paragraphs || []).map((paragraph) => `<p>${docEscape(paragraph)}</p>`).join('')}
            ${article.bulletPoints ? `<ul>${article.bulletPoints.map((point) => `<li>${docEscape(point)}</li>`).join('')}</ul>` : ''}
          </div>
        </article>
      `).join('')}
    </div>
    <p class="document-conclusion">${docEscape(doc.conclusion)}</p>
  `;
}

function renderItems(doc, container) {
  container = container || document.getElementById('documentContent');
  if (!container) {
    return;
  }

  container.innerHTML = `
    <div class="document-items-layout">
      <section class="document-item-section">
        <div class="document-section-kicker">Section 1</div>
        <h2>À apporter</h2>
        <p>Les éléments nécessaires pour vivre la retraite dans de bonnes conditions.</p>
        <ul class="document-item-list">${doc.required.map((item) => renderItem(item, false)).join('')}</ul>
      </section>
      <section class="document-item-section is-danger">
        <div class="document-section-kicker">Section 2</div>
        <h2>À ne pas apporter</h2>
        <p>Ces objets sont interdits sur le site de la retraite et peuvent entraîner une mesure disciplinaire.</p>
        <ul class="document-item-list">${doc.prohibited.map((item) => renderItem(item, true)).join('')}</ul>
      </section>
    </div>
    <aside class="document-notice">
      <h2>Important</h2>
      <ul>${doc.notice.map((item) => `<li>${docEscape(item)}</li>`).join('')}</ul>
    </aside>
  `;
}

function initRetreatDocumentPage() {
  const page = document.body.dataset.document;
  const docs = window.RetreatDocuments;
  if (!page || !docs || !docs[page]) return;

  const doc = docs[page];
  const identity = docs.identity;
  document.querySelectorAll('[data-doc-title]').forEach((node) => { node.textContent = doc.title; });
  document.querySelectorAll('[data-doc-subtitle]').forEach((node) => { node.textContent = doc.subtitle; });
  document.querySelectorAll('[data-doc-description]').forEach((node) => { node.textContent = doc.description; });
  document.querySelectorAll('[data-doc-eyebrow]').forEach((node) => { node.textContent = doc.eyebrow; });
  document.querySelectorAll('[data-doc-updated]').forEach((node) => { node.textContent = doc.updatedAt; });
  document.querySelectorAll('[data-doc-pages]').forEach((node) => { node.textContent = `${doc.pageCount} page${doc.pageCount > 1 ? 's' : ''}`; });
  document.querySelectorAll('[data-doc-theme]').forEach((node) => { node.textContent = identity.theme; });
  document.querySelectorAll('[data-doc-dates]').forEach((node) => { node.textContent = identity.dates; });
  document.querySelectorAll('[data-doc-location]').forEach((node) => { node.textContent = identity.location; });

  if (page === 'rules') renderRules(doc);
  if (page === 'items') renderItems(doc);

  const pdfButton = document.getElementById('downloadPdfBtn');
  if (pdfButton && window.RetreatDocumentPdf) {
    pdfButton.addEventListener('click', async () => {
      const original = pdfButton.innerHTML;
      pdfButton.disabled = true;
      pdfButton.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> Génération...';
      try {
        await window.RetreatDocumentPdf.download(page);
      } finally {
        pdfButton.disabled = false;
        pdfButton.innerHTML = original;
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', initRetreatDocumentPage);

window.RetreatDocumentIllustrations = { illustrationSvg };

/**
 * Monte les panneaux document dans la page billet (onglets intégrés).
 */
window.RetreatBilletDocumentPanels = {
  mountReglement(containerId) {
    const container = document.getElementById(containerId);
    const doc = window.RetreatDocuments && window.RetreatDocuments.rules;

    if (!container || !doc) {
      return;
    }

    renderRules(doc, container);
  },
  mountObjets(containerId) {
    const container = document.getElementById(containerId);
    const doc = window.RetreatDocuments && window.RetreatDocuments.items;

    if (!container || !doc) {
      return;
    }

    renderItems(doc, container);
  },
};

/**
 * Bannière dynamique du portail d'accueil (même source que l'inscription retraite).
 */
'use strict';

/**
 * Retourne la base URL de l'API inscription retraite.
 *
 * @returns {string}
 */
function getPortailRetraiteApiBase() {
  const meta = document.querySelector('meta[name="retraite-api-base"]');
  return meta && meta.content ? meta.content.replace(/\/$/, '') : '';
}

/**
 * Charge l'événement retraite actif.
 *
 * @returns {Promise<object|null>}
 */
async function fetchPortailRetraiteEvent() {
  const base = getPortailRetraiteApiBase();
  if (!base) {
    return null;
  }

  const response = await fetch(`${base}/event`, { headers: { Accept: 'application/json' } });
  if (!response.ok) {
    return null;
  }

  const json = await response.json();
  return json.data || null;
}

/**
 * Applique les informations événement sur la bannière portail.
 *
 * @param {object|null} event Données événement API
 * @return {void}
 */
function applyPortailHeroFromEvent(event) {
  const hero = document.getElementById('portailHero');
  if (!hero) {
    return;
  }

  hero.classList.toggle('hero--has-poster', !!(event && event.affiche_url));

  if (event && event.affiche_url) {
    const affiche = JSON.stringify(event.affiche_url);
    hero.style.backgroundImage = `linear-gradient(120deg, rgba(26,16,24,0.82), rgba(26,16,24,0.55)), url(${affiche})`;
    hero.style.backgroundSize = 'cover';
    hero.style.backgroundPosition = 'center';
    hero.style.backgroundRepeat = 'no-repeat';
  } else {
    hero.style.backgroundImage = '';
    hero.style.backgroundSize = '';
    hero.style.backgroundPosition = '';
    hero.style.backgroundRepeat = '';
  }

  const subtitleStrong = hero.querySelector('.hero-sub strong');
  if (subtitleStrong) {
    subtitleStrong.textContent = event && event.name
      ? event.name
      : (subtitleStrong.dataset.fallback || subtitleStrong.textContent);
  }

  const themeEl = document.getElementById('portailHeroThemeLine');
  if (themeEl) {
    const detail = event && event.retreat_detail;
    const theme = detail && detail.theme ? String(detail.theme) : '';
    const speaker = detail && detail.speaker ? String(detail.speaker) : '';
    if (theme || speaker) {
      themeEl.classList.remove('hidden');
      themeEl.textContent = speaker && theme ? `${theme} · ${speaker}` : theme || speaker;
    } else {
      themeEl.classList.add('hidden');
      themeEl.textContent = '';
    }
  }

  const placesEl = document.getElementById('portailHeroPlacesLine');
  if (placesEl) {
    if (event && event.places_message) {
      placesEl.classList.remove('hidden');
      placesEl.textContent = event.places_message;
    } else {
      placesEl.classList.add('hidden');
      placesEl.textContent = '';
    }
  }

  const soldOutEl = document.getElementById('portailHeroSoldOutBar');
  if (soldOutEl) {
    if (event && event.is_sold_out) {
      soldOutEl.classList.remove('hidden');
    } else {
      soldOutEl.classList.add('hidden');
    }
  }
}

/**
 * Initialise la bannière au chargement de la page.
 *
 * @return {Promise<void>}
 */
async function loadPortailHero() {
  try {
    const event = await fetchPortailRetraiteEvent();
    if (event) {
      applyPortailHeroFromEvent(event);
    }
  } catch (error) {
    console.warn('Bannière portail : événement non chargé', error);
  }
}

document.addEventListener('DOMContentLoaded', loadPortailHero);

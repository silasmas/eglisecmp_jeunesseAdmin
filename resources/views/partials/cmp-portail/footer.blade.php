{{--
  Pied de page CMP Jeunesse — liens portail, mentions légales et crédit SDEV.
  @param bool $compact Masque la marque étendue (pages billet / accès)
--}}
@php
  $compact = $compact ?? false;
  $currentPath = request()->path();
  $homeUrl = url('/');
  $inscriptionUrl = route('retraite.inscription');
  $verifyUrl = url('/#verification-inscription');
  $adminUrl = url('/admin');
  $logoUrl = asset('retraite-inscription/img/logo.jpg');
  $cmpSiteUrl = 'https://eglisecmp.com';
  $sdevSiteUrl = 'https://silasmas.com';
@endphp
<footer class="cmp-footer" role="contentinfo">
  <div class="cmp-footer__inner">
    @unless($compact)
      <div class="cmp-footer__brand">
        <img
          src="{{ $logoUrl }}"
          alt=""
          class="cmp-footer__logo"
          width="48"
          height="48"
          loading="lazy"
        />
        <div class="cmp-footer__brand-text">
          <strong>CMP Jeunesse</strong>
          <span>Grande Retraite de la Jeunesse</span>
        </div>
      </div>
    @endunless

    <nav class="cmp-footer__nav" aria-label="Liens du site">
      <a href="{{ $homeUrl }}" @if($currentPath === '/' || $currentPath === '') class="is-active" @endif>Accueil</a>
      <a href="{{ $inscriptionUrl }}" @if(str_starts_with($currentPath, 'inscription-retraite')) class="is-active" @endif>Inscription</a>
      <a href="{{ $verifyUrl }}">Vérifier une inscription</a>
      <a href="{{ $adminUrl }}">Administration</a>
    </nav>

    <div class="cmp-footer__bottom">
      <a
        href="{{ $cmpSiteUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        class="cmp-footer__legal"
      >
        <span class="cmp-footer__cmp-name">Centre Missionnaire Philadelphie</span>
        <span class="cmp-footer__legal-sep" aria-hidden="true">·</span>
        <span class="cmp-footer__rights">Tous droits réservés.</span>
      </a>
      <span class="cmp-footer__copyright" aria-label="Copyright">© {{ date('Y') }}</span>
      <p class="cmp-footer__sdev">
        Designed by
        <a href="{{ $sdevSiteUrl }}" target="_blank" rel="noopener noreferrer" title="SDEV — silasmas.com">SDEV</a>
      </p>
    </div>
  </div>
</footer>

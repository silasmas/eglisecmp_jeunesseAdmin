@php
  $participantName = $participant?->full_name ?? 'Participant';
  $proofUrl = $proofUrl ?? null;
  $downloadUrl = $downloadUrl ?? $proofUrl;
  $filename = $filename ?? 'preuve-paiement';
  $mediaKind = $mediaKind ?? 'unknown';
  $viewerId = 'payment-proof-viewer-'.($participant?->id ?? '0').'-'.substr(md5((string) $proofUrl), 0, 8);
@endphp

<style>
  .cmp-payment-proof-viewer {
    max-width: 100%;
    overflow: hidden;
  }

  .cmp-payment-proof-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.75rem;
  }

  .cmp-payment-proof-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2rem;
    padding: 0.35rem 0.65rem;
    border: 1px solid rgb(209 213 219);
    border-radius: 0.5rem;
    background: #fff;
    color: rgb(55 65 81);
    font-size: 0.78rem;
    font-weight: 600;
    line-height: 1.2;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
  }

  .cmp-payment-proof-btn:hover {
    background: rgb(249 250 251);
  }

  .cmp-payment-proof-btn-primary {
    border-color: rgb(123 29 62);
    background: rgb(123 29 62);
    color: #fff;
  }

  .cmp-payment-proof-btn-primary:hover {
    background: rgb(107 24 54);
  }

  .cmp-payment-proof-zoom-label {
    margin-left: auto;
    font-size: 0.78rem;
    font-weight: 700;
    color: rgb(107 114 128);
  }

  .cmp-payment-proof-viewport {
    max-height: min(52vh, 500px);
    overflow: auto;
    border: 1px solid rgb(229 231 235);
    border-radius: 0.75rem;
    background: repeating-conic-gradient(#f3f4f6 0% 25%, #eceff3 0% 50%) 50% / 20px 20px;
    padding: 0.75rem;
    overscroll-behavior: contain;
  }

  .cmp-payment-proof-stage {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: min-content;
    min-height: min-content;
    margin: auto;
    transform-origin: center center;
    transition: transform 0.12s ease-out;
  }

  .cmp-payment-proof-stage img {
    display: block;
    max-width: min(100%, 720px);
    max-height: min(46vh, 460px);
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 0.35rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    background: #fff;
  }

  .cmp-payment-proof-stage iframe {
    display: block;
    width: min(100%, 680px);
    height: min(46vh, 460px);
    border: 0;
    border-radius: 0.35rem;
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
  }

  .cmp-payment-proof-meta,
  .cmp-payment-proof-empty {
    margin: 0.75rem 0 0;
    font-size: 0.82rem;
    color: rgb(107 114 128);
    line-height: 1.45;
  }

  .dark .cmp-payment-proof-btn {
    border-color: rgb(75 85 99);
    background: rgb(31 41 55);
    color: rgb(229 231 235);
  }

  .dark .cmp-payment-proof-viewport {
    border-color: rgb(75 85 99);
    background: repeating-conic-gradient(#1f2937 0% 25%, #111827 0% 50%) 50% / 20px 20px;
  }
</style>

<div
  id="{{ $viewerId }}"
  class="cmp-payment-proof-viewer"
  data-cmp-payment-proof-viewer
  data-rotation="0"
  data-scale="1"
  data-media-kind="{{ $mediaKind }}"
  wire:ignore
>
  @if(filled($proofUrl))
    <div class="cmp-payment-proof-toolbar" role="toolbar" aria-label="Outils preuve de paiement">
      <button
        type="button"
        class="cmp-payment-proof-btn"
        data-cmp-proof-action="rotate-left"
        title="Pivoter à gauche"
      >
        ↺ Gauche
      </button>
      <button
        type="button"
        class="cmp-payment-proof-btn"
        data-cmp-proof-action="rotate-right"
        title="Pivoter à droite"
      >
        ↻ Droite
      </button>
      <button
        type="button"
        class="cmp-payment-proof-btn"
        data-cmp-proof-action="zoom-out"
        title="Zoom arrière"
      >
        − Zoom
      </button>
      <button
        type="button"
        class="cmp-payment-proof-btn"
        data-cmp-proof-action="zoom-in"
        title="Zoom avant"
      >
        + Zoom
      </button>
      <button
        type="button"
        class="cmp-payment-proof-btn"
        data-cmp-proof-action="reset"
        title="Réinitialiser"
      >
        ⟲ Réinitialiser
      </button>
      @if(filled($downloadUrl))
        <a
          href="{{ $downloadUrl }}"
          class="cmp-payment-proof-btn cmp-payment-proof-btn-primary"
          title="Télécharger la preuve"
        >
          ⬇ Télécharger
        </a>
      @endif
      <span class="cmp-payment-proof-zoom-label" data-cmp-proof-zoom-label>100%</span>
    </div>

    <div
      class="cmp-payment-proof-viewport"
      data-cmp-proof-viewport
      tabindex="0"
      aria-label="Aperçu de la preuve de paiement"
    >
      <div class="cmp-payment-proof-stage" data-cmp-proof-stage>
        @if($mediaKind === 'image')
          <img
            src="{{ $proofUrl }}"
            alt="Preuve de paiement — {{ $participantName }}"
            draggable="false"
          >
        @else
          <iframe
            src="{{ $proofUrl }}"
            title="Preuve de paiement — {{ $participantName }}"
          ></iframe>
        @endif
      </div>
    </div>

    <p class="cmp-payment-proof-meta">
      Participant : <strong>{{ $participantName }}</strong>
      · Molette dans la zone d’aperçu pour zoomer, barres de défilement si agrandi
    </p>
  @else
    <p class="cmp-payment-proof-empty">
      Aucune preuve de paiement disponible pour ce participant.
    </p>
  @endif
</div>

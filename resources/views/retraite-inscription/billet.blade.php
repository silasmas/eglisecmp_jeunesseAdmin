@php
  $reference = $payment?->reference ?: '#'.$participant->id.'-'.substr((string) $participant->download_token, 0, 8);
  $reglementPdf = collect($participantDocuments)->firstWhere('key', 'reglement');
  $ticketAsset = \App\Support\RetreatMailUrl::base().'/retraite-inscription/assets/billet-grande-retraite.jpg';
  $heroBackground = asset('retraite-inscription/assets/fond-retraite.jpg');
  $publicBase = \App\Support\RetreatMailUrl::base();
  $reglementStaticPdf = asset('retraite-inscription/assets/reglement_grande_retraite.pdf');
  $objetsStaticPdf = asset('retraite-inscription/assets/objets_grande_retraite.pdf');
  $billetPayload = [
    'qrUrl' => $qrScanUrl,
    'qrImageDataUrl' => $qrImageDataUri,
    'name' => $ticketName,
    'status' => $ticketStatus,
    'hebergement' => $ticketHebergement,
    'code' => $ticketCode,
    'slug' => \Illuminate\Support\Str::slug($participant->nom.'-'.$participant->prenom, '-'),
    'ticketAsset' => $ticketAsset,
  ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billet — {{ $participant->full_name }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('retraite-inscription/css/tokens.css') }}">
  <link rel="stylesheet" href="{{ asset('retraite-inscription/css/billet-page.css') }}">
  <link rel="stylesheet" href="{{ asset('retraite-inscription/css/billet-documents.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-layout.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-footer.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="{{ asset('retraite-inscription/js/document-data.js') }}"></script>
  <script src="{{ asset('retraite-inscription/js/document-ui.js') }}"></script>
  <script src="{{ asset('retraite-inscription/js/document-pdf.js') }}"></script>
  <script src="{{ asset('retraite-inscription/js/billet-page.js') }}" defer></script>
</head>
<body
  class="billet-page cmp-page-shell"
  style="--billet-hero-bg: url('{{ $heroBackground }}')"
  data-ticket-asset="{{ $ticketAsset }}"
  data-public-base="{{ $publicBase }}"
  data-reglement-pdf="{{ ($reglementPdf['url'] ?? null) ?: $reglementStaticPdf }}"
  data-objets-pdf="{{ $objetsStaticPdf }}"
  data-participant-slug="{{ $billetPayload['slug'] }}"
>
  <header class="hero billet-hero-banner">
    <div class="hero-content">
      <div class="hero-badge">
        <i class="bi bi-calendar-event" aria-hidden="true"></i>
        Jeunesse CMP — Gombe
      </div>
      <h1>Grande Retraite <span>des Jeunes</span></h1>
      <p class="hero-sub">
        <strong>Centre Missionnaire Philadelphie</strong> · Département de la Jeunesse
      </p>
      <div class="hero-divider"></div>
    </div>
  </header>

  <nav class="billet-page-nav" aria-label="Sections du billet">
    <button type="button" class="billet-page-nav-link is-active" data-billet-tab="billet">
      <i class="bi bi-ticket-perforated" aria-hidden="true"></i> Billet
    </button>
    <button type="button" class="billet-page-nav-link" data-billet-tab="reglement">
      <i class="bi bi-shield-check" aria-hidden="true"></i> Règlement
    </button>
    <button type="button" class="billet-page-nav-link" data-billet-tab="objets">
      <i class="bi bi-bag-check" aria-hidden="true"></i> Objets à apporter
    </button>
  </nav>

  <div class="billet-shell">
    <div class="billet-actions is-tab-billet" id="billetActionsBar">
      <a class="billet-btn billet-btn-outline" data-billet-action="portail" href="{{ url('/') }}">
        <i class="bi bi-house" aria-hidden="true"></i> Portail
      </a>
      <button type="button" class="billet-btn billet-btn-outline" data-billet-action="print" id="billetPrintBtn">
        <i class="bi bi-printer" aria-hidden="true"></i> Imprimer
      </button>
      <button type="button" class="billet-btn billet-btn-primary" data-billet-action="jpg" id="downloadTicketBtn">
        <i class="bi bi-download" aria-hidden="true"></i> Télécharger JPG
      </button>
      <button type="button" class="billet-btn billet-btn-primary" data-billet-action="pdf" id="downloadPdfBtn">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Télécharger le PDF
      </button>
    </div>

    <section class="billet-panel is-active" data-billet-panel="billet">
      <div class="billet-print-area" id="billetPrintArea">
        <div class="ticket-preview-stage">
          <div class="retreat-ticket-shell" id="ticketPreview">
            <div class="retreat-ticket">
              <img class="retreat-ticket-bg" src="{{ $ticketAsset }}" alt="">
              <div class="retreat-ticket-info">
                <div>
                  <span class="retreat-ticket-label">Noms</span>
                  <strong class="retreat-ticket-fit">{{ $ticketName }}</strong>
                </div>
                <div>
                  <span class="retreat-ticket-label">Statut</span>
                  <strong class="retreat-ticket-fit">{{ $ticketStatus }}</strong>
                </div>
                <div>
                  <span class="retreat-ticket-label">Hébergement</span>
                  <strong class="retreat-ticket-fit">{{ $ticketHebergement }}</strong>
                </div>
              </div>
              <img
                class="retreat-ticket-qr"
                id="ticketQrImage"
                src="{{ $qrImageDataUri }}"
                alt="Code QR du billet — {{ $participant->full_name }}"
              >
              <div class="retreat-ticket-qr-code" title="{{ $ticketCode }}">{{ $ticketCode }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="billet-meta">
        <strong>Référence :</strong> {{ $reference }}
        @if($showPlacements)
          · <strong>Chambre :</strong> {{ $participant->placementChambreLabel() }}
          · <strong>Atelier :</strong> {{ $participant->placementAtelierLabel() }}
        @else
          · {{ $placementsPendingMessage ?? 'Vos affectations chambre et atelier seront visibles à partir du début officiel de la retraite.' }}
        @endif
      </div>
    </section>

    <section class="billet-panel" data-billet-panel="reglement">
      <article
        class="document-reading document-reading-rules billet-print-area"
        id="reglementDocumentContent"
        aria-live="polite"
      ></article>
    </section>

    <section class="billet-panel" data-billet-panel="objets">
      <article
        class="document-reading document-reading-items billet-print-area"
        id="objetsDocumentContent"
        aria-live="polite"
      ></article>
    </section>
  </div>

  @include('partials.cmp-portail.footer', ['compact' => true])

  <script id="billetPayload" type="application/json">@json($billetPayload)</script>
</body>
</html>

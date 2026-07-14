@php
  $reference = $payment?->reference ?: '#'.$participant->id.'-'.substr((string) $participant->download_token, 0, 8);
  $reglementPdf = collect($participantDocuments)->firstWhere('key', 'reglement');
  $ticketAsset = asset('retraite-inscription/assets/billet-grande-retraite.jpg');
  $billetPayload = [
    'qrUrl' => $accessUrl,
    'name' => $ticketName,
    'status' => $ticketStatus,
    'hebergement' => $ticketHebergement,
    'code' => $ticketCode,
    'slug' => \Illuminate\Support\Str::slug($participant->nom.'-'.$participant->prenom, '-'),
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
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-layout.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-footer.css') }}">
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js" defer></script>
  <script src="{{ asset('retraite-inscription/js/billet-page.js') }}" defer></script>
</head>
<body
  class="billet-page cmp-page-shell"
  data-ticket-asset="{{ $ticketAsset }}"
>
  <header class="billet-hero">
    <div class="billet-hero-badge">
      <i class="bi bi-ticket-perforated" aria-hidden="true"></i>
      Billet officiel
    </div>
    <h1>Votre <span>billet</span></h1>
    <p class="billet-hero-sub">
      <strong>{{ $participant->event?->name ?? 'Grande Retraite des Jeunes' }}</strong>
      · Centre Missionnaire Philadelphie
    </p>
  </header>

  <div class="billet-shell">
    <nav class="billet-tabs" aria-label="Sections du billet">
      <button type="button" class="billet-tab is-active" data-billet-tab="billet">Billet</button>
      <button type="button" class="billet-tab" data-billet-tab="reglement">Règlement</button>
      <button type="button" class="billet-tab" data-billet-tab="objets">Objets à apporter</button>
    </nav>

    <div class="billet-actions">
      <a class="billet-btn billet-btn-outline" href="{{ url('/') }}">
        <i class="bi bi-house" aria-hidden="true"></i> Portail
      </a>
      <button type="button" class="billet-btn billet-btn-outline" onclick="window.print()">
        <i class="bi bi-printer" aria-hidden="true"></i> Imprimer
      </button>
      <button type="button" class="billet-btn billet-btn-primary" id="downloadTicketBtn">
        <i class="bi bi-download" aria-hidden="true"></i> Télécharger JPG
      </button>
    </div>

    <section class="billet-panel is-active" data-billet-panel="billet">
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
            <img class="retreat-ticket-qr" id="ticketQrImage" src="" alt="">
            <div class="retreat-ticket-qr-code" title="{{ $ticketCode }}">{{ $ticketCode }}</div>
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
      <article class="billet-doc">
        <div class="billet-doc-toolbar">
          <h2>{{ $rulesDocument['title'] ?? "Règlement d'Ordre Intérieur" }}</h2>
          @if($reglementPdf)
            <a class="billet-btn billet-btn-outline" href="{{ $reglementPdf['url'] }}" target="_blank" rel="noopener">
              <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF officiel
            </a>
          @endif
        </div>

        @if(filled($rulesDocument['preamble'] ?? null))
          <p class="billet-doc-preamble">{{ $rulesDocument['preamble'] }}</p>
        @endif

        @foreach($rulesDocument['articles'] ?? [] as $article)
          <article class="billet-article">
            <div class="billet-article-num">{{ str_pad((string) ($article['number'] ?? ''), 2, '0', STR_PAD_LEFT) }}</div>
            <div>
              <h3>Article {{ $article['number'] ?? '' }}</h3>
              @foreach($article['paragraphs'] ?? [] as $paragraph)
                <p>{{ $paragraph }}</p>
              @endforeach
              @if(! empty($article['bulletPoints']))
                <ul>
                  @foreach($article['bulletPoints'] as $point)
                    <li>{{ $point }}</li>
                  @endforeach
                </ul>
              @endif
            </div>
          </article>
        @endforeach

        @if(filled($rulesDocument['conclusion'] ?? null))
          <p class="billet-doc-preamble" style="margin-top:1rem;margin-bottom:0;">{{ $rulesDocument['conclusion'] }}</p>
        @endif
      </article>
    </section>

    <section class="billet-panel" data-billet-panel="objets">
      <article class="billet-doc">
        <div class="billet-doc-toolbar">
          <h2>Objets à apporter</h2>
        </div>

        <h3 class="billet-section-title">À apporter</h3>
        <ul class="billet-items-grid">
          @foreach($itemsDocument['required'] ?? [] as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>

        <h3 class="billet-section-title">À ne pas apporter</h3>
        <ul class="billet-items-grid is-danger">
          @foreach($itemsDocument['prohibited'] ?? [] as $item)
            <li>{{ $item }}</li>
          @endforeach
        </ul>

        @if(! empty($itemsDocument['notice']))
          <aside class="billet-notice">
            <strong>Important</strong>
            <ul>
              @foreach($itemsDocument['notice'] as $notice)
                <li>{{ $notice }}</li>
              @endforeach
            </ul>
          </aside>
        @endif
      </article>
    </section>
  </div>

  @include('partials.cmp-portail.footer', ['compact' => true])

  <script id="billetPayload" type="application/json">@json($billetPayload)</script>
</body>
</html>

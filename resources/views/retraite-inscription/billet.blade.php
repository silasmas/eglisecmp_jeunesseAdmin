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
    'ticketAsset' => $ticketAsset,
  ];
  $itemsPageTitle = $itemsDocument['page_title'] ?? 'Objets à apporter';
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
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script src="{{ asset('retraite-inscription/js/billet-page.js') }}" defer></script>
</head>
<body
  class="billet-page cmp-page-shell"
  data-ticket-asset="{{ $ticketAsset }}"
  data-participant-slug="{{ $billetPayload['slug'] }}"
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

    <div class="billet-actions" id="billetActionsBar">
      <div class="billet-actions-group is-active" data-actions-for="billet">
        <a class="billet-btn billet-btn-outline" href="{{ url('/') }}">
          <i class="bi bi-house" aria-hidden="true"></i> Portail
        </a>
        <button type="button" class="billet-btn billet-btn-outline" data-billet-print>
          <i class="bi bi-printer" aria-hidden="true"></i> Imprimer
        </button>
        <button type="button" class="billet-btn billet-btn-primary" id="downloadTicketBtn">
          <i class="bi bi-download" aria-hidden="true"></i> Télécharger JPG
        </button>
      </div>

      <div class="billet-actions-group" data-actions-for="reglement" hidden>
        @if($reglementPdf)
          <a
            class="billet-btn billet-btn-outline"
            href="{{ $reglementPdf['url'] }}"
            download
            target="_blank"
            rel="noopener"
          >
            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF officiel
          </a>
        @endif
        <button type="button" class="billet-btn billet-btn-outline" data-billet-print>
          <i class="bi bi-printer" aria-hidden="true"></i> Imprimer
        </button>
        <button type="button" class="billet-btn billet-btn-primary" data-billet-download-pdf="reglement">
          <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> Télécharger le PDF
        </button>
      </div>

      <div class="billet-actions-group" data-actions-for="objets" hidden>
        <a class="billet-btn billet-btn-outline" href="{{ route('retraite.inscription') }}">
          <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour à l'inscription
        </a>
        <button type="button" class="billet-btn billet-btn-outline" data-billet-print>
          <i class="bi bi-printer" aria-hidden="true"></i> Imprimer
        </button>
        <button type="button" class="billet-btn billet-btn-primary" data-billet-download-pdf="objets">
          <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> Télécharger le PDF
        </button>
      </div>
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
              <img class="retreat-ticket-qr" id="ticketQrImage" src="" alt="">
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
      <article class="billet-doc billet-print-area" id="reglementPrintArea">
        <div class="billet-doc-toolbar">
          <h2>{{ $rulesDocument['title'] ?? "Règlement d'Ordre Intérieur" }}</h2>
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
          <p class="billet-doc-preamble billet-doc-conclusion">{{ $rulesDocument['conclusion'] }}</p>
        @endif
      </article>
    </section>

    <section class="billet-panel" data-billet-panel="objets">
      <article class="billet-objets-sheet billet-print-area" id="objetsPrintArea">
        <header class="billet-objets-topbar">
          <a class="billet-objets-back" href="{{ route('retraite.inscription') }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour à l'inscription
          </a>
          <h2 class="billet-objets-title">{{ $itemsPageTitle }}</h2>
          <div class="billet-objets-topbar-actions" aria-hidden="true">
            <span class="billet-btn billet-btn-outline"><i class="bi bi-printer"></i> Imprimer</span>
            <span class="billet-btn billet-btn-dark"><i class="bi bi-file-earmark-arrow-down"></i> Télécharger le PDF</span>
          </div>
        </header>

        <div class="billet-objets-columns">
          <section class="billet-objets-col">
            <span class="billet-objets-kicker">Section 1</span>
            <h3>{{ $itemsDocument['required_heading'] ?? 'À apporter' }}</h3>
            <p class="billet-objets-intro">{{ $itemsDocument['required_intro'] ?? '' }}</p>
            <ul class="billet-objets-list">
              @foreach($itemsDocument['required'] ?? [] as $item)
                @php
                  $label = is_array($item) ? ($item['label'] ?? '') : (string) $item;
                  $icon = is_array($item) ? ($item['icon'] ?? 'bi-check-circle') : 'bi-check-circle';
                @endphp
                <li>
                  <span class="billet-objets-icon is-allowed" aria-hidden="true">
                    <i class="bi {{ $icon }}"></i>
                  </span>
                  <span class="billet-objets-label">{{ $label }}</span>
                </li>
              @endforeach
            </ul>
          </section>

          <section class="billet-objets-col is-forbidden">
            <span class="billet-objets-kicker">Section 2</span>
            <h3>{{ $itemsDocument['prohibited_heading'] ?? 'À ne pas apporter' }}</h3>
            <p class="billet-objets-intro">{{ $itemsDocument['prohibited_intro'] ?? '' }}</p>
            <ul class="billet-objets-list">
              @foreach($itemsDocument['prohibited'] ?? [] as $item)
                @php
                  $label = is_array($item) ? ($item['label'] ?? '') : (string) $item;
                  $subtitle = is_array($item) ? ($item['subtitle'] ?? null) : null;
                  $icon = is_array($item) ? ($item['icon'] ?? 'bi-x-circle') : 'bi-x-circle';
                @endphp
                <li>
                  <span class="billet-objets-icon is-forbidden" aria-hidden="true">
                    <i class="bi {{ $icon }}"></i>
                    <i class="bi bi-slash-circle billet-objets-ban"></i>
                  </span>
                  <span class="billet-objets-label">
                    {{ $label }}
                    @if(filled($subtitle))
                      <small>{{ $subtitle }}</small>
                    @endif
                  </span>
                </li>
              @endforeach
            </ul>
          </section>
        </div>

        @if(! empty($itemsDocument['notice']))
          <aside class="billet-objets-important">
            <strong>{{ $itemsDocument['notice_title'] ?? 'Important' }}</strong>
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

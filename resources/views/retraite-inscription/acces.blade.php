@php
  $reference = $payment?->reference ?: '#'.$participant->id.'-'.substr((string) $participant->download_token, 0, 8);
  $participantPhotoUrl = $participant->getFilamentAvatarUrl();
  $accessClass = $accessGranted ? 'granted' : 'denied';
  $accessLabel = $accessGranted ? 'Accès autorisé' : 'Accès non autorisé';
  $paymentLabel = $participant->paiement_valide ? 'Paiement validé' : 'Paiement en attente';
  $badgeLabel = $participant->badge_received ? 'Badge remis' : ($participant->paiement_valide ? 'Badge en attente' : '—');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contrôle d'accès — {{ $participant->full_name }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('retraite-inscription/css/tokens.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-layout.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-footer.css') }}">
  <style>
    :root {
      --ink: #171015;
      --muted: #6d5964;
      --line: #eadce3;
      --paper: #fff;
      --soft: #fbf5f8;
      --primary: #851c46;
      --success: #146c43;
      --danger: #b42318;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 24px 16px 0;
      color: var(--ink);
      background: #f5f1f3;
      font-family: Poppins, var(--font, Inter), ui-sans-serif, system-ui, sans-serif;
    }
    .sheet {
      width: min(640px, 100%);
      margin: 0 auto;
      background: var(--paper);
      border: 1px solid var(--line);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 12px 40px rgba(56, 24, 41, .1);
    }
    .banner {
      padding: 24px;
      text-align: center;
      color: #fff;
      font-size: 1.35rem;
      font-weight: 800;
    }
    .banner.granted { background: var(--success); }
    .banner.denied { background: var(--danger); }
    .content { padding: 24px; }
    .photo-wrap { display: flex; justify-content: center; margin-bottom: 16px; }
    .photo {
      width: 96px;
      height: 96px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid var(--line);
    }
    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    .field {
      padding: 12px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: var(--soft);
    }
    .field.full { grid-column: 1 / -1; }
    .label {
      display: block;
      margin-bottom: 4px;
      color: var(--muted);
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
    }
    .value { font-size: 15px; font-weight: 700; overflow-wrap: anywhere; }
    .note {
      margin-top: 16px;
      padding: 12px;
      border-left: 4px solid var(--primary);
      background: #fff8fb;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.5;
    }
    @media (max-width: 560px) { .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="cmp-page-shell">
  <main class="sheet">
    <div class="banner {{ $accessClass }}">{{ $accessLabel }}</div>
    <section class="content">
      <div class="photo-wrap">
        <img class="photo" src="{{ $participantPhotoUrl }}" alt="Photo participant">
      </div>
      <div class="grid">
        <div class="field full">
          <span class="label">Participant</span>
          <span class="value">{{ $participant->full_name }}</span>
        </div>
        <div class="field">
          <span class="label">Événement</span>
          <span class="value">{{ $participant->event?->name ?? 'Retraite' }}</span>
        </div>
        <div class="field">
          <span class="label">Référence</span>
          <span class="value">{{ $reference }}</span>
        </div>
        <div class="field">
          <span class="label">Paiement</span>
          <span class="value">{{ $paymentLabel }}</span>
        </div>
        <div class="field">
          <span class="label">Statut inscription</span>
          <span class="value">{{ ucfirst(str_replace('_', ' ', (string) $participant->registration_status)) }}</span>
        </div>
        <div class="field">
          <span class="label">Badge physique</span>
          <span class="value">{{ $badgeLabel }}</span>
        </div>
        @if($showPlacements)
          <div class="field">
            <span class="label">Chambre</span>
            <span class="value">{{ $participant->chambre?->nom ?? 'Non assignée' }}</span>
          </div>
          <div class="field">
            <span class="label">Atelier</span>
            <span class="value">{{ $participant->atelier?->numero ? 'Atelier '.$participant->atelier->numero : 'Non assigné' }}</span>
          </div>
        @endif
      </div>
      <p class="note">
        @if($accessGranted)
          L'inscription est confirmée. Vous pouvez accueillir ce participant selon les consignes sur place.
        @else
          L'accès n'est pas encore autorisé : paiement ou validation en cours. Orientez le participant vers l'accueil.
        @endif
      </p>
    </section>
  </main>
  @include('partials.cmp-portail.footer', ['compact' => true])
  </div>
</body>
</html>

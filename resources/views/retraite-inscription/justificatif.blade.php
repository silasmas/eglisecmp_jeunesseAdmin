@php
  $payment = $participant->payments->sortByDesc('id')->first();
  $status = $participant->paiement_valide
    ? ['label' => 'Inscription validee', 'class' => 'ok']
    : ['label' => 'En cours de validation', 'class' => 'pending'];
  $reference = $payment?->reference ?: '#'.$participant->id.'-'.substr((string) $participant->download_token, 0, 8);
  $participantPhotoUrl = $participant->getFilamentAvatarUrl();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Justificatif d'inscription - {{ $participant->full_name }}</title>
  <style>
    :root {
      --ink: #171015;
      --muted: #6d5964;
      --line: #eadce3;
      --paper: #fff;
      --soft: #fbf5f8;
      --primary: #851c46;
      --success: #146c43;
      --warning: #9a5b00;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 32px 18px;
      color: var(--ink);
      background: linear-gradient(180deg, #fff7fb 0%, #f5f1f3 100%);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .sheet {
      width: min(840px, 100%);
      margin: 0 auto;
      background: var(--paper);
      border: 1px solid var(--line);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 18px 55px rgba(56, 24, 41, .12);
    }
    .head {
      display: flex;
      justify-content: space-between;
      gap: 18px;
      padding: 28px;
      color: #fff;
      background: #851c46;
    }
    .eyebrow {
      margin: 0 0 8px;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
      opacity: .82;
    }
    h1 {
      margin: 0;
      font-size: clamp(28px, 4vw, 42px);
      line-height: 1.05;
      letter-spacing: 0;
    }
    .ref-box {
      min-width: 210px;
      align-self: flex-start;
      padding: 14px 16px;
      border: 1px solid rgba(255,255,255,.32);
      border-radius: 12px;
      background: rgba(255,255,255,.1);
      text-align: right;
    }
    .ref-box span { display: block; font-size: 12px; opacity: .8; }
    .ref-box strong { display: block; margin-top: 5px; font-size: 18px; overflow-wrap: anywhere; }
    .content { padding: 28px; }
    .status {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
      padding: 9px 13px;
      border-radius: 999px;
      font-weight: 800;
      background: #fff4dc;
      color: var(--warning);
    }
    .status.ok { background: #e9f7ef; color: var(--success); }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }
    .field {
      min-height: 76px;
      padding: 15px;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: var(--soft);
    }
    .field.full { grid-column: 1 / -1; }
    .participant-photo-wrap {
      display: flex;
      justify-content: center;
      margin: 0 0 16px;
    }
    .participant-photo {
      width: 118px;
      height: 118px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid #eadce3;
      background: #f3ebef;
    }
    .label {
      display: block;
      margin-bottom: 6px;
      color: var(--muted);
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .06em;
    }
    .value { font-size: 16px; font-weight: 700; overflow-wrap: anywhere; }
    .note {
      margin: 22px 0 0;
      padding: 16px;
      border-left: 4px solid var(--primary);
      border-radius: 10px;
      background: #fff8fb;
      color: var(--muted);
      line-height: 1.55;
    }
    .actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      padding: 18px 28px 28px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 44px;
      padding: 0 18px;
      border: 1px solid var(--line);
      border-radius: 10px;
      color: var(--primary);
      background: #fff;
      font-weight: 800;
      text-decoration: none;
      cursor: pointer;
    }
    .btn.primary {
      border-color: var(--primary);
      color: #fff;
      background: var(--primary);
    }
    @media (max-width: 640px) {
      body { padding: 12px; }
      .head { flex-direction: column; padding: 22px; }
      .ref-box { width: 100%; text-align: left; }
      .content { padding: 20px; }
      .grid { grid-template-columns: 1fr; }
      .actions { flex-direction: column; padding: 0 20px 20px; }
      .btn { width: 100%; }
    }
    @media print {
      body { padding: 0; background: #fff; }
      .sheet { width: 100%; border: 0; border-radius: 0; box-shadow: none; }
      .actions { display: none; }
    }
  </style>
</head>
<body>
  <main class="sheet">
    <header class="head">
      <div>
        <p class="eyebrow">Jeunesse CMP</p>
        <h1>Justificatif d'inscription</h1>
      </div>
      <div class="ref-box">
        <span>Reference</span>
        <strong>{{ $reference }}</strong>
      </div>
    </header>

    <section class="content">
      <div class="status {{ $status['class'] }}">{{ $status['label'] }}</div>
      <div class="participant-photo-wrap">
        <img class="participant-photo" src="{{ $participantPhotoUrl }}" alt="Photo du participant">
      </div>

      <div class="grid">
        <div class="field full">
          <span class="label">Participant</span>
          <span class="value">{{ $participant->full_name }}</span>
        </div>
        <div class="field">
          <span class="label">Evenement</span>
          <span class="value">{{ $participant->event?->name ?? 'Retraite' }}</span>
        </div>
        <div class="field">
          <span class="label">Date</span>
          <span class="value">{{ $participant->event?->start_at?->format('d/m/Y H:i') ?? 'A confirmer' }}</span>
        </div>
        <div class="field">
          <span class="label">Telephone</span>
          <span class="value">{{ $participant->telephone ?: '-' }}</span>
        </div>
        <div class="field">
          <span class="label">E-mail</span>
          <span class="value">{{ $participant->email ?: '-' }}</span>
        </div>
        <div class="field">
          <span class="label">Chambre</span>
          <span class="value">{{ $participant->chambre?->nom ?? 'Non assignee' }}</span>
        </div>
        <div class="field">
          <span class="label">Atelier</span>
          <span class="value">{{ $participant->atelier?->numero ? 'Atelier '.$participant->atelier->numero : 'Non assigne' }}</span>
        </div>
        <div class="field">
          <span class="label">Paiement</span>
          <span class="value">{{ $payment?->etat ? ucfirst(str_replace('_', ' ', $payment->etat)) : 'Non renseigne' }}</span>
        </div>
        <div class="field">
          <span class="label">Montant attendu</span>
          <span class="value">{{ $payment ? trim(($payment->amount_expected ?? '-').' '.($payment->currency ?? '')) : '-' }}</span>
        </div>
      </div>

      <p class="note">
        Ce document reprend les informations connues au moment de la consultation. Si le dossier est encore en validation, les organisateurs peuvent le confirmer depuis l'espace ouvrier.
      </p>
    </section>

    <div class="actions">
      <a class="btn" href="{{ url('/') }}">Retour au portail</a>
      <button class="btn primary" type="button" onclick="window.print()">Telecharger / imprimer</button>
    </div>
  </main>
</body>
</html>

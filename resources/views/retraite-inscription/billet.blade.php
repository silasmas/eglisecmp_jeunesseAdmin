@php
  $reference = $payment?->reference ?: '#'.$participant->id.'-'.substr((string) $participant->download_token, 0, 8);
  $participantPhotoUrl = $participant->getFilamentAvatarUrl();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Billet — {{ $participant->full_name }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('retraite-inscription/css/tokens.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-layout.css') }}">
  <link rel="stylesheet" href="{{ asset('cmp-portail/css/cmp-footer.css') }}">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
  <style>
    :root {
      --ink: #171015;
      --muted: #6d5964;
      --line: #eadce3;
      --paper: #fff;
      --soft: #fbf5f8;
      --primary: #851c46;
      --success: #146c43;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 32px 18px 0;
      color: var(--ink);
      background: linear-gradient(180deg, #fff7fb 0%, #f5f1f3 100%);
      font-family: Poppins, var(--font, Inter), ui-sans-serif, system-ui, sans-serif;
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
      background: var(--primary);
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
      font-size: clamp(28px, 4vw, 40px);
      line-height: 1.05;
    }
    .ref-box {
      min-width: 210px;
      padding: 14px 16px;
      border: 1px solid rgba(255,255,255,.32);
      border-radius: 12px;
      background: rgba(255,255,255,.1);
      text-align: right;
    }
    .ref-box span { display: block; font-size: 12px; opacity: .8; }
    .ref-box strong { display: block; margin-top: 5px; font-size: 18px; overflow-wrap: anywhere; }
    .content { padding: 28px; }
    .badge-ok {
      display: inline-flex;
      margin-bottom: 20px;
      padding: 9px 13px;
      border-radius: 999px;
      font-weight: 800;
      background: #e9f7ef;
      color: var(--success);
    }
    .layout {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 24px;
      align-items: start;
    }
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
    .photo-wrap { display: flex; justify-content: center; margin-bottom: 16px; }
    .photo {
      width: 118px;
      height: 118px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid var(--line);
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
    .qr-box {
      padding: 16px;
      border: 1px solid var(--line);
      border-radius: 14px;
      background: #fff;
      text-align: center;
      min-width: 180px;
    }
    .qr-box strong { display: block; margin-bottom: 10px; color: var(--primary); }
    .qr-mount { display: inline-block; min-height: 132px; min-width: 132px; }
    .qr-hint {
      margin: 12px 0 0;
      font-size: 12px;
      color: var(--muted);
      line-height: 1.45;
      max-width: 180px;
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
    .btn.qr-download {
      border-color: #146c43;
      color: #146c43;
      background: #fff;
    }
    #qrDownloadMount {
      position: absolute;
      left: -9999px;
      top: 0;
      width: 1px;
      height: 1px;
      overflow: hidden;
    }
    @media (max-width: 720px) {
      .layout { grid-template-columns: 1fr; }
      .qr-box { margin: 0 auto; }
      .grid { grid-template-columns: 1fr; }
      .head { flex-direction: column; }
      .ref-box { width: 100%; text-align: left; }
    }
    @media print {
      body { padding: 0; background: #fff; }
      .sheet { width: 100%; border: 0; box-shadow: none; }
      .actions { display: none; }
    }
  </style>
</head>
<body>
  <div class="cmp-page-shell">
  <main class="sheet">
    <header class="head">
      <div>
        <p class="eyebrow">Jeunesse CMP</p>
        <h1>Billet participant</h1>
      </div>
      <div class="ref-box">
        <span>Référence</span>
        <strong>{{ $reference }}</strong>
      </div>
    </header>

    <section class="content">
      <div class="badge-ok">Paiement validé — accès autorisé</div>

      <div class="photo-wrap">
        <img class="photo" src="{{ $participantPhotoUrl }}" alt="Photo du participant">
      </div>

      <div class="layout">
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
            <span class="label">Date</span>
            <span class="value">{{ $participant->event?->start_at?->format('d/m/Y H:i') ?? 'À confirmer' }}</span>
          </div>
          <div class="field">
            <span class="label">Téléphone</span>
            <span class="value">{{ $participant->telephone ?: '—' }}</span>
          </div>
          <div class="field">
            <span class="label">E-mail</span>
            <span class="value">{{ $participant->email ?: '—' }}</span>
          </div>
          <div class="field">
            <span class="label">Montant payé</span>
            <span class="value">{{ trim(($payment?->amount_paid ?? $payment?->amount_expected ?? '—').' '.($payment?->currency ?? '')) }}</span>
          </div>
          <div class="field">
            <span class="label">Canal de paiement</span>
            <span class="value">{{ $payment?->channel ? ucfirst(str_replace('_', ' ', $payment->channel)) : '—' }}</span>
          </div>
        </div>

        <aside class="qr-box">
          <strong>Contrôle d'accès</strong>
          <div id="billetQrMount" class="qr-mount" aria-hidden="true"></div>
          <p class="qr-hint">Présentez ce code à l'accueil : il affiche l'état de votre inscription.</p>
        </aside>
      </div>
    </section>

    <div class="actions">
      <a class="btn" href="{{ url('/') }}">Portail</a>
      <button class="btn qr-download" type="button" id="downloadQrBtn">Télécharger QR seul</button>
      <button class="btn primary" type="button" onclick="window.print()">Imprimer</button>
    </div>
  </main>
  @include('partials.cmp-portail.footer', ['compact' => true])
  </div>
  <div id="qrDownloadMount" aria-hidden="true"></div>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var url = @json($accessUrl);
      var mount = document.getElementById('billetQrMount');
      if (!mount || typeof QRCode !== 'function' || !url) {
        return;
      }

      mount.innerHTML = '';
      new QRCode(mount, {
        text: url,
        width: 132,
        height: 132,
        colorDark: '#1a1018',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });

      var img = mount.querySelector('img');
      if (img) {
        img.alt = 'QR code billet retraite';
      }

      var downloadBtn = document.getElementById('downloadQrBtn');
      if (!downloadBtn) {
        return;
      }

      downloadBtn.addEventListener('click', function () {
        var exportSize = 512;
        var outerPadding = 48;
        var innerPadding = 24;
        var tempMount = document.getElementById('qrDownloadMount');
        tempMount.innerHTML = '';

        new QRCode(tempMount, {
          text: url,
          width: exportSize,
          height: exportSize,
          colorDark: '#000000',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });

        var buildAndDownload = function () {
          var sourceCanvas = tempMount.querySelector('canvas');
          if (!sourceCanvas) {
            var qrImg = tempMount.querySelector('img');
            if (!qrImg || !qrImg.src) {
              return;
            }
            sourceCanvas = document.createElement('canvas');
            sourceCanvas.width = exportSize;
            sourceCanvas.height = exportSize;
            var sourceCtx = sourceCanvas.getContext('2d');
            sourceCtx.fillStyle = '#ffffff';
            sourceCtx.fillRect(0, 0, exportSize, exportSize);
            sourceCtx.drawImage(qrImg, 0, 0, exportSize, exportSize);
          }

          var cardSize = exportSize + (innerPadding * 2);
          var canvas = document.createElement('canvas');
          canvas.width = cardSize + (outerPadding * 2);
          canvas.height = cardSize + (outerPadding * 2);
          var ctx = canvas.getContext('2d');

          ctx.fillStyle = '#e8ecef';
          ctx.fillRect(0, 0, canvas.width, canvas.height);

          ctx.fillStyle = '#ffffff';
          ctx.fillRect(outerPadding, outerPadding, cardSize, cardSize);

          ctx.drawImage(sourceCanvas, outerPadding + innerPadding, outerPadding + innerPadding, exportSize, exportSize);

          var link = document.createElement('a');
          link.download = 'qrcode-billet-{{ Str::slug($participant->nom.'-'.$participant->prenom, '-') }}.jpg';
          link.href = canvas.toDataURL('image/jpeg', 0.92);
          link.click();
          tempMount.innerHTML = '';
        };

        window.requestAnimationFrame(function () {
          window.requestAnimationFrame(buildAndDownload);
        });
      });
    });
  </script>
</body>
</html>

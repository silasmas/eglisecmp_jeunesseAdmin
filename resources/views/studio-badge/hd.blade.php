<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Studio badges HD — CMP Jeunesse</title>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bebas+Neue&family=Bungee&family=Inter:wght@400;600;800;900&family=Montserrat:wght@400;700;900&family=Oswald:wght@400;600;700&family=Playfair+Display:wght@700;900&family=Poppins:wght@300;400;500;600;700;800;900&family=Raleway:wght@700;900&family=Roboto+Condensed:wght@700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  @vite($viteEntry)
  <style>
    .studio-badge-topnav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      max-width: 1520px;
      margin: 0 auto 0.75rem;
      padding: 0 1.2rem;
      flex-wrap: wrap;
    }
    .studio-badge-topnav a {
      color: #475569;
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
    }
    .studio-badge-topnav a:hover { color: #c01420; }
    .studio-badge-topnav a.is-active { color: #c01420; }
    .studio-badge-topnav-actions {
      display: flex;
      gap: 0.75rem;
      align-items: center;
    }
    .studio-badge-topnav form { margin: 0; }
    .studio-badge-topnav button {
      border: 1px solid rgba(15,23,42,0.12);
      border-radius: 8px;
      background: #fff;
      padding: 0.35rem 0.75rem;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      color: #475569;
    }
    .studio-badge-topnav button:hover {
      border-color: #c01420;
      color: #c01420;
    }
    .studio-badge-root-mount { min-height: calc(100vh - 56px); }
    .studio-variant-pill {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      background: #fff7ed;
      color: #9a3412;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 0.15rem 0.55rem;
    }
  </style>
</head>
<body>
  <nav class="studio-badge-topnav" aria-label="Navigation studio badges HD">
    <div>
      <a href="{{ $adminUrl }}">← Tableau de bord</a>
      ·
      <a href="{{ $classicUrl }}">Studio classique</a>
      ·
      <a href="{{ $hdUrl }}" class="is-active">Studio HD</a>
      @if (!empty($sessionEventName))
        ·
        <span style="font-size:0.78rem;color:#64748b;">Session : <strong style="color:#0f172a;">{{ $sessionEventName }}</strong></span>
      @endif
      ·
      <span class="studio-variant-pill">V2 HD badgecmp</span>
    </div>
    <div class="studio-badge-topnav-actions">
      <span style="font-size:0.78rem;color:#94a3b8;">{{ $sessionUserName ?: (auth()->user()->name ?? auth()->user()->email) }}</span>
      <form method="post" action="{{ $logoutUrl }}">
        @csrf
        <button type="submit">Déconnexion</button>
      </form>
    </div>
  </nav>

  <div
    id="studio-badge-root"
    class="studio-badge-root-mount"
    data-template-url="{{ $templateUrl }}"
    data-asset-base-url="{{ $assetBaseUrl }}"
    data-api-participants="{{ $apiParticipantsUrl }}"
    data-api-session="{{ $sessionApiUrl }}"
    data-session-event-name="{{ $sessionEventName ?? '' }}"
    data-session-user-name="{{ $sessionUserName }}"
  ></div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Studio badges — CMP Jeunesse</title>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bebas+Neue&family=Bungee&family=Inter:wght@400;600;800;900&family=Montserrat:wght@400;700;900&family=Oswald:wght@400;600;700&family=Playfair+Display:wght@700;900&family=Poppins:wght@300;400;500;600;700;800;900&family=Raleway:wght@700;900&family=Roboto+Condensed:wght@700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  @vite(['resources/css/studio-badge.css', 'resources/js/studio-badge/main.tsx'])
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

    .studio-badge-root-mount {
      min-height: calc(100vh - 56px);
    }
  </style>
</head>
<body>
  <nav class="studio-badge-topnav" aria-label="Navigation studio badges">
    <div>
      <a href="{{ $portalUrl }}">← Portail retraite</a>
      ·
      <a href="{{ $adminUrl }}">Administration</a>
    </div>
    <div class="studio-badge-topnav-actions">
      <span style="font-size:0.78rem;color:#94a3b8;">{{ auth()->user()->name ?? auth()->user()->email }}</span>
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
    data-api-participants="{{ route('studio-badge.api.participants') }}"
  ></div>
</body>
</html>

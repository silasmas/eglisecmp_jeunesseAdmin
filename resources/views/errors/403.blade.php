<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accès refusé — Jeunesse CMP</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #faf8f6;
            color: #1f2937;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            max-width: 560px;
            width: 100%;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
        h1 {
            margin: 0 0 0.75rem;
            font-size: 1.5rem;
            color: #b42318;
        }
        p {
            margin: 0 0 1rem;
            line-height: 1.6;
        }
        .hint {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            font-size: 0.95rem;
        }
        a {
            color: #146c43;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Accès refusé (403)</h1>
        <p>{{ $message ?? 'Vous n\'avez pas les droits nécessaires pour accéder à cette ressource.' }}</p>
        <div class="hint">
            Cette page s'affiche lorsque votre compte n'a pas la permission requise, que la session a expiré,
            ou que le fichier demandé (ex. preuve de paiement) n'est pas accessible via un lien direct.
            Les super administrateurs peuvent consulter les preuves depuis l'administration « Paiements cash ».
        </div>
        <p style="margin-top: 1.25rem;">
            <a href="{{ url('/admin') }}">Retour à l'administration</a>
            ·
            <a href="{{ url('/') }}">Portail retraite</a>
        </p>
    </div>
</body>
</html>

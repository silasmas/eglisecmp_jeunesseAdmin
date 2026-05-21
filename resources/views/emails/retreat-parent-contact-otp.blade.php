<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Code OTP parent/tuteur</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;color:#1f1b22;line-height:1.55;">
    <h2 style="margin:0 0 12px;">Vérification parent/tuteur</h2>
    <p style="margin:0 0 12px;">
        Utilisez ce code OTP pour confirmer l'adresse e-mail saisie pendant l'inscription.
    </p>
    <p style="margin:0 0 14px;font-size:30px;font-weight:700;letter-spacing:5px;">
        {{ $otp }}
    </p>
    <p style="margin:0 0 8px;">
        Ce code expire dans {{ $expiresInMinutes }} minute(s).
    </p>
    <p style="margin:0;color:#6f6471;">
        Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail.
    </p>
</body>
</html>

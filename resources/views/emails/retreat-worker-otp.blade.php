<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Code de verification</title>
</head>
<body style="margin:0;padding:0;background:#f8f3f5;color:#19151a;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f3f5;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border:1px solid #eadde3;border-radius:8px;padding:28px;">
                    <tr>
                        <td>
                            <h1 style="margin:0 0 14px;font-size:22px;color:#7b1d3e;">Verification ouvrier</h1>
                            <p style="margin:0 0 18px;line-height:1.6;">Voici votre code pour acceder a la verification des inscriptions de la retraite.</p>
                            <p style="margin:0 0 18px;font-size:34px;font-weight:800;letter-spacing:6px;color:#19151a;">{{ $otp }}</p>
                            <p style="margin:0;line-height:1.6;color:#6d6470;">Ce code expire dans {{ $expiresInMinutes }} minutes. Si vous n'avez pas demande ce code, ignorez ce message.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

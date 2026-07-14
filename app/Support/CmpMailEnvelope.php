<?php

namespace App\Support;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Enveloppe e-mail standard Jeunesse CMP (expéditeur, reply-to, en-têtes anti-spam).
 */
final class CmpMailEnvelope
{
    /**
     * @param string $subject Sujet de l'e-mail
     * @param Address|null $replyTo Adresse de réponse (optionnelle)
     * @return Envelope Enveloppe configurée
     */
    public static function make(string $subject, ?Address $replyTo = null): Envelope
    {
        $replyAddress = trim((string) config('retraite.mail_reply_to', ''));
        $replyName = (string) config('retraite.mail_reply_to_name', 'Jeunesse CMP');

        $resolvedReplyTo = $replyTo;

        if ($resolvedReplyTo === null && $replyAddress !== '') {
            $resolvedReplyTo = new Address($replyAddress, $replyName);
        }

        return new Envelope(
            subject: $subject,
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name')
            ),
            replyTo: $resolvedReplyTo !== null ? [$resolvedReplyTo] : [],
            tags: ['jeunesse-cmp'],
            metadata: [
                'organization' => 'Jeunesse CMP',
            ],
        );
    }
}

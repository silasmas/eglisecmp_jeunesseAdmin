<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Construit des SMS courts tenus en un seul segment (160 caractères GSM).
 */
class RetreatSmsMessageBuilder
{
    public const SINGLE_SMS_MAX_LENGTH = 160;

    /**
     * Message de confirmation billet (1 SMS max, sans accents).
     *
     * @param string $participantName Prénom ou nom du participant
     * @param string $billetUrl Lien billet public
     * @return string Corps SMS
     */
    public function billetConfirmation(string $participantName, string $billetUrl): string
    {
        $name = $this->toGsmSafe(Str::limit(trim($participantName), 22, ''));
        $url = trim($billetUrl);

        $candidates = [
            "CMP - {$name}, billet: {$url}",
            "CMP billet: {$url}",
            "CMP: {$url}",
        ];

        foreach ($candidates as $candidate) {
            if (strlen($candidate) <= self::SINGLE_SMS_MAX_LENGTH) {
                return $candidate;
            }
        }

        return Str::limit($url, self::SINGLE_SMS_MAX_LENGTH, '');
    }

    /**
     * @param string $value Texte source
     * @return string Texte compatible GSM 7-bit
     */
    protected function toGsmSafe(string $value): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($transliterated === false) {
            $transliterated = $value;
        }

        $clean = preg_replace('/[^\x20-\x7E]/', '', $transliterated) ?? '';

        return trim(preg_replace('/\s+/', ' ', $clean) ?? '');
    }
}

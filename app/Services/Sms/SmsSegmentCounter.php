<?php

namespace App\Services\Sms;

/**
 * Compte caractères et segments SMS (GSM-7 vs UCS-2).
 */
class SmsSegmentCounter
{
    /** Longueur max d’un SMS GSM unique. */
    public const GSM_SINGLE = 160;

    /** Longueur utile par segment GSM concaténé. */
    public const GSM_CONCAT = 153;

    /** Longueur max d’un SMS Unicode unique. */
    public const UCS_SINGLE = 70;

    /** Longueur utile par segment Unicode concaténé. */
    public const UCS_CONCAT = 67;

    /**
     * Analyse un message pour l’encodage et le nombre de segments.
     *
     * @param  string  $message  Corps SMS
     * @return array{
     *     encoding: 'gsm'|'ucs2',
     *     character_count: int,
     *     segments: int,
     *     max_single: int,
     *     chars_per_segment: int,
     *     remaining_in_segment: int
     * }
     */
    public function analyze(string $message): array
    {
        $isGsm = $this->isGsmCompatible($message);
        $characterCount = $isGsm
            ? $this->gsmLength($message)
            : mb_strlen($message, 'UTF-8');

        $maxSingle = $isGsm ? self::GSM_SINGLE : self::UCS_SINGLE;
        $perSegment = $isGsm ? self::GSM_CONCAT : self::UCS_CONCAT;

        if ($characterCount === 0) {
            $segments = 0;
            $remaining = $maxSingle;
        } elseif ($characterCount <= $maxSingle) {
            $segments = 1;
            $remaining = $maxSingle - $characterCount;
        } else {
            $segments = (int) ceil($characterCount / $perSegment);
            $usedInLast = $characterCount % $perSegment;
            $remaining = $usedInLast === 0 ? 0 : ($perSegment - $usedInLast);
        }

        return [
            'encoding' => $isGsm ? 'gsm' : 'ucs2',
            'character_count' => $characterCount,
            'segments' => $segments,
            'max_single' => $maxSingle,
            'chars_per_segment' => $characterCount <= $maxSingle ? $maxSingle : $perSegment,
            'remaining_in_segment' => $remaining,
        ];
    }

    /**
     * Indique si le texte tient en GSM-7 (alphabet de base + extension).
     *
     * @param  string  $message  Corps SMS
     */
    public function isGsmCompatible(string $message): bool
    {
        $length = mb_strlen($message, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($message, $i, 1, 'UTF-8');
            if (! $this->isGsmChar($char)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Longueur GSM (caractères d’extension = 2).
     *
     * @param  string  $message  Corps SMS GSM
     */
    public function gsmLength(string $message): int
    {
        $length = 0;
        $count = mb_strlen($message, 'UTF-8');

        for ($i = 0; $i < $count; $i++) {
            $char = mb_substr($message, $i, 1, 'UTF-8');
            $length += $this->isGsmExtendedChar($char) ? 2 : 1;
        }

        return $length;
    }

    /**
     * @param  string  $char  Caractère Unicode
     */
    protected function isGsmChar(string $char): bool
    {
        return $this->isGsmBasicChar($char) || $this->isGsmExtendedChar($char);
    }

    /**
     * Alphabet GSM 7-bit de base.
     *
     * @param  string  $char  Caractère
     */
    protected function isGsmBasicChar(string $char): bool
    {
        static $basic = null;

        if ($basic === null) {
            $basic = array_flip(preg_split('//u',
                "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà",
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: []);
        }

        return isset($basic[$char]);
    }

    /**
     * Table d’extension GSM (coût 2).
     *
     * @param  string  $char  Caractère
     */
    protected function isGsmExtendedChar(string $char): bool
    {
        return in_array($char, ['^', '{', '}', '\\', '[', '~', ']', '|', '€'], true);
    }
}

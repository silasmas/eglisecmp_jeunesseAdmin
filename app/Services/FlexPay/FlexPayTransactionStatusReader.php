<?php

namespace App\Services\FlexPay;

/**
 * Extraction du statut numérique d'une transaction FlexPay Mobile Money.
 */
class FlexPayTransactionStatusReader
{
    /**
     * Retourne le statut brut (0 = payé, 1 = annulé, 2 = en cours) ou null si indisponible.
     *
     * @param array<string, mixed> $payload Réponse FlexPay
     * @return mixed
     */
    public function extractStatus(array $payload): mixed
    {
        $paths = [
            'transaction.status',
            'transaction.TransactionStatus',
            'Transaction.status',
            'status',
            'data.transaction.status',
            'data.status',
            'result.status',
            'payment.status',
        ];

        foreach ($paths as $path) {
            $raw = data_get($payload, $path);
            if ($raw !== null && $raw !== '' && is_numeric($raw)) {
                return $raw;
            }
        }

        $transaction = data_get($payload, 'transaction');
        if (is_array($transaction)) {
            foreach (['status', 'Status', 'transactionStatus'] as $key) {
                if (isset($transaction[$key]) && $transaction[$key] !== '' && is_numeric($transaction[$key])) {
                    return $transaction[$key];
                }
            }
        }

        return null;
    }

    /**
     * Fusionne payload racine et sous-clé data si présente.
     *
     * @param array<string, mixed> $payload Réponse FlexPay
     * @return array<string, mixed>
     */
    public function mergePayload(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return array_merge($payload, $payload['data']);
        }

        return $payload;
    }
}

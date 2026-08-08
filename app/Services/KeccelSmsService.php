<?php

namespace App\Services;

use App\Models\SmsMessageLog;
use App\Models\SmsOperator;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class KeccelSmsService
{
    protected ?SmsMessageLog $lastLog = null;

    public function send(string $to, string $message, ?string $context = null, ?SmsOperator $operator = null): Response
    {
        $operator ??= $this->resolveOperator();
        $url = $operator ? (string) $operator->send_url : (string) config('services.sms.url');
        $token = $operator ? trim((string) $operator->token) : trim((string) config('services.sms.token'));
        $from = $operator ? trim((string) $operator->sender) : trim((string) config('services.sms.from', 'CMP'));
        $configuredMethod = strtoupper($operator ? (string) $operator->send_method : 'POST');
        $timeout = (int) config('services.sms.timeout', 15);

        if ($url === '' || $token === '' || $from === '') {
            throw new RuntimeException('Configuration SMS Keccel incomplète.');
        }

        $phone = $this->normalizeRecipient($to);
        if ($phone === '') {
            throw new RuntimeException('Numéro destinataire SMS invalide.');
        }

        $log = $this->createLog($operator, $phone, $message, $from, $context);

        $payload = [
            'token' => $token,
            'from' => $from,
            'to' => $phone,
            'message' => $message,
        ];

        $method = $configuredMethod === 'GET' ? 'GET' : 'POST';
        $response = $this->sendRequest($url, $payload, $method, $timeout);

        if ($response->failed() || $this->looksRejected($response)) {
            $errorMessage = $this->providerErrorMessage($response) ?: 'Keccel a refusé l’envoi du SMS.';
            $this->markLogFailed($log, $response, $method, $errorMessage);
            Log::warning('Envoi SMS Keccel refusé', [
                'to' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException($errorMessage);
        }

        $this->markLogSent($log, $response, $method);

        return $response;
    }

    public function lastLog(): ?SmsMessageLog
    {
        return $this->lastLog?->fresh();
    }

    /**
     * Consulte le solde Keccel et persiste remaining_sms (peut être négatif si compte expiré).
     *
     * @param  SmsOperator|null  $operator  Opérateur ciblé (sinon actif)
     * @return int|null Solde numérique ou null si non parsable
     */
    public function refreshBalance(?SmsOperator $operator = null): ?int
    {
        $operator ??= $this->resolveOperator();
        if (! $operator || blank($operator->balance_url)) {
            throw new RuntimeException('Aucune URL de consultation du solde SMS n’est configurée.');
        }

        $token = trim((string) $operator->token);
        $from = trim((string) $operator->sender);
        $payload = [
            'token' => $token,
            'from' => $from,
            'FROM' => $from,
        ];

        // Docs Keccel : GET sur balance.asp ; on tente GET puis POST en secours.
        $timeout = (int) config('services.sms.timeout', 15);
        $url = (string) $operator->balance_url;
        $response = Http::timeout($timeout)
            ->withToken($token)
            ->acceptJson()
            ->get($url, $payload);

        if ($response->failed() || $this->looksRejected($response)) {
            $response = Http::timeout($timeout)
                ->withToken($token)
                ->asJson()
                ->post($url, $payload);
        }

        if ($response->failed() || $this->looksRejected($response)) {
            $operator->update([
                'last_balance_checked_at' => now(),
                'last_balance_response' => $response->body(),
            ]);

            throw new RuntimeException($this->providerErrorMessage($response) ?: 'Impossible de consulter le solde SMS.');
        }

        $balance = $this->extractBalance($response);
        $operator->update([
            'remaining_sms' => $balance,
            'last_balance_checked_at' => now(),
            'last_balance_response' => $response->body(),
        ]);

        return $balance;
    }

    /**
     * Extrait expiration / statut compte depuis la dernière réponse solde.
     *
     * @param  string|null  $body  Corps JSON Keccel
     * @return array{expiration: ?string, account_status: ?string, is_expired: bool}
     */
    public function parseBalanceMeta(?string $body): array
    {
        $expiration = null;
        $accountStatus = null;
        $json = json_decode((string) $body, true);
        if (is_array($json)) {
            $expiration = isset($json['expiration']) ? trim((string) $json['expiration']) : null;
            $accountStatus = isset($json['status']) ? trim((string) $json['status']) : null;
            if ($expiration === '') {
                $expiration = null;
            }
            if ($accountStatus === '') {
                $accountStatus = null;
            }
        }

        $isExpired = false;
        if ($expiration !== null) {
            try {
                $isExpired = now()->greaterThan(Carbon::parse($expiration));
            } catch (\Throwable) {
                $isExpired = false;
            }
        }

        return [
            'expiration' => $expiration,
            'account_status' => $accountStatus,
            'is_expired' => $isExpired,
        ];
    }

    public function describeResponse(?string $body): array
    {
        $body = trim((string) $body);
        if ($body === '') {
            return [
                'type' => 'vide',
                'preview' => '',
            ];
        }

        json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'type' => 'json',
                'preview' => $body,
            ];
        }

        if (str_starts_with($body, '<')) {
            return [
                'type' => 'xml/html',
                'preview' => $body,
            ];
        }

        if (str_contains($body, '=') || str_contains($body, '&')) {
            return [
                'type' => 'texte clé-valeur',
                'preview' => $body,
            ];
        }

        return [
            'type' => 'texte brut',
            'preview' => $body,
        ];
    }

    /**
     * Interroge Keccel (delivery.asp) pour l’accusé de réception d’un SMS logué.
     *
     * @param  SmsMessageLog  $log  Log d’envoi avec provider_reference
     * @return SmsMessageLog Log actualisé
     */
    public function refreshDelivery(SmsMessageLog $log): SmsMessageLog
    {
        $operator = $log->operator ?: $this->resolveOperator();
        $deliveryUrl = $operator ? (string) $operator->delivery_url : (string) config('services.sms.delivery_url');
        $token = $operator ? trim((string) $operator->token) : trim((string) config('services.sms.token'));
        $from = $operator ? trim((string) $operator->sender) : trim((string) config('services.sms.from', 'CMP'));

        if (blank($deliveryUrl)) {
            throw new RuntimeException('Aucune URL de vérification de livraison SMS n’est configurée.');
        }
        if (blank($log->provider_reference)) {
            throw new RuntimeException('Référence Keccel du SMS introuvable.');
        }

        $payload = [
            'from' => $from,
            'FROM' => $from,
            'token' => $token,
            'messageid' => $log->provider_reference,
        ];
        $timeout = (int) config('services.sms.timeout', 15);

        // Docs Keccel : GET ; POST en secours pour les configs historiques.
        $response = Http::timeout($timeout)
            ->withToken($token)
            ->acceptJson()
            ->get($deliveryUrl, $payload);

        if ($response->failed() || $this->extractDeliveryStatus($response) === 'ERROR') {
            $response = Http::timeout($timeout)
                ->withToken($token)
                ->asJson()
                ->post($deliveryUrl, $payload);
        }

        $deliveryStatus = $this->extractDeliveryStatus($response);
        $log->update([
            'status' => $this->statusFromDelivery($deliveryStatus, $log->status),
            'delivery_status' => $deliveryStatus,
            'delivery_checked_at' => now(),
            'delivery_response' => $response->body(),
            'error_message' => $response->failed() ? 'Impossible de vérifier la livraison du SMS.' : $log->error_message,
        ]);

        return $log->fresh();
    }

    /**
     * Actualise les accusés pour une liste de logs (continue sur erreur individuelle).
     *
     * @param  iterable<int, SmsMessageLog|int>  $logsOrIds  Logs ou IDs
     * @return array{checked: int, delivered: int, failed: int, errors: int}
     */
    public function refreshDeliveries(iterable $logsOrIds): array
    {
        $stats = ['checked' => 0, 'delivered' => 0, 'failed' => 0, 'errors' => 0];

        foreach ($logsOrIds as $item) {
            $log = $item instanceof SmsMessageLog
                ? $item
                : SmsMessageLog::query()->find((int) $item);

            if (! $log || blank($log->provider_reference)) {
                $stats['errors']++;

                continue;
            }

            try {
                $updated = $this->refreshDelivery($log);
                $stats['checked']++;
                $status = strtoupper((string) $updated->delivery_status);
                if (in_array($status, ['DELIVERED', 'READ'], true)) {
                    $stats['delivered']++;
                } elseif (in_array($status, ['FAILED', 'ERROR'], true)) {
                    $stats['failed']++;
                }
            } catch (\Throwable $e) {
                report($e);
                $stats['errors']++;
            }

            usleep(80000);
        }

        return $stats;
    }

    protected function resolveOperator(): ?SmsOperator
    {
        if (! Schema::hasTable('sms_operators')) {
            return null;
        }

        return SmsOperator::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    protected function sendRequest(string $url, array $payload, string $method, int $timeout): Response
    {
        $request = Http::timeout($timeout)->withToken((string) $payload['token']);

        return $method === 'GET'
            ? $request->get($url, $payload)
            : $request->asJson()->post($url, $payload);
    }

    /**
     * Normalise un numéro vers le format Keccel (243…).
     *
     * @param  string  $raw  Numéro saisi
     * @return string Digits normalisés ou chaîne vide si invalide
     */
    public function normalizePhone(string $raw): string
    {
        return $this->normalizeRecipient($raw);
    }

    /**
     * @param  string  $raw  Numéro brut
     */
    protected function normalizeRecipient(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', trim($raw)) ?: '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '243'.substr($digits, 1);
        }
        if (! str_starts_with($digits, '243')) {
            return '243'.ltrim($digits, '0');
        }

        return $digits;
    }

    protected function looksRejected(Response $response): bool
    {
        $body = strtolower(trim($response->body()));

        return str_contains($body, 'error')
            || str_contains($body, 'invalid')
            || str_contains($body, 'failed')
            || str_contains($body, 'rejected')
            || str_contains($body, 'parameter is empty')
            || str_contains($body, 'ko');
    }

    protected function providerErrorMessage(Response $response): ?string
    {
        $json = $response->json();
        if (is_array($json)) {
            foreach (['description', 'message', 'error', 'status'] as $key) {
                if (! blank($json[$key] ?? null)) {
                    return 'Keccel: '.(string) $json[$key];
                }
            }
        }

        $body = trim($response->body());

        return $body === '' ? null : 'Keccel: '.$body;
    }

    protected function createLog(?SmsOperator $operator, string $phone, string $message, string $from, ?string $context): ?SmsMessageLog
    {
        if (! Schema::hasTable('sms_message_logs')) {
            return null;
        }

        $payload = [
            'sms_operator_id' => $operator?->id,
            'provider' => $operator?->provider ?: 'keccel',
            'context' => $context,
            'sender' => $from,
            'recipient' => $phone,
            'message' => $message,
            'status' => 'pending',
        ];

        if (! Schema::hasColumn('sms_message_logs', 'sms_operator_id')) {
            unset($payload['sms_operator_id']);
        }

        return $this->lastLog = SmsMessageLog::query()->create($payload);
    }

    protected function markLogSent(?SmsMessageLog $log, Response $response, string $method): void
    {
        if (! $log) {
            return;
        }

        $log->update([
            'status' => 'sent',
            'delivery_status' => 'PENDING',
            'http_method' => $method,
            'http_status' => $response->status(),
            'provider_response' => $response->body(),
            'provider_reference' => $this->extractProviderReference($response),
            'sent_at' => now(),
        ]);
        $this->lastLog = $log->fresh();
    }

    protected function markLogFailed(?SmsMessageLog $log, Response $response, string $method, string $message): void
    {
        if (! $log) {
            return;
        }

        $log->update([
            'status' => 'failed',
            'delivery_status' => 'FAILED',
            'http_method' => $method,
            'http_status' => $response->status(),
            'provider_response' => $response->body(),
            'error_message' => $message,
        ]);
        $this->lastLog = $log->fresh();
    }

    protected function extractProviderReference(Response $response): ?string
    {
        $json = $response->json();
        if (is_array($json)) {
            foreach (['messageid', 'message_id', 'messageId', 'id', 'reference', 'ref'] as $key) {
                if (! empty($json[$key])) {
                    return (string) $json[$key];
                }
            }
        }

        if (preg_match('/(?:messageid|message_id|id)\s*[=:]\s*([A-Za-z0-9_-]+)/i', $response->body(), $m)) {
            return $m[1];
        }

        if (preg_match('/^\s*([0-9]{3,})\s*$/', $response->body(), $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Parse un solde numérique (positif ou négatif) depuis la réponse Keccel.
     *
     * @param  Response  $response  Réponse HTTP
     */
    protected function extractBalance(Response $response): ?int
    {
        $body = trim($response->body());
        $json = $response->json();
        if (is_array($json)) {
            foreach (['balance', 'solde', 'remaining', 'remaining_sms', 'sms', 'credit', 'credits'] as $key) {
                if (isset($json[$key]) && is_numeric($json[$key])) {
                    return (int) $json[$key];
                }
            }
        }

        if (preg_match('/(?:balance|solde|remaining|credits?)\s*[=:]\s*(-?[0-9]+)/i', $body, $m)) {
            return (int) $m[1];
        }

        if (str_contains($body, '&') || str_contains($body, '=')) {
            parse_str($body, $parsed);
            foreach (['balance', 'solde', 'remaining', 'remaining_sms', 'sms', 'credit', 'credits'] as $key) {
                if (isset($parsed[$key]) && is_numeric($parsed[$key])) {
                    return (int) $parsed[$key];
                }
            }
        }

        if (str_starts_with($body, '<')) {
            $xml = @simplexml_load_string($body);
            if ($xml !== false) {
                $flat = json_decode(json_encode($xml), true);
                if (is_array($flat)) {
                    foreach (['balance', 'solde', 'remaining', 'remaining_sms', 'sms', 'credit', 'credits'] as $key) {
                        if (isset($flat[$key]) && is_numeric($flat[$key])) {
                            return (int) $flat[$key];
                        }
                    }
                }
            }
        }

        if (preg_match('/^\s*(-?[0-9]+)\s*$/', $body, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    protected function extractDeliveryStatus(Response $response): string
    {
        if ($response->failed()) {
            return 'ERROR';
        }

        $json = $response->json();
        if (is_array($json) && ! empty($json['status'])) {
            return strtoupper((string) $json['status']);
        }

        if (preg_match('/status\s*[=:]\s*([A-Za-z]+)/i', $response->body(), $m)) {
            return strtoupper($m[1]);
        }

        $body = strtoupper(trim($response->body()));
        if (str_contains($body, 'DELIVERED')) {
            return 'DELIVERED';
        }
        if (str_contains($body, 'FAILED')) {
            return 'FAILED';
        }
        if (str_contains($body, 'ERROR')) {
            return 'ERROR';
        }

        return $body !== '' ? substr($body, 0, 40) : 'UNKNOWN';
    }

    /**
     * Mappe un statut DLR Keccel vers le statut métier du log.
     *
     * @param  string  $deliveryStatus  Statut fournisseur (DELIVERED, FAILED, …)
     * @param  string  $currentStatus  Statut actuel du log
     */
    protected function statusFromDelivery(string $deliveryStatus, string $currentStatus): string
    {
        return match ($deliveryStatus) {
            'DELIVERED', 'READ' => 'delivered',
            'FAILED', 'ERROR', 'REJECTED', 'EXPIRED' => 'failed',
            'PENDING', 'BUFFERED', 'ENROUTE', 'ACCEPTED' => 'sent',
            default => $currentStatus,
        };
    }
}

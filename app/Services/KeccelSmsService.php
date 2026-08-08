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
     * Consulte le solde Keccel (API v2 prioritaire) et persiste remaining_sms (>= 0 uniquement).
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
        $timeout = (int) config('services.sms.timeout', 15);

        $urls = $this->balanceEndpointCandidates((string) $operator->balance_url);
        $lastResponse = null;
        $rejectedNegative = null;

        foreach ($urls as $url) {
            $response = $this->requestBalance($url, $payload, $token, $timeout);
            $lastResponse = $response;

            if ($response->failed() || $this->looksRejected($response)) {
                continue;
            }

            $balance = $this->extractBalance($response);
            if ($balance === null) {
                continue;
            }

            // Un solde négatif (ex. -7 sur l’ancienne URL non-v2) n’est pas fiable : on ignore.
            if ($balance < 0) {
                $rejectedNegative = $balance;

                continue;
            }

            $operator->update([
                'remaining_sms' => $balance,
                'balance_url' => $this->preferV2SmsUrl((string) $operator->balance_url, 'balance'),
                'delivery_url' => $this->preferV2SmsUrl((string) ($operator->delivery_url ?: config('services.sms.delivery_url')), 'delivery'),
                'last_balance_checked_at' => now(),
                'last_balance_response' => $response->body(),
            ]);

            return $balance;
        }

        $operator->update([
            'last_balance_checked_at' => now(),
            'last_balance_response' => $lastResponse?->body(),
        ]);

        if ($rejectedNegative !== null) {
            throw new RuntimeException(
                "Keccel a renvoyé un solde incohérent ({$rejectedNegative}). Solde précédent conservé. Vérifiez l’URL v2 : https://api.keccel.com/sms/v2/balance.asp"
            );
        }

        $hint = $lastResponse ? $this->providerErrorMessage($lastResponse) : null;

        throw new RuntimeException($hint ?: 'Impossible de consulter le solde SMS.');
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
        $deliveryUrl = $this->preferV2SmsUrl(
            $operator ? (string) $operator->delivery_url : (string) config('services.sms.delivery_url'),
            'delivery'
        );
        $token = $operator ? trim((string) $operator->token) : trim((string) config('services.sms.token'));
        $from = $operator ? trim((string) $operator->sender) : trim((string) config('services.sms.from', 'CMP'));

        if (blank($deliveryUrl)) {
            throw new RuntimeException('Aucune URL de vérification de livraison SMS n’est configurée.');
        }
        if (blank($log->provider_reference)) {
            $recovered = $this->extractProviderReferenceFromBody($log->provider_response);
            if ($recovered) {
                $log->update(['provider_reference' => $recovered]);
                $log->provider_reference = $recovered;
            }
        }
        if (blank($log->provider_reference)) {
            throw new RuntimeException('Référence Keccel du SMS introuvable (messageID manquant).');
        }

        $payload = [
            'from' => $from,
            'FROM' => $from,
            'token' => $token,
            'messageid' => $log->provider_reference,
            'messageID' => $log->provider_reference,
        ];
        $timeout = (int) config('services.sms.timeout', 15);

        // Docs Keccel : GET v2/delivery.asp ; POST en secours.
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

    /**
     * Extrait le messageID Keccel (casse réelle : messageID) depuis une réponse HTTP.
     *
     * @param  Response  $response  Réponse d’envoi
     */
    protected function extractProviderReference(Response $response): ?string
    {
        return $this->extractProviderReferenceFromBody($response->body());
    }

    /**
     * Parse messageID depuis un corps JSON/texte (utile aussi pour rattrapage des logs).
     *
     * @param  string|null  $body  Corps brut Keccel
     */
    public function extractProviderReferenceFromBody(?string $body): ?string
    {
        $body = trim((string) $body);
        if ($body === '') {
            return null;
        }

        $json = json_decode($body, true);
        if (is_array($json)) {
            $normalized = [];
            foreach ($json as $key => $value) {
                $normalized[strtolower((string) $key)] = $value;
            }

            foreach (['messageid', 'message_id', 'id', 'reference', 'ref'] as $key) {
                if (! empty($normalized[$key]) && is_scalar($normalized[$key])) {
                    return trim((string) $normalized[$key]);
                }
            }
        }

        if (preg_match('/"messageID"\s*:\s*"([^"]+)"/i', $body, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/(?:messageid|message_id)\s*[=:]\s*([A-Za-z0-9_-]+)/i', $body, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/^\s*([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})\s*$/i', $body, $m)) {
            return $m[1];
        }

        if (preg_match('/^\s*([0-9]{3,})\s*$/', $body, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Rattrape provider_reference depuis provider_response pour les logs déjà envoyés.
     *
     * @return int Nombre de logs mis à jour
     */
    public function backfillMissingProviderReferences(): int
    {
        $updated = 0;

        SmsMessageLog::query()
            ->where(function ($q): void {
                $q->whereNull('provider_reference')->orWhere('provider_reference', '');
            })
            ->whereNotNull('provider_response')
            ->orderBy('id')
            ->chunkById(100, function ($logs) use (&$updated): void {
                foreach ($logs as $log) {
                    $ref = $this->extractProviderReferenceFromBody($log->provider_response);
                    if ($ref === null || $ref === '') {
                        continue;
                    }

                    $log->update(['provider_reference' => $ref]);
                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * @return list<string>
     */
    protected function balanceEndpointCandidates(string $configuredUrl): array
    {
        $candidates = [
            $this->preferV2SmsUrl($configuredUrl, 'balance'),
            'https://api.keccel.com/sms/v2/balance.asp',
            $configuredUrl,
        ];

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * @param  array<string, string>  $payload
     */
    protected function requestBalance(string $url, array $payload, string $token, int $timeout): Response
    {
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

        return $response;
    }

    /**
     * Force l’URL Keccel vers /sms/v2/{balance|delivery}.asp quand l’ancienne route est détectée.
     *
     * @param  string  $url  URL configurée
     * @param  string  $resource  balance|delivery
     */
    protected function preferV2SmsUrl(string $url, string $resource): string
    {
        $url = trim($url);
        if ($url === '') {
            return $resource === 'delivery'
                ? 'https://api.keccel.com/sms/v2/delivery.asp'
                : 'https://api.keccel.com/sms/v2/balance.asp';
        }

        $pattern = '#/sms/(?!v2/)'.$resource.'\.asp#i';
        $replaced = preg_replace($pattern, '/sms/v2/'.$resource.'.asp', $url);

        return is_string($replaced) && $replaced !== '' ? $replaced : $url;
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

<?php

namespace App\Services\FlexPay;

use App\Support\RetreatMailUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Exécute des appels FlexPay de test et retourne la requête/réponse pour diagnostic admin.
 */
class FlexPayTestService
{
    /**
     * Résumé de la configuration FlexPay (token masqué).
     *
     * @return array<string, mixed>
     */
    public function configSnapshot(): array
    {
        $token = (string) config('services.flexpay.token');
        $merchant = (string) config('services.flexpay.merchant');

        return [
            'merchant' => $merchant !== '' ? $merchant : null,
            'merchant_configured' => $merchant !== '',
            'token_configured' => $token !== '',
            'token_preview' => $this->maskSecret($token),
            'gateway_mobile' => (string) config('services.flexpay.gateway_mobile'),
            'gateway_card' => (string) config('services.flexpay.gateway_card'),
            'gateway_check' => (string) config('services.flexpay.gateway_check'),
            'callback_url' => RetreatMailUrl::flexpayInscriptionWebhook(),
            'app_url' => (string) config('app.url'),
            'mobile_providers' => config('retraite.flexpay_mobile_providers', []),
        ];
    }

    /**
     * Teste la joignabilité HTTP des passerelles configurées.
     *
     * @return array<string, mixed>
     */
    public function probeGateways(int $timeoutSeconds = 15): array
    {
        $urls = array_values(array_unique(array_filter([
            (string) config('services.flexpay.gateway_mobile'),
            (string) config('services.flexpay.gateway_card'),
            (string) config('services.flexpay.gateway_check'),
        ])));

        $results = [];

        foreach ($urls as $url) {
            $results[] = $this->executeRequest('GET', $url, [], null, $timeoutSeconds);
        }

        return [
            'success' => collect($results)->contains(fn (array $row): bool => ($row['success'] ?? false) === true),
            'summary' => 'Sondage HTTP des URLs FlexPay configurées (GET — le code HTTP importe peu, l’objectif est d’éviter un timeout).',
            'probes' => $results,
        ];
    }

    /**
     * Initie un paiement Mobile Money de test.
     *
     * @return array<string, mixed>
     */
    public function testMobilePayment(
        string $reference,
        float|string $amount,
        string $currency,
        string $phone,
        string $type,
        int $timeoutSeconds = 30,
    ): array {
        $token = (string) config('services.flexpay.token');
        $url = (string) config('services.flexpay.gateway_mobile');
        $merchant = (string) config('services.flexpay.merchant');

        if ($token === '' || $url === '' || $merchant === '') {
            return $this->configurationError('mobile');
        }

        $body = [
            'merchant' => $merchant,
            'type' => (string) config('retraite.flexpay_mobile_money_api_type', '1'),
            'phone' => $phone,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'callbackUrl' => RetreatMailUrl::flexpayInscriptionWebhook(),
        ];

        $result = $this->executeRequest(
            'POST',
            $url,
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            $body,
            $timeoutSeconds,
        );

        $json = is_array($result['response']['json'] ?? null) ? $result['response']['json'] : [];
        $flexpayOk = isset($json['code']) && (string) $json['code'] === '0';

        $result['operation'] = 'mobile';
        $result['flexpay_accepted'] = $flexpayOk;
        $result['provider_selected'] = $type;
        $result['flexpay_api_type'] = (string) config('retraite.flexpay_mobile_money_api_type', '1');
        $result['summary'] = $flexpayOk
            ? 'FlexPay a accepté la demande mobile (code 0).'
            : ($result['success']
                ? 'Réponse HTTP reçue mais FlexPay n’a pas renvoyé code 0.'
                : 'Échec réseau ou timeout vers la passerelle mobile.');

        return $result;
    }

    /**
     * Initie un paiement carte de test.
     *
     * @return array<string, mixed>
     */
    public function testCardPayment(
        string $reference,
        float|string $amount,
        string $currency,
        string $description,
        int $timeoutSeconds = 30,
    ): array {
        $token = (string) config('services.flexpay.token');
        $url = (string) config('services.flexpay.gateway_card');
        $merchant = (string) config('services.flexpay.merchant');
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($token === '' || $url === '' || $merchant === '') {
            return $this->configurationError('card');
        }

        $baseRedirectUrl = "{$appUrl}/inscription-retraite/paiement-carte/{$reference}/{$amount}/{$currency}";

        $body = [
            'authorization' => 'Bearer '.$token,
            'merchant' => $merchant,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'callback_url' => RetreatMailUrl::flexpayInscriptionWebhook(),
            'approve_url' => "{$baseRedirectUrl}/success",
            'cancel_url' => "{$baseRedirectUrl}/cancel",
            'decline_url' => "{$baseRedirectUrl}/decline",
            'home_url' => "{$appUrl}/",
        ];

        $result = $this->executeRequest(
            'POST',
            $url,
            ['Content-Type' => 'application/json'],
            $body,
            $timeoutSeconds,
        );

        $json = is_array($result['response']['json'] ?? null) ? $result['response']['json'] : [];
        $flexpayOk = isset($json['code']) && (string) $json['code'] === '0';

        $result['operation'] = 'card';
        $result['flexpay_accepted'] = $flexpayOk;
        $result['redirect_url'] = $json['url'] ?? null;
        $result['summary'] = $flexpayOk
            ? 'FlexPay a accepté la demande carte (code 0).'
            : ($result['success']
                ? 'Réponse HTTP reçue mais FlexPay n’a pas renvoyé code 0.'
                : 'Échec réseau ou timeout vers la passerelle carte.');

        return $result;
    }

    /**
     * Vérifie le statut d’une transaction FlexPay.
     *
     * @return array<string, mixed>
     */
    public function testCheckTransaction(string $reference, int $timeoutSeconds = 30): array
    {
        $token = (string) config('services.flexpay.token');
        $base = rtrim((string) config('services.flexpay.gateway_check'), '/');
        $url = $base.'/'.urlencode($reference);

        if ($token === '' || $base === '') {
            return $this->configurationError('check');
        }

        $result = $this->executeRequest(
            'GET',
            $url,
            ['Authorization' => 'Bearer '.$token],
            null,
            $timeoutSeconds,
        );

        $result['operation'] = 'check';
        $result['summary'] = ($result['success'] ?? false)
            ? 'Réponse reçue du service de vérification FlexPay.'
            : 'Impossible de joindre le service de vérification FlexPay.';

        return $result;
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    protected function executeRequest(
        string $method,
        string $url,
        array $headers,
        ?array $body,
        int $timeoutSeconds,
    ): array {
        $startedAt = microtime(true);

        try {
            $client = Http::timeout($timeoutSeconds)->withHeaders($headers);

            /** @var Response $response */
            $response = strtoupper($method) === 'GET'
                ? $client->get($url)
                : $client->post($url, $body ?? []);

            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);
            $json = $response->json();
            $rawBody = $response->body();

            return [
                'success' => true,
                'http_status' => $response->status(),
                'duration_ms' => $durationMs,
                'request' => [
                    'method' => strtoupper($method),
                    'url' => $url,
                    'headers' => $this->sanitizeHeaders($headers),
                    'body' => $this->sanitizeBody($body),
                ],
                'response' => [
                    'status' => $response->status(),
                    'json' => $json,
                    'body' => $rawBody !== '' ? $rawBody : null,
                    'message' => is_array($json) ? ($json['message'] ?? null) : null,
                    'code' => is_array($json) ? ($json['code'] ?? null) : null,
                ],
                'error' => null,
            ];
        } catch (ConnectionException $exception) {
            return $this->failedRequestPayload(
                $method,
                $url,
                $headers,
                $body,
                $exception,
                microtime(true) - $startedAt,
            );
        } catch (Throwable $exception) {
            return $this->failedRequestPayload(
                $method,
                $url,
                $headers,
                $body,
                $exception,
                microtime(true) - $startedAt,
            );
        }
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>
     */
    protected function failedRequestPayload(
        string $method,
        string $url,
        array $headers,
        ?array $body,
        Throwable $exception,
        float $elapsedSeconds,
    ): array {
        return [
            'success' => false,
            'http_status' => null,
            'duration_ms' => round($elapsedSeconds * 1000, 2),
            'request' => [
                'method' => strtoupper($method),
                'url' => $url,
                'headers' => $this->sanitizeHeaders($headers),
                'body' => $this->sanitizeBody($body),
            ],
            'response' => null,
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function configurationError(string $operation): array
    {
        return [
            'success' => false,
            'operation' => $operation,
            'summary' => 'Configuration FlexPay incomplète (FLEXPAY_API_TOKEN, FLEXPAY_MARCHAND ou URL passerelle manquante).',
            'config' => $this->configSnapshot(),
            'error' => 'Identifiants ou URL FlexPay manquants dans le .env.',
        ];
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $key => $value) {
            if (Str::lower((string) $key) === 'authorization') {
                $sanitized[$key] = $this->maskSecret((string) $value);

                continue;
            }

            $sanitized[$key] = (string) $value;
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @return array<string, mixed>|null
     */
    protected function sanitizeBody(?array $body): ?array
    {
        if ($body === null) {
            return null;
        }

        $copy = $body;

        if (isset($copy['authorization'])) {
            $copy['authorization'] = $this->maskSecret((string) $copy['authorization']);
        }

        return $copy;
    }

    /**
     * Masque un secret pour affichage admin.
     */
    protected function maskSecret(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '(vide)';
        }

        if (Str::startsWith($value, 'Bearer ')) {
            return 'Bearer '.$this->maskSecret(Str::after($value, 'Bearer '));
        }

        if (strlen($value) <= 12) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 6).'…'.substr($value, -4);
    }
}

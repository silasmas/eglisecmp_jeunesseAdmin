<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ref = 'DEBUG-'.time();
$amount = 100;
$currency = 'USD';
$merchant = config('services.flexpay.merchant');
$token = config('services.flexpay.token');
$publicBase = App\Support\RetreatMailUrl::base();
$baseRedirect = "{$publicBase}/inscription-retraite/paiement-carte/{$ref}/{$amount}/{$currency}";

$scenarios = [
    'v1.1 body auth' => [
        'url' => 'https://cardpayment.flexpay.cd/v1.1/pay',
        'headers' => ['Content-Type' => 'application/json'],
        'body' => [
            'authorization' => 'Bearer '.$token,
            'merchant' => $merchant,
            'reference' => $ref.'-a',
            'amount' => $amount,
            'currency' => $currency,
            'description' => 'Debug',
            'callback_url' => App\Support\RetreatMailUrl::flexpayInscriptionWebhook(),
            'approve_url' => "{$baseRedirect}/success",
            'cancel_url' => "{$baseRedirect}/cancel",
            'decline_url' => "{$baseRedirect}/decline",
            'home_url' => App\Support\RetreatMailUrl::portal(),
        ],
    ],
    'v1.1 header auth' => [
        'url' => 'https://cardpayment.flexpay.cd/v1.1/pay',
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ],
        'body' => [
            'merchant' => $merchant,
            'reference' => $ref.'-b',
            'amount' => (string) $amount,
            'currency' => $currency,
            'description' => 'Debug',
            'callback_url' => App\Support\RetreatMailUrl::flexpayInscriptionWebhook(),
            'approve_url' => "{$baseRedirect}/success",
            'cancel_url' => "{$baseRedirect}/cancel",
            'decline_url' => "{$baseRedirect}/decline",
            'home_url' => App\Support\RetreatMailUrl::portal(),
        ],
    ],
    'v2 pay' => [
        'url' => 'https://cardpayment.flexpay.cd/v2/pay',
        'headers' => ['Content-Type' => 'application/json'],
        'body' => [
            'authorization' => 'Bearer '.$token,
            'merchant' => $merchant,
            'reference' => $ref.'-c',
            'amount' => $amount,
            'currency' => $currency,
            'description' => 'Debug',
            'callback_url' => App\Support\RetreatMailUrl::flexpayInscriptionWebhook(),
            'approve_url' => "{$baseRedirect}/success",
            'cancel_url' => "{$baseRedirect}/cancel",
            'decline_url' => "{$baseRedirect}/decline",
            'home_url' => App\Support\RetreatMailUrl::portal(),
        ],
    ],
    'backend card endpoint' => [
        'url' => 'https://backend.flexpay.cd/api/rest/v1/card',
        'headers' => ['Content-Type' => 'application/json'],
        'body' => [
            'authorization' => 'Bearer '.$token,
            'merchant' => $merchant,
            'reference' => $ref.'-d',
            'amount' => $amount,
            'currency' => $currency,
            'description' => 'Debug',
            'callback_url' => App\Support\RetreatMailUrl::flexpayInscriptionWebhook(),
            'approve_url' => "{$baseRedirect}/success",
            'cancel_url' => "{$baseRedirect}/cancel",
            'decline_url' => "{$baseRedirect}/decline",
            'home_url' => App\Support\RetreatMailUrl::portal(),
        ],
    ],
];

foreach ($scenarios as $label => $scenario) {
    echo "\n=== {$label} ===\n";
    try {
        $response = Illuminate\Support\Facades\Http::timeout(45)
            ->withHeaders($scenario['headers'])
            ->post($scenario['url'], $scenario['body']);
        echo 'HTTP: '.$response->status()."\n";
        echo $response->body()."\n";
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n";
    }
}

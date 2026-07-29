<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.flexpay.token');
$merchant = config('services.flexpay.merchant');
$publicBase = 'https://jeunesse.eglisecmp.com/public';
$ref = 'RET-'.(time() % 100000000);
$amount = 100;
$currency = 'USD';
$baseRedirect = "{$publicBase}/inscription-retraite/paiement-carte/{$ref}/{$amount}/{$currency}";

$body = [
    'authorization' => 'Bearer '.$token,
    'merchant' => $merchant,
    'reference' => $ref,
    'amount' => $amount,
    'currency' => $currency,
    'description' => 'Test carte retraite',
    'callback_url' => $publicBase.'/api/v1/retreat/inscription/webhooks/flexpay-callback',
    'approve_url' => "{$baseRedirect}/success",
    'cancel_url' => "{$baseRedirect}/cancel",
    'decline_url' => "{$baseRedirect}/decline",
    'home_url' => $publicBase.'/',
];

$endpoints = [
    'v1.1' => 'https://cardpayment.flexpay.cd/v1.1/pay',
    'v1' => 'https://cardpayment.flexpay.cd/v1/pay',
    'v2' => 'https://cardpayment.flexpay.cd/v2/pay',
];

foreach ($endpoints as $label => $url) {
    $response = Illuminate\Support\Facades\Http::timeout(30)->post($url, $body);
    echo "{$label} HTTP {$response->status()}\n";
    echo substr($response->body(), 0, 300)."\n\n";
}

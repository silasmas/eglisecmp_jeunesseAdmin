<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.flexpay.token');
$merchant = config('services.flexpay.merchant');
$publicBase = rtrim((string) config('retraite.public_base_url', config('app.url')), '/');
$ref = 'TEST'.substr((string) time(), -6);
$amount = 100;
$currency = 'USD';
$baseRedirect = "{$publicBase}/inscription-retraite/paiement-carte/{$ref}/{$amount}/{$currency}";

$body = [
    'authorization' => 'Bearer '.$token,
    'merchant' => $merchant,
    'reference' => $ref,
    'amount' => $amount,
    'currency' => $currency,
    'description' => 'Diagnostic CMP Jeunesse',
    'callback_url' => $publicBase.'/api/v1/retreat/inscription/webhooks/flexpay-callback',
    'approve_url' => "{$baseRedirect}/success",
    'cancel_url' => "{$baseRedirect}/cancel",
    'decline_url' => "{$baseRedirect}/decline",
    'home_url' => $publicBase.'/',
];

echo "Merchant: {$merchant}\n";
echo "Public base: {$publicBase}\n";
echo "Reference: {$ref} (len=".strlen($ref).")\n";
echo "Callback: {$body['callback_url']}\n\n";

$response = Illuminate\Support\Facades\Http::timeout(45)
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post(config('services.flexpay.gateway_card'), $body);

echo "HTTP {$response->status()}\n";
echo $response->body()."\n";

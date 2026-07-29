<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ref = 'DEBUG-'.time();
$amount = 100;
$currency = 'USD';
$merchant = config('services.flexpay.merchant');
$token = config('services.flexpay.token');
$gateway = config('services.flexpay.gateway_card');
$publicBase = App\Support\RetreatMailUrl::base();
$baseRedirect = "{$publicBase}/inscription-retraite/paiement-carte/{$ref}/{$amount}/{$currency}";

$body = [
    'authorization' => 'Bearer '.$token,
    'merchant' => $merchant,
    'reference' => $ref,
    'amount' => $amount,
    'currency' => $currency,
    'description' => 'Debug retraite carte',
    'callback_url' => App\Support\RetreatMailUrl::flexpayInscriptionWebhook(),
    'approve_url' => "{$baseRedirect}/success",
    'cancel_url' => "{$baseRedirect}/cancel",
    'decline_url' => "{$baseRedirect}/decline",
    'home_url' => App\Support\RetreatMailUrl::portal(),
];

echo "Gateway: {$gateway}\n";
echo "Merchant: {$merchant}\n";
echo "Callback: ".App\Support\RetreatMailUrl::flexpayInscriptionWebhook()."\n";
echo "Approve: {$baseRedirect}/success\n\n";

$response = Illuminate\Support\Facades\Http::timeout(30)
    ->withHeaders(['Content-Type' => 'application/json'])
    ->post($gateway, $body);

echo 'HTTP status: '.$response->status()."\n";
echo "Body:\n".$response->body()."\n";

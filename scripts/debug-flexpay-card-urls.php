<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ref = 'DEBUG-'.time();
$amount = 100;
$currency = 'USD';
$merchant = config('services.flexpay.merchant');
$token = config('services.flexpay.token');

$bases = [
    'with_public' => 'https://jeunesse.eglisecmp.com/public',
    'without_public' => 'https://jeunesse.eglisecmp.com',
];

foreach ($bases as $label => $publicBase) {
    $baseRedirect = "{$publicBase}/inscription-retraite/paiement-carte/{$ref}/{$amount}/{$currency}";
    $callback = rtrim($publicBase, '/').'/api/v1/retreat/inscription/webhooks/flexpay-callback';

    $body = [
        'authorization' => 'Bearer '.$token,
        'merchant' => $merchant,
        'reference' => $ref.'-'.$label,
        'amount' => $amount,
        'currency' => $currency,
        'description' => 'Debug URLs',
        'callback_url' => $callback,
        'approve_url' => "{$baseRedirect}/success",
        'cancel_url' => "{$baseRedirect}/cancel",
        'decline_url' => "{$baseRedirect}/decline",
        'home_url' => rtrim($publicBase, '/').'/',
    ];

    echo "\n=== {$label} ===\n";
    echo "callback: {$callback}\n";
    $response = Illuminate\Support\Facades\Http::timeout(45)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post('https://cardpayment.flexpay.cd/v1.1/pay', $body);
    echo $response->body()."\n";
}

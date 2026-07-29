<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = config('services.flexpay.token');
$merchant = config('services.flexpay.merchant');
$publicBase = 'https://jeunesse.eglisecmp.com/public';

foreach (['USD', 'CDF'] as $currency) {
    $ref = 'T'.substr((string) time(), -8);
    $amount = $currency === 'USD' ? 30 : 75000;
    $baseRedirect = "{$publicBase}/inscription-retraite/paiement-carte/{$ref}/{$amount}/{$currency}";

    $body = [
        'authorization' => 'Bearer '.$token,
        'merchant' => $merchant,
        'reference' => $ref,
        'amount' => $amount,
        'currency' => $currency,
        'description' => 'Retraite CMP Jeunesse',
        'callback_url' => $publicBase.'/api/v1/retreat/inscription/webhooks/flexpay-callback',
        'approve_url' => "{$baseRedirect}/success",
        'cancel_url' => "{$baseRedirect}/cancel",
        'decline_url' => "{$baseRedirect}/decline",
        'home_url' => $publicBase.'/',
    ];

    $response = Illuminate\Support\Facades\Http::timeout(30)->post('https://cardpayment.flexpay.cd/v1.1/pay', $body);
    echo "{$currency} HTTP {$response->status()}: {$response->body()}\n";
}

$mobile = app(App\Services\FlexPay\FlexPayMobileService::class);
$mobileRef = 'M'.substr((string) time(), -8);
$mobileResult = $mobile->initiateMobilePayment($mobileRef, 30, 'USD', '243993107499', '1');
echo 'MOBILE: '.json_encode($mobileResult, JSON_UNESCAPED_UNICODE)."\n";

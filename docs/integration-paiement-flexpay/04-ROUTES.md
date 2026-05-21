# Routes à ajouter

Dans `routes/web.php` :

```php
use App\Http\Controllers\DonationPaymentController;

// Routes publiques (ou avec middleware auth si besoin)
Route::post('/init-don', [DonationPaymentController::class, 'initDon'])->name('init.don');
Route::post('/process-payment', [DonationPaymentController::class, 'processPayment'])->name('process.payment');
Route::get('/checkTransactionStatus', [DonationPaymentController::class, 'checkTransactionStatus'])->name('checkTransactionStatus');

// Retour FlexPay après paiement carte
Route::get('/paid/{reference}/{amount}/{currency}/{status}', [DonationPaymentController::class, 'paid'])
    ->whereNumber(['amount'])
    ->name('paid');

// Page de remerciement après don
Route::get('/don/merci', function () {
    return view('don.merci');
})->name('don.merci');
```

## Exclusions CSRF (si nécessaire)

Dans `app/Http/Middleware/VerifyCsrfToken.php` :

```php
protected $except = [
    'payment/callback',  // Si FlexPay envoie un webhook
];
```

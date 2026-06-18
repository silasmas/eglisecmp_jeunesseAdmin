<?php

use App\Http\Controllers\Api\RetreatIntegrationController;
use App\Http\Controllers\Api\RetreatPublicRegistrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/retreat/inscription')
    ->name('api.v1.retreat.inscription.')
    ->middleware(['throttle:120,1'])
    ->group(function (): void {
        Route::get('event', [RetreatPublicRegistrationController::class, 'activeEvent'])->name('event');
        Route::get('hints/tutor-family', [RetreatPublicRegistrationController::class, 'tutorFamilyHint'])
            ->middleware('throttle:60,1')
            ->name('hints.tutor_family');
        Route::get('hints/main-phone', [RetreatPublicRegistrationController::class, 'mainPhoneDuplicateHint'])
            ->middleware('throttle:60,1')
            ->name('hints.main_phone');
        Route::get('hints/email', [RetreatPublicRegistrationController::class, 'emailDuplicateHint'])
            ->middleware('throttle:60,1')
            ->name('hints.email');
        Route::get('hints/participant-identity', [RetreatPublicRegistrationController::class, 'participantIdentityDuplicateHint'])
            ->middleware('throttle:60,1')
            ->name('hints.participant_identity');
        Route::get('hints/sponsorship-voucher', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'sponsorshipVoucherHint'])
            ->middleware('throttle:60,1')
            ->name('hints.sponsorship_voucher');
        Route::get('policies', [RetreatPublicRegistrationController::class, 'inscriptionPolicies'])->name('policies');
        Route::post('worker-prefill', [RetreatPublicRegistrationController::class, 'workerPrefill'])
            ->middleware('throttle:20,1')
            ->name('worker.prefill');
        Route::post('contact-verification/request-otp', [RetreatPublicRegistrationController::class, 'requestParentContactOtp'])
            ->middleware('throttle:20,1')
            ->name('contact_verification.request_otp');
        Route::post('contact-verification/verify-otp', [RetreatPublicRegistrationController::class, 'verifyParentContactOtp'])
            ->middleware('throttle:30,1')
            ->name('contact_verification.verify_otp');
        Route::get('contact-verification/sms-delivery', [RetreatPublicRegistrationController::class, 'checkParentContactSmsDelivery'])
            ->middleware('throttle:30,1')
            ->name('contact_verification.sms_delivery');
        Route::get('payments/receipt', [RetreatPublicRegistrationController::class, 'paymentReceipt'])->name('payments.receipt');
        Route::post('register', [RetreatPublicRegistrationController::class, 'register'])
            ->middleware('throttle:12,1')
            ->name('register');
        Route::post('funnel', [RetreatPublicRegistrationController::class, 'recordFunnel'])
            ->middleware('throttle:60,1')
            ->name('funnel');
        Route::get('participants/{participant}/status', [RetreatPublicRegistrationController::class, 'participantStatus'])->name('participants.status');
        Route::post('participants/{participant}/payments/mobile', [RetreatPublicRegistrationController::class, 'initMobilePayment'])->name('participants.payments.mobile');
        Route::post('participants/{participant}/payments/card', [RetreatPublicRegistrationController::class, 'initCardPayment'])->name('participants.payments.card');
        Route::post('participants/{participant}/payments/cash', [RetreatPublicRegistrationController::class, 'submitCashPayment'])->name('participants.payments.cash');
        Route::post('participants/{participant}/sponsorship-voucher', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'applySponsorshipVoucher'])
            ->middleware('throttle:30,1')
            ->name('participants.sponsorship_voucher');
        Route::get('payments/check', [RetreatPublicRegistrationController::class, 'checkPayment'])->name('payments.check');
        Route::post('webhooks/flexpay-callback', [RetreatPublicRegistrationController::class, 'flexpayWebhook'])->name('webhooks.flexpay');
    });

Route::prefix('v1/retreat/donations')
    ->name('api.v1.retreat.donations.')
    ->middleware(['throttle:60,1'])
    ->group(function (): void {
        Route::get('context', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'context'])->name('context');
        Route::post('in-kind', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'storeInKind'])
            ->middleware('throttle:20,1')
            ->name('in_kind');
        Route::post('cash/init', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'initCash'])
            ->middleware('throttle:20,1')
            ->name('cash.init');
        Route::post('{donation}/payments/mobile', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'initMobilePayment'])
            ->middleware('throttle:20,1')
            ->name('payments.mobile');
        Route::post('{donation}/payments/card', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'initCardPayment'])
            ->middleware('throttle:20,1')
            ->name('payments.card');
        Route::post('{donation}/payments/cash', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'submitCashPayment'])
            ->middleware('throttle:20,1')
            ->name('payments.cash');
        Route::get('payments/check', [\App\Http\Controllers\Api\RetreatVoluntaryDonationController::class, 'checkPayment'])->name('payments.check');
    });

Route::prefix('v1/retreat')->name('api.v1.retreat.')->group(function (): void {
    Route::get('summary', [RetreatIntegrationController::class, 'summary'])->name('summary');

    Route::get('participants', [RetreatIntegrationController::class, 'participants'])->name('participants.index');
    Route::post('participants', [RetreatIntegrationController::class, 'storeParticipant'])->name('participants.store');
    Route::post('participants/register', [RetreatIntegrationController::class, 'storeParticipantRegistration'])->name('participants.register');
    Route::post('participants/{participant}/payments', [RetreatIntegrationController::class, 'storeParticipantPayment'])->name('participants.payments.store');
    Route::get('participants/{participant}/registration', [RetreatIntegrationController::class, 'participantRegistration'])->name('participants.registration.show');
    Route::get('participants/{participant}', [RetreatIntegrationController::class, 'participant'])->name('participants.show');

    Route::get('chambres', [RetreatIntegrationController::class, 'chambres'])->name('chambres.index');
    Route::get('ateliers', [RetreatIntegrationController::class, 'ateliers'])->name('ateliers.index');
    Route::get('sessions', [RetreatIntegrationController::class, 'sessions'])->name('sessions.index');
    Route::get('activity-plans', [RetreatIntegrationController::class, 'activityPlans'])->name('activity-plans.index');
    Route::get('policies', [RetreatIntegrationController::class, 'policies'])->name('policies.index');

    Route::get('attendances', [RetreatIntegrationController::class, 'attendances'])->name('attendances.index');
    Route::post('attendances', [RetreatIntegrationController::class, 'storeAttendance'])->name('attendances.store');
});

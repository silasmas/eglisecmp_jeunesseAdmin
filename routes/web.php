<?php

use App\Http\Controllers\RetreatAdminParticipantBilletController;
use App\Http\Controllers\RetreatAdminPaymentProofController;
use App\Http\Controllers\ProductionBaseSyncController;
use App\Http\Controllers\RetreatBadgeStudioApiController;
use App\Http\Controllers\RetreatBadgeStudioController;
use App\Http\Controllers\RetreatInscriptionAccessController;
use App\Http\Controllers\RetreatInscriptionBilletController;
use App\Http\Controllers\RetreatInscriptionCardReturnController;
use App\Http\Controllers\RetreatInscriptionJustificatifController;
use App\Http\Controllers\RetreatVerificationPortalController;
use App\Models\ChurchEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/** Synchronisation données de base + Shield (token PRODUCTION_BASE_SYNC_TOKEN) */
Route::get('/system/sync-production-base/{token}', ProductionBaseSyncController::class)
    ->middleware('throttle:5,1')
    ->name('system.sync-production-base');

Route::get('/', function () {
    $portalRetreatEvent = ChurchEvent::query()
        ->where('type', 'retraite')
        ->where('is_active', true)
        ->where('is_publicly_closed', false)
        ->whereNotNull('start_at')
        ->orderBy('start_at')
        ->first();

    $portalProgrammeLocked = ! $portalRetreatEvent
        || now()->lt($portalRetreatEvent->start_at);

    $portalPublicClosed = ChurchEvent::query()
        ->where('type', 'retraite')
        ->where('is_active', true)
        ->where('is_publicly_closed', true)
        ->orderByDesc('end_at')
        ->first();

    $portalDonEvent = ChurchEvent::query()
        ->where('type', 'retraite')
        ->orderByDesc('is_active')
        ->orderByDesc('start_at')
        ->orderByDesc('id')
        ->first();

    $portalInactiveEvent = ChurchEvent::query()
        ->where('type', 'retraite')
        ->where('is_active', false)
        ->orderByDesc('start_at')
        ->orderByDesc('id')
        ->first();

    return view('welcome', [
        'portalRetreatEvent' => $portalRetreatEvent,
        'portalProgrammeLocked' => $portalProgrammeLocked,
        'portalPublicClosed' => $portalPublicClosed,
        'portalDonEvent' => $portalDonEvent,
        'portalInactiveEvent' => $portalInactiveEvent,
    ]);
});

/** Preuve de paiement cash (admin authentifié — évite les 403 stockage direct) */
Route::get('/admin/retreat-participants/{participant}/payment-proof', [RetreatAdminPaymentProofController::class, 'show'])
    ->middleware(['auth'])
    ->name('retreat.admin.payment-proof');

/** Aperçu billet participant (admin — même page que le lien public) */
Route::get('/admin/retreat-participants/{participant}/billet-preview', [RetreatAdminParticipantBilletController::class, 'show'])
    ->middleware(['auth'])
    ->name('retreat.admin.billet-preview');

/** Formulaire public Grande Retraite des Jeunes (UI Blade + assets dans public/retraite-inscription) */
Route::view('/inscription-retraite', 'retraite-inscription.index')->name('retraite.inscription');

/** Don volontaire pour la retraite (nature ou espèces) */
Route::view('/don-retraite', 'retraite-don.index')->name('retraite.don');

/** Page publique liée au QR code imprimé après inscription (token unique) */
Route::get('/inscription-retraite/justificatif/{token}', RetreatInscriptionJustificatifController::class)
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('retraite.inscription.justificatif');

/** Billet participant (paiement validé) */
Route::get('/inscription-retraite/billet/{token}', RetreatInscriptionBilletController::class)
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('retraite.inscription.billet');

/** Contrôle d'accès au scan du QR (état d'inscription) */
Route::get('/inscription-retraite/acces/{token}', RetreatInscriptionAccessController::class)
    ->where('token', '[A-Za-z0-9]{32}')
    ->name('retraite.inscription.acces');

/** Retour FlexPay après paiement carte (parcours inscription retraite) — montant peut être 100 ou 100.00 */
Route::get('/inscription-retraite/paiement-carte/{reference}/{amount}/{currency}/{status}', RetreatInscriptionCardReturnController::class)
    ->where('amount', '[0-9]+(?:\.[0-9]{1,2})?')
    ->whereIn('status', ['success', 'cancel', 'decline', 'failure', 'failed', 'error'])
    ->name('retraite.inscription.card-return');

/** Studio badges participants (React + Vite, permission View:BadgeStudio) */
Route::prefix('studio-badge')
    ->name('studio-badge.')
    ->middleware(['auth', 'badge_studio'])
    ->group(function (): void {
        Route::get('/', RetreatBadgeStudioController::class)->name('index');
        Route::get('api/participants', [RetreatBadgeStudioApiController::class, 'participants'])
            ->name('api.participants');
        Route::post('logout', function () {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect('/');
        })->name('logout');
    });

Route::prefix('verification-retraite')
    ->name('retraite.verification.')
    ->controller(RetreatVerificationPortalController::class)
    ->group(function (): void {
        Route::get('status', 'status')->name('status');
        Route::post('otp/request', 'requestOtp')->middleware('throttle:5,1')->name('otp.request');
        Route::post('otp/verify', 'verifyOtp')->middleware('throttle:10,1')->name('otp.verify');
        Route::post('logout', 'logout')->name('logout');
        Route::post('search', 'search')->middleware('throttle:60,1')->name('search');
        Route::post('participants/{participant}/action', 'workerAction')
            ->middleware('throttle:60,1')
            ->name('participants.action');
        Route::get('attendance/context', 'attendanceContext')->name('attendance.context');
        Route::get('attendance/blocks', 'attendanceBlocks')->name('attendance.blocks');
        Route::post('attendance/set', 'attendanceSet')->middleware('throttle:120,1')->name('attendance.set');
        Route::post('attendance/excuse', 'attendanceExcuse')->middleware('throttle:120,1')->name('attendance.excuse');
        Route::post('attendance/report/submit', 'attendanceReportSubmit')->middleware('throttle:30,1')->name('attendance.report.submit');
        Route::post('public-lookup', 'publicLookup')->middleware('throttle:30,1')->name('public.lookup');
        Route::get('chatbot/context', 'chatbotContext')->name('chatbot.context');
    });

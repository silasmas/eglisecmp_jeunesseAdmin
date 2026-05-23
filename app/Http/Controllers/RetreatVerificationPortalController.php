<?php

namespace App\Http\Controllers;

use App\Mail\RetreatWorkerOtpMail;
use App\Models\ChurchEvent;
use App\Models\RetreatActivityAttendance;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatPolicy;
use App\Models\User;
use App\Services\RetreatAtelierAttendancePanelService;
use App\Services\RetreatAtelierAuthorizationService;
use App\Services\RetreatParticipantRegistrationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RetreatVerificationPortalController extends Controller
{
    private const SESSION_USER_ID = 'retreat_verifier_user_id';
    private const SESSION_EMAIL = 'retreat_verifier_email';
    private const OTP_TTL_MINUTES = 5;

    public function status(Request $request): JsonResponse
    {
        $user = $this->currentVerifier($request);

        return response()->json([
            'authenticated' => $user !== null,
            'user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'can_mark_atelier_attendance' => $user
                ? app(RetreatAtelierAuthorizationService::class)->managesAnyAtelier($user)
                : false,
            'can_manage_registrations' => $user
                ? $user->hasAnyRole(['super_admin', 'panel_user'])
                : false,
        ]);
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:254'],
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (! $user || ! $this->canVerifyRetreatRegistrations($user)) {
            return response()->json([
                'message' => 'Aucun ouvrier autorise ne correspond a cette adresse e-mail.',
            ], 403);
        }

        $throttleKey = $this->otpThrottleKey($email);
        if (Cache::has($throttleKey)) {
            return response()->json([
                'message' => 'Un code vient deja d\'etre envoye. Patientez une minute avant une nouvelle demande.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put($this->otpCacheKey($email), Hash::make($otp), now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::put($throttleKey, true, now()->addMinute());

        try {
            Mail::to($user->email)->send(new RetreatWorkerOtpMail($otp, self::OTP_TTL_MINUTES));
        } catch (\Throwable $e) {
            Cache::forget($this->otpCacheKey($email));
            Cache::forget($throttleKey);

            Log::error('Echec envoi OTP ouvrier retraite', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Le code n\'a pas pu etre envoye par e-mail. Verifiez la configuration SMTP puis reessayez.',
                'debug' => app()->isLocal() ? $e->getMessage() : null,
            ], 502);
        }

        return response()->json([
            'message' => 'Code envoye par e-mail.',
            'email' => $user->email,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = Str::lower(trim((string) $validated['email']));
        $hash = Cache::get($this->otpCacheKey($email));

        if (! is_string($hash) || ! Hash::check((string) $validated['otp'], $hash)) {
            return response()->json([
                'message' => 'Code invalide ou expire.',
            ], 422);
        }

        $user = User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
        if (! $user || ! $this->canVerifyRetreatRegistrations($user)) {
            return response()->json([
                'message' => 'Ce compte n\'est pas autorise.',
            ], 403);
        }

        Cache::forget($this->otpCacheKey($email));
        $request->session()->put(self::SESSION_USER_ID, $user->id);
        $request->session()->put(self::SESSION_EMAIL, $user->email);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Connexion verification active.',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'can_mark_atelier_attendance' => app(RetreatAtelierAuthorizationService::class)->managesAnyAtelier($user),
            'can_manage_registrations' => $user->hasAnyRole(['super_admin', 'panel_user']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget([self::SESSION_USER_ID, self::SESSION_EMAIL]);

        return response()->json(['message' => 'Session de verification fermee.']);
    }

    public function search(Request $request): JsonResponse
    {
        if (! $this->currentVerifier($request)) {
            return response()->json(['message' => 'Connexion ouvrier requise.'], 401);
        }

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:150'],
            'mode' => ['nullable', 'string', 'in:auto,reference,phone,email,name,qr'],
        ]);

        $query = trim((string) $validated['query']);
        $mode = $validated['mode'] ?? 'auto';
        $token = $mode === 'qr' ? $this->extractQrToken($query) : null;

        $participants = RetreatParticipant::query()
            ->with(['event', 'chambre', 'atelier', 'payments.event'])
            ->when($token, fn (Builder $builder): Builder => $builder->where('download_token', $token))
            ->when(! $token, function (Builder $builder) use ($query, $mode): void {
                $builder->where(function (Builder $builder) use ($query, $mode): void {
                    if (in_array($mode, ['auto', 'reference'], true)) {
                        $builder->orWhereHas('payments', function (Builder $paymentQuery) use ($query): void {
                            $paymentQuery
                                ->where('reference', $query)
                                ->orWhere('provider_reference', $query);
                        });
                    }

                    if (in_array($mode, ['auto', 'phone'], true)) {
                        $digits = preg_replace('/\D+/', '', $query) ?: '';
                        if (strlen($digits) >= 6) {
                            $builder
                                ->orWhere('telephone', 'like', "%{$digits}%")
                                ->orWhere('telephone_urgence', 'like', "%{$digits}%")
                                ->orWhere('guardian_phone', 'like', "%{$digits}%");
                        }
                    }

                    if (in_array($mode, ['auto', 'email'], true) && filter_var($query, FILTER_VALIDATE_EMAIL)) {
                        $email = Str::lower($query);
                        $builder->orWhereRaw('LOWER(TRIM(email)) = ?', [$email]);
                    }

                    if (in_array($mode, ['auto', 'name'], true)) {
                        $name = preg_replace('/\s+/u', ' ', Str::lower($query));
                        if (is_string($name) && mb_strlen($name, 'UTF-8') >= 3) {
                            $builder->orWhereRaw("LOWER(TRIM(CONCAT_WS(' ', prenom, nom, postnom))) LIKE ?", ["%{$name}%"])
                                ->orWhereRaw("LOWER(TRIM(CONCAT_WS(' ', nom, postnom, prenom))) LIKE ?", ["%{$name}%"])
                                ->orWhere('nom', 'like', "%{$query}%")
                                ->orWhere('prenom', 'like', "%{$query}%")
                                ->orWhere('postnom', 'like', "%{$query}%");
                        }
                    }
                });
            })
            ->latest('id')
            ->limit(25)
            ->get();

        return response()->json([
            'data' => $participants->map(fn (RetreatParticipant $participant): array => $this->participantPayload(
                $participant,
                $this->currentVerifier($request),
            ))->values(),
        ]);
    }

    public function publicLookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:150'],
            'mode' => ['nullable', 'string', 'in:auto,reference,phone,email,name'],
        ]);

        $query = trim((string) $validated['query']);
        $mode = $validated['mode'] ?? 'auto';

        $participants = RetreatParticipant::query()
            ->with(['event', 'payments.event'])
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($query, $mode): void {
                if (in_array($mode, ['auto', 'reference'], true)) {
                    $builder->orWhereHas('payments', function (Builder $paymentQuery) use ($query): void {
                        $paymentQuery
                            ->where('reference', $query)
                            ->orWhere('provider_reference', $query);
                    });
                }

                if (in_array($mode, ['auto', 'phone'], true)) {
                    $digits = preg_replace('/\D+/', '', $query) ?: '';
                    if (strlen($digits) >= 6) {
                        $builder->orWhere('telephone', 'like', "%{$digits}%");
                    }
                }

                if (in_array($mode, ['auto', 'email'], true) && filter_var($query, FILTER_VALIDATE_EMAIL)) {
                    $builder->orWhereRaw('LOWER(TRIM(email)) = ?', [Str::lower($query)]);
                }

                if (in_array($mode, ['auto', 'name'], true)) {
                    $name = preg_replace('/\s+/u', ' ', Str::lower($query));
                    if (is_string($name) && mb_strlen($name, 'UTF-8') >= 3) {
                        $builder
                            ->orWhereRaw("LOWER(TRIM(CONCAT_WS(' ', prenom, nom, postnom))) LIKE ?", ["%{$name}%"])
                            ->orWhereRaw("LOWER(TRIM(CONCAT_WS(' ', nom, postnom, prenom))) LIKE ?", ["%{$name}%"]);
                    }
                }
            })
            ->latest('id')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $participants->map(fn (RetreatParticipant $participant): array => $this->publicParticipantPayload($participant))->values(),
        ]);
    }

    public function workerAction(Request $request, RetreatParticipant $participant): JsonResponse
    {
        $user = $this->currentVerifier($request);
        if (! $user) {
            return response()->json(['message' => 'Connexion ouvrier requise.'], 401);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:retreat_access,activity_access,exit_permission,exclude_retreat,mark_badge_received,validate_registration,send_billet'],
        ]);

        $participant->loadMissing('event');
        $isRegistrationAdmin = $user->hasAnyRole(['super_admin', 'panel_user']);
        $registrationActions = ['validate_registration', 'send_billet'];
        $eventStarted = $participant->event?->start_at?->isPast() ?? false;

        if (in_array($validated['action'], $registrationActions, true) && ! $isRegistrationAdmin) {
            return response()->json(['message' => 'Action réservée aux administrateurs.'], 403);
        }

        $preEventActions = ['validate_registration', 'send_billet'];

        if (! in_array($validated['action'], $preEventActions, true) && ! $eventStarted) {
            return response()->json([
                'message' => 'Ces actions seront disponibles lorsque la retraite aura commencé.',
            ], 422);
        }

        if ($validated['action'] === 'retreat_access') {
            if (! $participant->paiement_valide) {
                return response()->json([
                    'message' => 'Le paiement doit être validé avant d\'accorder l\'accès.',
                ], 422);
            }

            if ($participant->present) {
                return response()->json([
                    'message' => 'L\'accès à la retraite a déjà été accordé.',
                ], 422);
            }
        }

        if ($validated['action'] === 'mark_badge_received' && ! $participant->paiement_valide) {
            return response()->json([
                'message' => 'Le badge ne peut être remis qu\'après validation du paiement.',
            ], 422);
        }

        if ($validated['action'] === 'mark_badge_received' && $participant->badge_received) {
            return response()->json([
                'message' => 'Le badge a déjà été remis à ce participant.',
            ], 422);
        }

        if ($validated['action'] === 'mark_badge_received' && ! $participant->present) {
            return response()->json([
                'message' => 'Accordez d\'abord l\'accès à la retraite avant de remettre le badge.',
            ], 422);
        }

        $registrationService = app(RetreatParticipantRegistrationService::class);
        $sendResult = null;

        match ($validated['action']) {
            'retreat_access' => $participant->update([
                'present' => true,
                'date_presence' => $participant->date_presence ?? now(),
            ]),
            'activity_access' => $this->markCurrentActivityAccess($participant),
            'exit_permission' => $participant->update(['exit_allowed' => true]),
            'exclude_retreat' => $participant->update([
                'is_active' => false,
                'observation' => trim(((string) $participant->observation)."\nExclu de la retraite le ".now()->format('d/m/Y H:i')),
            ]),
            'mark_badge_received' => $registrationService->markBadgeReceived($participant),
            'validate_registration' => $registrationService->validateRegistration($participant, $user),
            'send_billet' => $sendResult = $registrationService->sendBilletNotification($participant, true),
        };

        $message = match ($validated['action']) {
            'retreat_access' => 'Accès à la retraite validé.',
            'activity_access' => 'Accès à l\'activité validé.',
            'exit_permission' => 'Permission de sortie accordée.',
            'exclude_retreat' => 'Participant exclu de la retraite.',
            'mark_badge_received' => 'Badge physique marqué comme remis.',
            'validate_registration' => 'Inscription validée.',
            'send_billet' => is_array($sendResult) ? ($sendResult['message'] ?? 'Notification billet traitée.') : 'Notification billet traitée.',
        };

        return response()->json([
            'message' => $message,
            'data' => $this->participantPayload(
                $participant->fresh(['event', 'chambre', 'atelier', 'payments.event']),
                $user,
            ),
        ]);
    }

    /**
     * Contexte du pointage atelier (activités disponibles) pour le portail ouvrier.
     */
    public function attendanceContext(Request $request): JsonResponse
    {
        $user = $this->currentVerifier($request);
        if (! $user) {
            return response()->json(['message' => 'Connexion ouvrier requise.'], 401);
        }

        $auth = app(RetreatAtelierAuthorizationService::class);
        if (! $auth->managesAnyAtelier($user)) {
            return response()->json(['message' => 'Réservé au responsable ou à l\'adjoint d\'atelier.'], 403);
        }

        $service = app(RetreatAtelierAttendancePanelService::class);

        return response()->json([
            'activities' => collect($service->activityOptionsForUser($user))
                ->map(fn (string $label, int $id): array => ['id' => $id, 'label' => $label])
                ->values(),
        ]);
    }

    /**
     * Données de pointage par atelier pour une activité (portail ouvrier).
     */
    public function attendanceBlocks(Request $request): JsonResponse
    {
        $user = $this->currentVerifier($request);
        if (! $user) {
            return response()->json(['message' => 'Connexion ouvrier requise.'], 401);
        }

        $auth = app(RetreatAtelierAuthorizationService::class);
        if (! $auth->managesAnyAtelier($user)) {
            return response()->json(['message' => 'Réservé au responsable ou à l\'adjoint d\'atelier.'], 403);
        }

        $validated = $request->validate([
            'activity_plan_id' => ['required', 'integer', 'exists:retreat_activity_plans,id'],
        ]);

        $service = app(RetreatAtelierAttendancePanelService::class);
        $blocks = $service->buildBlocksForUser($user, (int) $validated['activity_plan_id']);

        return response()->json([
            'data' => $service->serializeBlocksForPortal($blocks),
        ]);
    }

    /**
     * Enregistre la présence d'un participant (portail ouvrier).
     */
    public function attendanceSet(Request $request): JsonResponse
    {
        $user = $this->currentVerifier($request);
        if (! $user) {
            return response()->json(['message' => 'Connexion ouvrier requise.'], 401);
        }

        $validated = $request->validate([
            'activity_plan_id' => ['required', 'integer', 'exists:retreat_activity_plans,id'],
            'participant_id' => ['required', 'integer', 'exists:retreat_participant,id'],
            'status' => ['required', 'string', 'in:present,absent,late,excused'],
            'excuse_note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = app(RetreatAtelierAttendancePanelService::class)->setAttendance(
            $user,
            (int) $validated['activity_plan_id'],
            (int) $validated['participant_id'],
            (string) $validated['status'],
            $validated['excuse_note'] ?? null,
        );

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], 403);
        }

        return response()->json(['message' => $result['message']]);
    }

    /**
     * Enregistre le motif d'excuse (portail ouvrier).
     */
    public function attendanceExcuse(Request $request): JsonResponse
    {
        $user = $this->currentVerifier($request);
        if (! $user) {
            return response()->json(['message' => 'Connexion ouvrier requise.'], 401);
        }

        $validated = $request->validate([
            'activity_plan_id' => ['required', 'integer', 'exists:retreat_activity_plans,id'],
            'participant_id' => ['required', 'integer', 'exists:retreat_participant,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = app(RetreatAtelierAttendancePanelService::class)->saveExcuseNote(
            $user,
            (int) $validated['activity_plan_id'],
            (int) $validated['participant_id'],
            $validated['note'] ?? null,
        );

        if (! $result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function chatbotContext(): JsonResponse
    {
        $event = ChurchEvent::query()
            ->with('retreatDetail')
            ->where('type', 'retraite')
            ->where('is_active', true)
            ->latest('start_at')
            ->first();

        $policies = $event
            ? RetreatPolicy::query()
                ->where('is_active', true)
                ->whereIn('target_audience', ['all', 'participant'])
                ->where(function (Builder $query) use ($event): void {
                    $query->whereNull('event_id')->orWhere('event_id', $event->id);
                })
                ->orderByDesc('is_mandatory')
                ->orderBy('severity_level')
                ->limit(8)
                ->get(['title', 'content'])
            : collect();

        return response()->json([
            'data' => [
                'event' => $event ? [
                    'name' => $event->name,
                    'start_at' => $event->start_at?->toISOString(),
                    'end_at' => $event->end_at?->toISOString(),
                    'has_started' => $event->start_at?->isPast() ?? false,
                    'location' => $event->location,
                    'price_to_pay' => $event->price_to_pay,
                    'currency' => $event->currency,
                    'theme' => $event->retreatDetail?->theme,
                    'speaker' => $event->retreatDetail?->speaker,
                    'notes' => $event->retreatDetail?->notes,
                ] : null,
                'policies' => $policies->map(fn (RetreatPolicy $policy): array => [
                    'title' => $policy->title,
                    'content' => Str::limit(strip_tags((string) $policy->content), 260),
                ])->values(),
            ],
        ]);
    }

    protected function currentVerifier(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION_USER_ID);
        if (! $id) {
            return null;
        }

        $user = User::query()->find($id);

        return $user && $this->canVerifyRetreatRegistrations($user) ? $user : null;
    }

    protected function canVerifyRetreatRegistrations(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasAnyRole(['super_admin', 'panel_user', 'ouvrier', 'worker', 'staff'])) {
            return true;
        }

        $function = Str::lower((string) $user->fonction_metier);

        return in_array($function, [
            'ouvrier',
            'worker',
            'staff',
            'encadreur',
            'responsable_chambre',
            'responsable_atelier',
        ], true);
    }

    protected function otpCacheKey(string $email): string
    {
        return 'retreat_worker_otp:'.sha1($email);
    }

    protected function otpThrottleKey(string $email): string
    {
        return 'retreat_worker_otp_throttle:'.sha1($email);
    }

    protected function extractQrToken(string $value): ?string
    {
        if (preg_match('/[?&]token=([A-Za-z0-9]{32})/', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('#/justificatif/([A-Za-z0-9]{32})#', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('#/acces/([A-Za-z0-9]{32})#', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('#/billet/([A-Za-z0-9]{32})#', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[A-Za-z0-9]{32}$/', $value)) {
            return $value;
        }

        return null;
    }

    protected function participantPayload(RetreatParticipant $participant, ?User $verifier = null): array
    {
        $payment = $participant->payments->sortByDesc('id')->first();
        $eventStarted = $participant->event?->start_at?->isPast() ?? false;
        $canManageRegistrations = $verifier?->hasAnyRole(['super_admin', 'panel_user']) ?? false;

        return [
            'id' => $participant->id,
            'full_name' => $participant->full_name,
            'nom' => $participant->nom,
            'postnom' => $participant->postnom,
            'prenom' => $participant->prenom,
            'email' => $participant->email,
            'telephone' => $participant->telephone,
            'telephone_urgence' => $participant->telephone_urgence,
            'guardian_name' => $participant->guardian_name,
            'guardian_phone' => $participant->guardian_phone,
            'registration_status' => $participant->registration_status,
            'paiement_valide' => $participant->paiement_valide,
            'present' => $participant->present,
            'badge_received' => (bool) $participant->badge_received,
            'badge_received_at' => $participant->badge_received_at?->toISOString(),
            'billet_envoye' => (bool) $participant->billet_envoye,
            'date_billet_envoye' => $participant->date_billet_envoye?->toISOString(),
            'can_manage_registrations' => $canManageRegistrations,
            'registration_validated' => (bool) $participant->paiement_valide
                && in_array($participant->registration_status, ['completed', 'confirmed', 'valide'], true),
            'photo_url' => $participant->getFilamentAvatarUrl(),
            'event' => $participant->event ? [
                'name' => $participant->event->name,
                'start_at' => $participant->event->start_at?->toISOString(),
                'has_started' => $eventStarted,
            ] : null,
            'actions_enabled' => $eventStarted,
            'actions_available_at' => $participant->event?->start_at?->toISOString(),
            'chambre' => $participant->chambre?->nom,
            'atelier' => $participant->atelier?->numero,
            'payment' => $payment ? [
                'reference' => $payment->reference,
                'etat' => $payment->etat,
                'channel' => $payment->channel,
                'amount_expected' => $payment->amount_expected,
                'amount_paid' => $payment->amount_paid,
                'currency' => $payment->currency,
                'paid_at' => $payment->paid_at?->toISOString(),
            ] : null,
            'justificatif_url' => $participant->download_token
                ? route('retraite.inscription.justificatif', ['token' => $participant->download_token])
                : null,
        ];
    }

    protected function markCurrentActivityAccess(RetreatParticipant $participant): void
    {
        $plan = RetreatActivityPlan::query()
            ->where('is_active', true)
            ->whereHas('session', fn (Builder $query): Builder => $query->where('event_id', $participant->event_id))
            ->orderBy('starts_at')
            ->first();

        if (! $plan) {
            $participant->update([
                'present' => true,
                'date_presence' => $participant->date_presence ?? now(),
            ]);

            return;
        }

        RetreatActivityAttendance::query()->updateOrCreate(
            [
                'activity_plan_id' => $plan->id,
                'participant_id' => $participant->id,
            ],
            [
                'status' => 'present',
                'check_in_at' => now(),
                'scan_source' => 'manual',
                'is_active' => true,
            ],
        );

        $participant->update([
            'present' => true,
            'date_presence' => $participant->date_presence ?? now(),
        ]);
    }

    /**
     * Marque le badge physique comme remis au participant.
     *
     * @param RetreatParticipant $participant Participant concerné
     * @return void
     */
    protected function markBadgeReceived(RetreatParticipant $participant): void
    {
        $participant->update([
            'badge_received' => true,
            'badge_received_at' => now(),
        ]);
    }

    protected function publicParticipantPayload(RetreatParticipant $participant): array
    {
        $payment = $participant->payments->sortByDesc('id')->first();
        $status = $participant->paiement_valide
            ? 'completed'
            : (($participant->registration_status === 'pending_payment' || $payment?->etat === 'en_cours') ? 'pending' : ($participant->registration_status ?? 'pending'));
        $canResumePayment = ! $participant->paiement_valide
            && in_array($status, ['pending', 'pending_payment'], true)
            && filled($payment?->reference);

        return [
            'full_name' => $participant->full_name,
            'photo_url' => $participant->getFilamentAvatarUrl(),
            'event' => $participant->event ? [
                'name' => $participant->event->name,
                'start_at' => $participant->event->start_at?->toISOString(),
            ] : null,
            'status' => $status,
            'status_label' => match ($status) {
                'completed' => 'Inscription validée',
                'pending', 'pending_payment' => 'Inscription en cours de validation',
                default => 'Inscription enregistrée',
            },
            'payment' => $payment ? [
                'reference' => $payment->reference,
                'etat' => $payment->etat,
                'amount_expected' => $payment->amount_expected,
                'currency' => $payment->currency,
            ] : null,
            'justificatif_url' => $participant->download_token
                ? route('retraite.inscription.justificatif', ['token' => $participant->download_token])
                : null,
            'can_resume_payment' => $canResumePayment,
            'resume_payment_url' => $canResumePayment
                ? route('retraite.inscription', ['resume_payment_ref' => $payment?->reference])
                : null,
        ];
    }
}

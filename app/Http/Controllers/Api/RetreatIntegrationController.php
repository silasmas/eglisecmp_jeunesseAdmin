<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChurchEvent;
use App\Models\RetreatActivityAttendance;
use App\Models\RetreatActivityPlan;
use App\Models\RetreatAtelier;
use App\Models\RetreatChambre;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatPolicy;
use App\Models\RetreatSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RetreatIntegrationController extends Controller
{
    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => [
                'events' => ChurchEvent::query()->where('type', 'retraite')->count(),
                'active_event' => $this->eventPayload(
                    ChurchEvent::query()
                        ->where('type', 'retraite')
                        ->where('is_active', true)
                        ->latest('start_at')
                        ->first()
                ),
                'participants' => RetreatParticipant::query()->count(),
                'participants_present' => RetreatParticipant::query()->where('present', true)->count(),
                'chambres' => RetreatChambre::query()->count(),
                'ateliers' => RetreatAtelier::query()->count(),
                'activity_plans' => RetreatActivityPlan::query()->count(),
                'attendances' => RetreatActivityAttendance::query()->count(),
                'policies' => RetreatPolicy::query()->where('is_active', true)->count(),
            ],
        ]);
    }

    public function participants(Request $request): JsonResponse
    {
        $participants = RetreatParticipant::query()
            ->with(['chambre.responsable', 'atelier.responsable'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('chambre_id'), fn (Builder $query): Builder => $query->where('chambre_id', $request->integer('chambre_id')))
            ->when($request->filled('atelier_id'), fn (Builder $query): Builder => $query->where('atelier_id', $request->integer('atelier_id')))
            ->when($request->filled('present'), fn (Builder $query): Builder => $query->where('present', $request->boolean('present')))
            ->orderBy('prenom')
            ->orderBy('nom')
            ->paginate($this->perPage($request))
            ->through(fn (RetreatParticipant $participant): array => $this->participantPayload($participant));

        return response()->json($participants);
    }

    public function storeParticipant(Request $request): JsonResponse
    {
        $validated = $request->validate($this->participantValidationRules());

        $alreadyExists = RetreatParticipant::query()
            ->where('nom', $validated['nom'])
            ->where('prenom', $validated['prenom'])
            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'message' => 'Un participant avec ce nom et ce prenom existe deja.',
            ], 409);
        }

        $participant = $this->createParticipant($validated);

        return response()->json([
            'message' => 'Participant cree avec succes.',
            'data' => $this->participantPayload($participant->load(['chambre.responsable', 'atelier.responsable', 'payments.event'])),
        ], 201);
    }

    public function storeParticipantRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            ...$this->participantValidationRules(),
            'payment' => ['nullable', 'array'],
            ...$this->paymentValidationRules('payment.'),
        ]);

        $participant = DB::transaction(function () use ($validated): RetreatParticipant {
            $participantData = Arr::except($validated, ['payment']);

            $alreadyExists = RetreatParticipant::query()
                ->where('nom', $participantData['nom'])
                ->where('prenom', $participantData['prenom'])
                ->exists();

            if ($alreadyExists) {
                abort(response()->json([
                    'message' => 'Un participant avec ce nom et ce prenom existe deja.',
                ], 409));
            }

            $participant = $this->createParticipant($participantData);

            if (! empty($validated['payment'] ?? [])) {
                $this->saveParticipantPayment($participant, $validated['payment']);
            }

            return $participant;
        });

        return response()->json([
            'message' => 'Inscription du participant enregistree avec succes.',
            'data' => $this->participantPayload($participant->load(['chambre.responsable', 'atelier.responsable', 'payments.event'])),
        ], 201);
    }

    public function participant(RetreatParticipant $participant): JsonResponse
    {
        return response()->json([
            'data' => $this->participantPayload($participant->load(['chambre.responsable', 'atelier.responsable', 'payments.event'])),
        ]);
    }

    public function participantRegistration(RetreatParticipant $participant): JsonResponse
    {
        return response()->json([
            'data' => $this->participantPayload($participant->load(['chambre.responsable', 'atelier.responsable', 'payments.event'])),
        ]);
    }

    public function storeParticipantPayment(Request $request, RetreatParticipant $participant): JsonResponse
    {
        $validated = $request->validate($this->paymentValidationRules());
        $payment = $this->saveParticipantPayment($participant, $validated);

        return response()->json([
            'message' => 'Paiement du participant enregistre avec succes.',
            'data' => [
                'participant' => $this->participantPayload($participant->fresh(['chambre.responsable', 'atelier.responsable', 'payments.event'])),
                'payment' => $this->paymentPayload($payment->load('event')),
            ],
        ], $payment->wasRecentlyCreated ? 201 : 200);
    }

    public function chambres(Request $request): JsonResponse
    {
        $chambres = RetreatChambre::query()
            ->with('responsable')
            ->withCount('participants')
            ->when($request->filled('sexe'), fn (Builder $query): Builder => $query->where('sexe', $request->string('sexe')->toString()))
            ->where('is_active', true)
            ->orderBy('nom')
            ->paginate($this->perPage($request))
            ->through(fn (RetreatChambre $chambre): array => [
                'id' => $chambre->id,
                'nom' => $chambre->nom,
                'capacite' => $chambre->capacite,
                'sexe' => $chambre->sexe,
                'responsable' => $this->userPayload($chambre->responsable),
                'participants_count' => $chambre->participants_count,
                'places_restantes' => max(($chambre->capacite ?? 0) - ($chambre->participants_count ?? 0), 0),
                'description' => $chambre->description,
            ]);

        return response()->json($chambres);
    }

    public function ateliers(Request $request): JsonResponse
    {
        $ateliers = RetreatAtelier::query()
            ->with('responsable')
            ->withCount('participants')
            ->where('is_active', true)
            ->orderBy('numero')
            ->paginate($this->perPage($request))
            ->through(fn (RetreatAtelier $atelier): array => [
                'id' => $atelier->id,
                'numero' => $atelier->numero,
                'responsable' => $this->userPayload($atelier->responsable),
                'participants_count' => $atelier->participants_count,
                'description' => $atelier->description,
            ]);

        return response()->json($ateliers);
    }

    public function sessions(Request $request): JsonResponse
    {
        $sessions = RetreatSession::query()
            ->with('event')
            ->where('is_active', true)
            ->orderBy('start_at')
            ->paginate($this->perPage($request))
            ->through(fn (RetreatSession $session): array => [
                'id' => $session->id,
                'title' => $session->title,
                'room' => $session->room,
                'start_at' => $session->start_at?->toISOString(),
                'end_at' => $session->end_at?->toISOString(),
                'event' => $this->eventPayload($session->event),
            ]);

        return response()->json($sessions);
    }

    public function activityPlans(Request $request): JsonResponse
    {
        $plans = RetreatActivityPlan::query()
            ->with('session.event')
            ->when($request->filled('session_id'), fn (Builder $query): Builder => $query->where('session_id', $request->integer('session_id')))
            ->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')->toString()))
            ->where('is_active', true)
            ->orderBy('session_id')
            ->orderBy('starts_at')
            ->paginate($this->perPage($request))
            ->through(fn (RetreatActivityPlan $plan): array => [
                'id' => $plan->id,
                'title' => $plan->title,
                'activity_type' => $plan->activity_type,
                'starts_at' => $plan->starts_at?->format('H:i:s'),
                'ends_at' => $plan->ends_at?->format('H:i:s'),
                'location' => $plan->location,
                'is_mandatory' => $plan->is_mandatory,
                'status' => $plan->status,
                'notes' => $plan->notes,
                'session' => [
                    'id' => $plan->session?->id,
                    'title' => $plan->session?->title,
                    'start_at' => $plan->session?->start_at?->toISOString(),
                    'event' => $this->eventPayload($plan->session?->event),
                ],
            ]);

        return response()->json($plans);
    }

    public function policies(Request $request): JsonResponse
    {
        $policies = RetreatPolicy::query()
            ->with('event')
            ->when($request->filled('target_audience'), fn (Builder $query): Builder => $query->where('target_audience', $request->string('target_audience')->toString()))
            ->where('is_active', true)
            ->orderByDesc('is_mandatory')
            ->orderBy('severity_level')
            ->paginate($this->perPage($request))
            ->through(fn (RetreatPolicy $policy): array => [
                'id' => $policy->id,
                'category' => $policy->category,
                'title' => $policy->title,
                'content' => $policy->content,
                'target_audience' => $policy->target_audience,
                'severity_level' => $policy->severity_level,
                'is_mandatory' => $policy->is_mandatory,
                'effective_from' => $policy->effective_from?->toISOString(),
                'effective_to' => $policy->effective_to?->toISOString(),
                'event' => $this->eventPayload($policy->event),
            ]);

        return response()->json($policies);
    }

    public function attendances(Request $request): JsonResponse
    {
        $attendances = RetreatActivityAttendance::query()
            ->with(['activityPlan.session.event', 'participant.chambre', 'participant.atelier', 'recorder'])
            ->when($request->filled('activity_plan_id'), fn (Builder $query): Builder => $query->where('activity_plan_id', $request->integer('activity_plan_id')))
            ->when($request->filled('participant_id'), fn (Builder $query): Builder => $query->where('participant_id', $request->integer('participant_id')))
            ->when($request->filled('status'), fn (Builder $query): Builder => $query->where('status', $request->string('status')->toString()))
            ->latest('updated_at')
            ->paginate($this->perPage($request))
            ->through(fn (RetreatActivityAttendance $attendance): array => $this->attendancePayload($attendance));

        return response()->json($attendances);
    }

    public function storeAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activity_plan_id' => ['required', 'exists:retreat_activity_plans,id'],
            'participant_id' => ['required', 'exists:retreat_participant,id'],
            'status' => ['required', Rule::in(['present', 'late', 'absent', 'excused'])],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date', 'after_or_equal:check_in_at'],
            'scan_source' => ['nullable', Rule::in(['manual', 'qr', 'nfc', 'api'])],
            'recorded_by' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $attendance = RetreatActivityAttendance::query()->updateOrCreate(
            [
                'activity_plan_id' => $validated['activity_plan_id'],
                'participant_id' => $validated['participant_id'],
            ],
            [
                'status' => $validated['status'],
                'check_in_at' => $validated['check_in_at'] ?? null,
                'check_out_at' => $validated['check_out_at'] ?? null,
                'scan_source' => $validated['scan_source'] ?? 'api',
                'recorded_by' => $validated['recorded_by'] ?? null,
                'note' => $validated['note'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ],
        );

        if (in_array($attendance->status, ['present', 'late'], true)) {
            $attendance->participant()->update([
                'present' => true,
                'date_presence' => $attendance->check_in_at ?? now(),
            ]);
        }

        return response()->json([
            'message' => 'Pointage enregistre avec succes.',
            'data' => $this->attendancePayload($attendance->load(['activityPlan.session.event', 'participant.chambre', 'participant.atelier', 'recorder'])),
        ], $attendance->wasRecentlyCreated ? 201 : 200);
    }

    private function participantValidationRules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'email' => ['nullable', 'email', 'max:254'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'sexe' => ['nullable', Rule::in(['homme', 'femme'])],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone_urgence' => ['nullable', 'string', 'max:20'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'participant_type' => ['nullable', Rule::in(['internal', 'external'])],
            'role_participant' => ['nullable', 'string', 'max:20'],
            'chambre_id' => ['nullable', 'exists:retreat_chambre,id'],
            'atelier_id' => ['nullable', 'exists:retreat_atelier,id'],
            'observation' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function paymentValidationRules(string $prefix = ''): array
    {
        return [
            "{$prefix}event_id" => ['nullable', 'exists:events_event,id'],
            "{$prefix}amount_expected" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}amount_paid" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}currency" => ['nullable', 'string', 'max:5'],
            "{$prefix}channel" => ['nullable', 'string', 'max:20'],
            "{$prefix}phone" => ['nullable', 'string', 'max:30'],
            "{$prefix}provider_reference" => ['nullable', 'string', 'max:100'],
            "{$prefix}provider_status_code" => ['nullable', 'string', 'max:20'],
            "{$prefix}provider_message" => ['nullable', 'string', 'max:255'],
            "{$prefix}etat" => ['nullable', Rule::in(['init', 'en_cours', 'payee', 'annulee', 'echouee', 'remboursee'])],
            "{$prefix}paid_at" => ['nullable', 'date'],
            "{$prefix}access_granted" => ['nullable', 'boolean'],
        ];
    }

    private function createParticipant(array $validated): RetreatParticipant
    {
        return RetreatParticipant::query()->create([
            ...$validated,
            'participant_type' => $validated['participant_type'] ?? 'internal',
            'role_participant' => $validated['role_participant'] ?? 'participant',
            'paiement_valide' => false,
            'present' => false,
            'billet_envoye' => false,
            'download_token' => Str::random(32),
            'is_verified' => false,
            'billet_envoye_email' => false,
            'billet_envoye_whatsapp' => false,
            'registration_status' => 'pending',
            'is_active' => true,
        ]);
    }

    private function saveParticipantPayment(RetreatParticipant $participant, array $validated): RetreatPayment
    {
        $event = filled($validated['event_id'] ?? null)
            ? ChurchEvent::query()->find($validated['event_id'])
            : $this->activeRetreatEvent();

        if (! $event) {
            abort(response()->json([
                'message' => 'Aucun evenement retraite actif trouve pour associer ce paiement.',
            ], 422));
        }

        $amountExpected = $validated['amount_expected'] ?? $event->price_to_pay ?? 0;
        $amountPaid = $validated['amount_paid'] ?? 0;
        $etat = $validated['etat'] ?? ((float) $amountPaid >= (float) $amountExpected && (float) $amountExpected > 0 ? 'payee' : 'en_cours');
        $accessGranted = $validated['access_granted'] ?? ($etat === 'payee');

        $payment = RetreatPayment::query()->firstOrNew([
            'participant_id' => $participant->id,
            'event_id' => $event->id,
        ]);

        if (! $payment->exists) {
            $payment->reference = $this->generatePaymentReference();
        }

        $payment->fill([
            'amount_expected' => $amountExpected,
            'amount_paid' => $amountPaid,
            'currency' => $validated['currency'] ?? $event->currency ?? 'USD',
            'channel' => $validated['channel'] ?? 'mobile_money',
            'phone' => $validated['phone'] ?? $participant->telephone,
            'provider_reference' => $validated['provider_reference'] ?? $payment->provider_reference,
            'provider_status_code' => $validated['provider_status_code'] ?? $payment->provider_status_code,
            'provider_message' => $validated['provider_message'] ?? $payment->provider_message,
            'etat' => $etat,
            'access_granted' => $accessGranted,
            'access_granted_at' => $accessGranted ? ($payment->access_granted_at ?? now()) : null,
            'paid_at' => $validated['paid_at'] ?? ($etat === 'payee' ? ($payment->paid_at ?? now()) : null),
            'is_active' => true,
        ])->save();

        if ($payment->etat === 'payee' || $payment->access_granted) {
            $participant->update([
                'paiement_valide' => true,
                'preuve_paiement' => $payment->provider_reference ?? $payment->reference,
                'registration_status' => 'completed',
            ]);
        }

        return $payment;
    }

    private function activeRetreatEvent(): ?ChurchEvent
    {
        return ChurchEvent::query()
            ->where('type', 'retraite')
            ->where('is_active', true)
            ->latest('start_at')
            ->first();
    }

    private function generatePaymentReference(): string
    {
        return 'RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 25), 1), 100);
    }

    private function participantPayload(?RetreatParticipant $participant): ?array
    {
        if (! $participant) {
            return null;
        }

        $photo = $participant->photo;

        $payload = [
            'id' => $participant->id,
            'nom' => $participant->nom,
            'prenom' => $participant->prenom,
            'full_name' => $participant->full_name,
            'age' => $participant->age,
            'email' => $participant->email,
            'telephone' => $participant->telephone,
            'sexe' => $participant->sexe,
            'adresse' => $participant->adresse,
            'telephone_urgence' => $participant->telephone_urgence,
            'guardian_name' => $participant->guardian_name,
            'guardian_phone' => $participant->guardian_phone,
            'participant_type' => $participant->participant_type,
            'role_participant' => $participant->role_participant,
            'observation' => $participant->observation,
            'photo_url' => app(\App\Services\PublicStorageUrl::class)->fromPath($photo),
            'paiement_valide' => $participant->paiement_valide,
            'preuve_paiement' => $participant->preuve_paiement,
            'present' => $participant->present,
            'registration_status' => $participant->registration_status,
            'chambre' => $participant->chambre ? [
                'id' => $participant->chambre->id,
                'nom' => $participant->chambre->nom,
                'responsable' => $this->userPayload($participant->chambre->responsable),
            ] : null,
            'atelier' => $participant->atelier ? [
                'id' => $participant->atelier->id,
                'numero' => $participant->atelier->numero,
                'responsable' => $this->userPayload($participant->atelier->responsable),
            ] : null,
        ];

        if ($participant->relationLoaded('payments')) {
            $payload['payments'] = $participant->payments
                ->map(fn (RetreatPayment $payment): array => $this->paymentPayload($payment))
                ->values()
                ->all();
        }

        return $payload;
    }

    private function paymentPayload(RetreatPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->reference,
            'amount_expected' => $payment->amount_expected,
            'amount_paid' => $payment->amount_paid,
            'currency' => $payment->currency,
            'channel' => $payment->channel,
            'phone' => $payment->phone,
            'etat' => $payment->etat,
            'access_granted' => $payment->access_granted,
            'access_granted_at' => $payment->access_granted_at?->toISOString(),
            'paid_at' => $payment->paid_at?->toISOString(),
            'provider_reference' => $payment->provider_reference,
            'provider_status_code' => $payment->provider_status_code,
            'provider_message' => $payment->provider_message,
            'event' => $this->eventPayload($payment->event),
        ];
    }

    private function attendancePayload(RetreatActivityAttendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'status' => $attendance->status,
            'check_in_at' => $attendance->check_in_at?->toISOString(),
            'check_out_at' => $attendance->check_out_at?->toISOString(),
            'scan_source' => $attendance->scan_source,
            'note' => $attendance->note,
            'participant' => $this->participantPayload($attendance->participant),
            'activity_plan' => [
                'id' => $attendance->activityPlan?->id,
                'title' => $attendance->activityPlan?->title,
                'activity_type' => $attendance->activityPlan?->activity_type,
                'starts_at' => $attendance->activityPlan?->starts_at?->format('H:i:s'),
                'ends_at' => $attendance->activityPlan?->ends_at?->format('H:i:s'),
                'session' => [
                    'id' => $attendance->activityPlan?->session?->id,
                    'title' => $attendance->activityPlan?->session?->title,
                    'event' => $this->eventPayload($attendance->activityPlan?->session?->event),
                ],
            ],
            'recorded_by' => $this->userPayload($attendance->recorder),
        ];
    }

    private function eventPayload(?ChurchEvent $event): ?array
    {
        if (! $event) {
            return null;
        }

        return [
            'id' => $event->id,
            'name' => $event->name,
            'type' => $event->type,
            'start_at' => $event->start_at?->toISOString(),
            'end_at' => $event->end_at?->toISOString(),
            'location' => $event->location,
            'capacity' => $event->capacity,
            'price_to_pay' => $event->price_to_pay,
            'currency' => $event->currency,
            'is_active' => $event->is_active,
        ];
    }

    private function userPayload($user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}

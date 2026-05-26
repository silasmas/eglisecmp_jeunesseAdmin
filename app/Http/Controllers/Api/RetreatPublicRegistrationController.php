<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventAccessAuthMode;
use App\Enums\EventAccessOtpChannel;
use App\Http\Controllers\Controller;
use App\Mail\RetreatParentContactOtpMail;
use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatPayment;
use App\Models\RetreatPolicy;
use App\Models\SmsMessageLog;
use App\Models\User;
use App\Services\FlexPay\FlexPayCardService;
use App\Services\FlexPay\FlexPayMobileService;
use App\Services\KeccelSmsService;
use App\Services\PublicStorageUrl;
use App\Services\RetreatCashPaymentAdminNotifier;
use App\Services\RetreatPlacementAssignmentService;
use App\Services\RetreatInscriptionFunnelService;
use App\Services\RetreatInscriptionPaymentCompletionService;
use App\Services\StoragePathService;
use App\Support\StoragePath;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RetreatPublicRegistrationController extends Controller
{
    private const PARENT_CONTACT_OTP_TTL_MINUTES = 10;
    private const PARENT_CONTACT_VERIFIED_TTL_HOURS = 24;

    public function __construct(
        protected FlexPayMobileService $flexPayMobile,
        protected FlexPayCardService $flexPayCard,
        protected RetreatInscriptionPaymentCompletionService $paymentCompletion,
        protected KeccelSmsService $keccelSms,
        protected RetreatCashPaymentAdminNotifier $cashPaymentAdminNotifier,
        protected RetreatInscriptionFunnelService $inscriptionFunnel,
    ) {}

    /**
     * Règles / politiques à afficher avant le paiement (inscription publique).
     */
    public function inscriptionPolicies(Request $request): JsonResponse
    {
        $event = $this->resolveEvent($request);

        if (! $event) {
            return response()->json(['message' => 'Aucun événement éligible.'], 404);
        }

        $policies = $this->publicPoliciesForEventQuery($event)
            ->orderByDesc('is_mandatory')
            ->orderBy('severity_level')
            ->get([
                'id',
                'title',
                'content',
                'category',
                'is_mandatory',
                'severity_level',
                'target_audience',
            ]);

        $mandatoryIds = $policies->where('is_mandatory', true)->pluck('id')->values()->all();

        return response()->json([
            'data' => [
                'policies' => $policies,
                'mandatory_policy_ids' => $mandatoryIds,
            ],
        ]);
    }

    /** Reçu / récap sécurisé par référence de paiement (retour carte, etc.). */
    public function paymentReceipt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:64'],
        ]);

        $payment = RetreatPayment::query()
            ->with(['participant', 'event'])
            ->where('reference', $validated['reference'])
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Transaction introuvable.'], 404);
        }

        $participant = $payment->participant;

        return response()->json([
            'data' => [
                'reference' => $payment->reference,
                'etat' => $payment->etat,
                'amount_expected' => $payment->amount_expected,
                'amount_paid' => $payment->amount_paid,
                'currency' => $payment->currency,
                'channel' => $payment->channel,
                'paid_at' => $payment->paid_at?->toISOString(),
                'event' => $payment->event ? [
                    'name' => $payment->event->name,
                    'start_at' => $payment->event->start_at?->toISOString(),
                    'location' => $payment->event->location,
                ] : null,
                'participant' => $participant ? [
                    'id' => $participant->id,
                    'full_name' => $participant->full_name,
                    'paiement_valide' => $participant->paiement_valide,
                ] : null,
            ],
        ]);
    }

    public function activeEvent(Request $request): JsonResponse
    {
        $event = $this->resolveEvent($request);

        if (! $event) {
            return response()->json([
                'message' => 'Aucun événement retraite actif avec inscriptions ouvertes (vérifiez : type « retraite », actif, date de fin non dépassée).',
            ], 404);
        }

        return response()->json([
            'data' => $this->publicEventPayload($event),
        ]);
    }

    public function workerPrefill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:254'],
        ]);

        $identifier = trim((string) $validated['identifier']);
        $digits = preg_replace('/\D+/', '', $identifier) ?: '';

        $user = User::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($identifier, $digits): void {
                $query->whereRaw('LOWER(TRIM(email)) = ?', [Str::lower($identifier)]);

                foreach (['telephone', 'phone', 'tel', 'mobile'] as $column) {
                    if (Schema::hasColumn('users', $column) && strlen($digits) >= 6) {
                        $query->orWhere($column, 'like', "%{$digits}%");
                    }
                }
            })
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Aucun compte actif ne correspond a cet e-mail ou telephone.',
            ], 404);
        }

        if (! $this->isWorkerUser($user)) {
            return response()->json([
                'message' => 'Ce compte n’est pas un ouvrier de la jeunesse. Utilisez le formulaire participant.',
            ], 422);
        }

        $parts = preg_split('/\s+/u', trim((string) $user->name), 3) ?: [];
        $phone = null;
        foreach (['telephone', 'phone', 'tel', 'mobile'] as $column) {
            if (Schema::hasColumn('users', $column) && filled($user->{$column})) {
                $phone = (string) $user->{$column};
                break;
            }
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $phone,
                'nom' => $user->getAttribute('nom') ?: ($parts[0] ?? ''),
                'postnom' => $user->getAttribute('postnom') ?: (count($parts) >= 3 ? ($parts[1] ?? '') : ''),
                'prenom' => $user->getAttribute('prenom') ?: (count($parts) >= 3 ? ($parts[2] ?? '') : ($parts[1] ?? '')),
                'sexe' => $user->getAttribute('sexe'),
                'date_naissance' => $user->date_naissance?->format('Y-m-d'),
                'role_jeunesse' => $user->getAttribute('role_jeunesse') ?: $user->getAttribute('role_participant') ?: 'Ouvrier',
                'indicatif_telephone' => $user->getAttribute('indicatif_telephone'),
                'telephone_urgence' => $user->getAttribute('telephone_urgence'),
                'guardian_name' => $user->getAttribute('guardian_name'),
                'guardian_phone' => $user->getAttribute('guardian_phone'),
                'adresse' => $user->getAttribute('adresse'),
                'commune' => $user->getAttribute('commune'),
                'ville' => $user->getAttribute('ville'),
                'eglise_assemblee' => $user->getAttribute('eglise_assemblee'),
                'departement_cellule' => $user->getAttribute('departement_cellule'),
                'hebergement_choice' => $user->getAttribute('hebergement_choice'),
                'observation' => $user->getAttribute('observation'),
                'fonction_metier' => $user->fonction_metier,
                'roles' => $user->roles()->pluck('name')->values(),
            ],
        ]);
    }

    /**
     * Indication temps réel : liens possibles avec une autre inscription (tél. / tuteur).
     */
    public function tutorFamilyHint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tel_urgence' => ['nullable', 'string', 'max:30'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'indicatif' => ['required', 'string', 'max:10'],
            'event_id' => ['nullable', 'integer', 'exists:events_event,id'],
        ]);

        $emptyBase = fn (): array => [
            'eligible' => false,
            'matches_registered_participant' => false,
            'tutor_matches_registered_participant' => false,
            'guardian_matches_registered_participant' => false,
            'guardian_phone_matches_registered_guardian' => false,
            'guardian_name_matches_registered_guardian_name' => false,
            'hint' => null,
            'hint_tutor' => null,
            'hint_guardian' => null,
            'hint_guardian_dup_phone' => null,
            'hint_guardian_dup_name' => null,
            'masked_matches' => [],
        ];

        $emptyTutor = ! filled($validated['tel_urgence'] ?? null);
        $emptyGuardian = ! filled($validated['guardian_phone'] ?? null);
        $guardianNameRaw = isset($validated['guardian_name']) ? trim((string) $validated['guardian_name']) : '';
        $nameKey = $this->normalizeGuardianNameKey(Str::limit($guardianNameRaw, 150, ''));

        if ($emptyTutor && $emptyGuardian && $nameKey === null) {
            return response()->json(['data' => $emptyBase()]);
        }

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['data' => $emptyBase()]);
        }

        $tutorCanon = $emptyTutor
            ? null
            : $this->canonicalEmergencyPhoneDigits((string) $validated['tel_urgence'], $validated['indicatif']);
        $guardianCanon = $emptyGuardian
            ? null
            : $this->canonicalEmergencyPhoneDigits((string) $validated['guardian_phone'], $validated['indicatif']);

        $tutorDigitsRaw = $emptyTutor ? '' : (preg_replace('/\D+/', '', (string) $validated['tel_urgence']) ?: '');
        $guardianDigitsRaw = $emptyGuardian ? '' : (preg_replace('/\D+/', '', (string) $validated['guardian_phone']) ?: '');

        $tutorOk = $tutorCanon !== null && strlen($tutorCanon) >= 10;
        $guardianOk = $guardianCanon !== null && strlen($guardianCanon) >= 10;

        /** Saisie encore incomplète mais déjà exploitable pour repérage (évite les trous dans la vérif temps réel). */
        $tutorPartialOk = ! $emptyTutor && ! $tutorOk && strlen($tutorDigitsRaw) >= 6 && strlen($tutorDigitsRaw) <= 14;
        $guardianPartialOk = ! $emptyGuardian && ! $guardianOk && strlen($guardianDigitsRaw) >= 6 && strlen($guardianDigitsRaw) <= 14;

        $phoneInputOk = $tutorOk || $guardianOk || $tutorPartialOk || $guardianPartialOk;
        $nameInputOk = $nameKey !== null;

        if (! $phoneInputOk && ! $nameInputOk) {
            return response()->json(['data' => $emptyBase()]);
        }

        $tutorMatch = false;
        $guardianMatchMain = false;
        $guardianPhoneDup = false;
        $guardianNameDup = false;

        if ($tutorOk) {
            $tutorMatch = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where('telephone', $tutorCanon)
                ->exists();
        } elseif ($tutorPartialOk) {
            $needle = $tutorDigitsRaw;
            $tutorMatch = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where(function (Builder $query) use ($needle): void {
                    $query
                        ->where('telephone', 'like', '%'.$needle.'%')
                        ->orWhere('telephone_urgence', 'like', '%'.$needle.'%')
                        ->orWhere('guardian_phone', 'like', '%'.$needle.'%');
                })
                ->exists();
        }
        if ($guardianOk) {
            $guardianMatchMain = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where('telephone', $guardianCanon)
                ->exists();

            $g = Str::limit($guardianCanon, 20, '');
            $guardianPhoneDup = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where('guardian_phone', $g)
                ->exists();
        } elseif ($guardianPartialOk) {
            $needle = $guardianDigitsRaw;
            $guardianMatchMain = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where('telephone', 'like', '%'.$needle.'%')
                ->exists();

            $guardianPhoneDup = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->where('guardian_phone', 'like', '%'.$needle.'%')
                ->exists();
        }
        if ($nameKey !== null) {
            $guardianNameDup = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->whereNotNull('guardian_name')
                ->lazyById()
                ->contains(fn (RetreatParticipant $p): bool => $this->normalizeGuardianNameKey($p->guardian_name) === $nameKey);
        }

        $hintMain = 'Ce numéro correspond au portable principal d’une personne déjà inscrite à cette retraite.';
        $hintDupPhone = 'Ce numéro de tuteur est déjà enregistré sur une autre inscription (contact parent / tuteur identique ou partagé).';
        $hintDupName = 'Ce nom de tuteur correspond déjà à une autre inscription. Si vous faites partie du même foyer ou que le même adulte représente plusieurs jeunes inscrits, c’est normal.';

        $anyMatch = $tutorMatch || $guardianMatchMain || $guardianPhoneDup || $guardianNameDup;

        $maskedBucket = [];

        if ($anyMatch) {
            if ($tutorMatch && $tutorOk && $tutorCanon !== null) {
                foreach (RetreatParticipant::query()
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->where('telephone', $tutorCanon)
                    ->orderBy('id')
                    ->limit(25)
                    ->get(['id', 'prenom', 'nom', 'postnom']) as $p) {
                    $this->accumulateFamilyHintMatch(
                        $maskedBucket,
                        $p,
                        'Portable principal identique au numéro d’urgence indiqué.'
                    );
                }
            } elseif ($tutorMatch && $tutorPartialOk) {
                $needle = $tutorDigitsRaw;
                foreach (RetreatParticipant::query()
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->where(function (Builder $query) use ($needle): void {
                        $query
                            ->where('telephone', 'like', '%'.$needle.'%')
                            ->orWhere('telephone_urgence', 'like', '%'.$needle.'%')
                            ->orWhere('guardian_phone', 'like', '%'.$needle.'%');
                    })
                    ->orderBy('id')
                    ->limit(25)
                    ->get(['id', 'prenom', 'nom', 'postnom']) as $p) {
                    $this->accumulateFamilyHintMatch(
                        $maskedBucket,
                        $p,
                        'Numéro partiellement identique à une inscription déjà enregistrée — complétez pour confirmer.'
                    );
                }
            }
            if ($guardianMatchMain && $guardianOk && $guardianCanon !== null) {
                foreach (RetreatParticipant::query()
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->where('telephone', $guardianCanon)
                    ->orderBy('id')
                    ->limit(25)
                    ->get(['id', 'prenom', 'nom', 'postnom']) as $p) {
                    $this->accumulateFamilyHintMatch(
                        $maskedBucket,
                        $p,
                        'Portable principal identique au numéro du parent / tuteur.'
                    );
                }
            } elseif ($guardianMatchMain && $guardianPartialOk) {
                $needle = $guardianDigitsRaw;
                foreach (RetreatParticipant::query()
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->where('telephone', 'like', '%'.$needle.'%')
                    ->orderBy('id')
                    ->limit(25)
                    ->get(['id', 'prenom', 'nom', 'postnom']) as $p) {
                    $this->accumulateFamilyHintMatch(
                        $maskedBucket,
                        $p,
                        'Fragment du numéro du tuteur proche d’un portable principal déjà inscrit — vérifiez les chiffres.'
                    );
                }
            }
            if ($guardianPhoneDup && $guardianOk && $guardianCanon !== null) {
                $g = Str::limit($guardianCanon, 20, '');
                foreach (RetreatParticipant::query()
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->where('guardian_phone', $g)
                    ->orderBy('id')
                    ->limit(25)
                    ->get(['id', 'prenom', 'nom', 'postnom']) as $p) {
                    $this->accumulateFamilyHintMatch(
                        $maskedBucket,
                        $p,
                        'Ce numéro est déjà enregistré comme téléphone du tuteur sur une inscription.'
                    );
                }
            } elseif ($guardianPhoneDup && $guardianPartialOk) {
                $needle = $guardianDigitsRaw;
                foreach (RetreatParticipant::query()
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->where('guardian_phone', 'like', '%'.$needle.'%')
                    ->orderBy('id')
                    ->limit(25)
                    ->get(['id', 'prenom', 'nom', 'postnom']) as $p) {
                    $this->accumulateFamilyHintMatch(
                        $maskedBucket,
                        $p,
                        'Fragment du téléphone tuteur proche d’un numéro déjà saisi sur une autre fiche.'
                    );
                }
            }
            if ($guardianNameDup && $nameKey !== null) {
                foreach (RetreatParticipant::query()
                    ->where('event_id', $event->id)
                    ->where('is_active', true)
                    ->whereNotNull('guardian_name')
                    ->orderBy('id')
                    ->limit(200)
                    ->get(['id', 'prenom', 'nom', 'postnom', 'guardian_name']) as $p) {
                    if ($this->normalizeGuardianNameKey($p->guardian_name) === $nameKey) {
                        $this->accumulateFamilyHintMatch(
                            $maskedBucket,
                            $p,
                            'Nom du tuteur identique sur une inscription existante.'
                        );
                    }
                }
            }
        }

        $maskedMatches = collect($maskedBucket)
            ->sortBy('participant_id')
            ->values()
            ->take(25)
            ->all();

        return response()->json([
            'data' => [
                'eligible' => true,
                'matches_registered_participant' => $anyMatch,
                'tutor_matches_registered_participant' => $tutorMatch,
                'guardian_matches_registered_participant' => $guardianMatchMain,
                'guardian_phone_matches_registered_guardian' => $guardianPhoneDup,
                'guardian_name_matches_registered_guardian_name' => $guardianNameDup,
                'hint' => null,
                'hint_tutor' => $tutorMatch ? $hintMain : null,
                'hint_guardian' => $guardianMatchMain ? $hintMain : null,
                'hint_guardian_dup_phone' => $guardianPhoneDup ? $hintDupPhone : null,
                'hint_guardian_dup_name' => $guardianNameDup ? $hintDupName : null,
                'masked_matches' => $maskedMatches,
            ],
        ]);
    }

    /**
     * Temps réel : le portable principal est-il déjà utilisé par une autre inscription (même événement).
     */
    public function mainPhoneDuplicateHint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'telephone' => ['required', 'string', 'max:30'],
            'indicatif' => ['required', 'string', 'max:10'],
            'event_id' => ['nullable', 'integer', 'exists:events_event,id'],
        ]);

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json([
                'data' => [
                    'eligible' => false,
                    'duplicate_registered' => false,
                ],
            ]);
        }

        $mainCanon = $this->normalizePhone($validated['indicatif'], $validated['telephone']);

        if (strlen($mainCanon) < 10) {
            return response()->json([
                'data' => [
                    'eligible' => false,
                    'duplicate_registered' => false,
                ],
            ]);
        }

        $exists = RetreatParticipant::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->where('telephone', $mainCanon)
            ->exists();

        return response()->json([
            'data' => [
                'eligible' => true,
                'duplicate_registered' => $exists,
                'hint' => $exists
                    ? 'Ce numéro est déjà utilisé comme portable principal pour une autre inscription à cette retraite. Utilisez votre propre ligne WhatsApp ou rapprochez-vous de l’organisation.'
                    : null,
            ],
        ]);
    }

    /**
     * Temps réel : l’e-mail est-il déjà utilisé par une autre inscription (même événement).
     */
    public function emailDuplicateHint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:254'],
            'event_id' => ['nullable', 'integer', 'exists:events_event,id'],
        ]);

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json([
                'data' => [
                    'eligible' => false,
                    'duplicate_registered' => false,
                ],
            ]);
        }

        $emailNorm = Str::lower(trim($validated['email']));
        if ($emailNorm === '' || ! filter_var($emailNorm, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'data' => [
                    'eligible' => false,
                    'duplicate_registered' => false,
                ],
            ]);
        }

        $exists = RetreatParticipant::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->whereRaw('LOWER(TRIM(email)) = ?', [$emailNorm])
            ->exists();

        return response()->json([
            'data' => [
                'eligible' => true,
                'duplicate_registered' => $exists,
                'hint' => $exists
                    ? 'Cette adresse e-mail est déjà utilisée pour une autre inscription à cette retraite. Utilisez une boîte personnelle ou rapprochez-vous de l’organisation.'
                    : null,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $this->validateRegistration($request);

        $postnomNorm = isset($validated['postnom']) ? trim((string) $validated['postnom']) : '';
        $postnomNorm = $postnomNorm !== '' ? $postnomNorm : null;

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['message' => 'Aucun événement retraite actif pour enregistrer l’inscription.'], 422);
        }

        $parentGroupMode = (bool) ($validated['parent_group_mode'] ?? false);
        $parentOtpChannel = $this->parentOtpChannelForEvent($event);
        $parentContactEmail = isset($validated['parent_contact_email']) ? Str::lower(trim((string) $validated['parent_contact_email'])) : null;
        $parentContactPhone = isset($validated['parent_contact_phone']) ? $this->normalizeCdMobileMoneyMsisdn((string) $validated['parent_contact_phone']) : null;
        $parentVerifiedToken = isset($validated['parent_verified_token']) ? trim((string) $validated['parent_verified_token']) : '';
        $parentFullName = isset($validated['parent_full_name']) ? trim((string) $validated['parent_full_name']) : '';
        $parentFullName = $parentFullName !== '' ? Str::limit($parentFullName, 150, '') : null;

        if ($parentGroupMode) {
            $missingParentContact = $parentOtpChannel === 'sms'
                ? ! $parentContactPhone
                : ! $parentContactEmail;

            if ($missingParentContact || $parentVerifiedToken === '') {
                return response()->json([
                    'message' => 'Activez d’abord la vérification parent/tuteur (OTP) avant de soumettre.',
                ], 422);
            }
            if (! $this->parentContactsAreVerified($parentVerifiedToken, $parentContactEmail ?? '', $parentContactPhone ?? '')) {
                return response()->json([
                    'message' => 'La vérification parent/tuteur a expiré ou ne correspond pas aux contacts saisis. Relancez les OTP.',
                ], 422);
            }
            if (! $parentFullName) {
                return response()->json([
                    'message' => 'Après vérification du contact parent/tuteur, indiquez le nom complet du parent pour le regroupement familial.',
                ], 422);
            }
        }

        $emailCanon = Str::lower(trim((string) $validated['email']));
        if (! $parentGroupMode && $emailCanon !== '') {
            $emailTaken = RetreatParticipant::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->whereRaw('LOWER(TRIM(email)) = ?', [$emailCanon])
                ->exists();
            if ($emailTaken) {
                return response()->json([
                    'message' => 'Cette adresse e-mail est déjà utilisée pour une autre inscription à cette retraite. Indiquez une autre adresse ou rapprochez-vous de l’organisation.',
                ], 422);
            }
        }

        $mainCanon = $this->normalizePhone($validated['indicatif'], $validated['telephone']);
        $tutorCanon = $this->canonicalEmergencyPhoneDigits((string) ($validated['tel_urgence'] ?? ''), $validated['indicatif']);

        if ($tutorCanon !== null && $tutorCanon !== '' && $tutorCanon === $mainCanon) {
            return response()->json([
                'message' => 'Le téléphone d’urgence ne peut pas être identique au numéro principal (WhatsApp).',
            ], 422);
        }

        $rawGuardianPhone = isset($validated['guardian_phone']) ? trim((string) $validated['guardian_phone']) : '';
        $guardianCanon = $rawGuardianPhone !== ''
            ? $this->canonicalEmergencyPhoneDigits($rawGuardianPhone, $validated['indicatif'])
            : null;

        $guardianNameNorm = isset($validated['guardian_name']) ? trim((string) $validated['guardian_name']) : '';
        $guardianNameNorm = $guardianNameNorm !== '' ? Str::limit($guardianNameNorm, 150, '') : null;

        if ($rawGuardianPhone !== '') {
            if ($guardianCanon === null || strlen($guardianCanon) < 10) {
                return response()->json([
                    'message' => 'Le téléphone du parent ou tuteur est incomplet ou invalide. Utilisez le même format que pour le téléphone d’urgence (indication internationale avec + ou le même indicatif que votre ligne principale).',
                ], 422);
            }

            if ($guardianCanon === $mainCanon) {
                return response()->json([
                    'message' => 'Le téléphone du parent ou tuteur ne peut pas être identique au numéro principal (WhatsApp).',
                ], 422);
            }

            if ($guardianCanon === $tutorCanon && $tutorCanon !== null && $tutorCanon !== '') {
                return response()->json([
                    'message' => 'Le téléphone du parent ou tuteur ne doit pas être identique au téléphone d’urgence lorsque vous remplissez les deux.',
                ], 422);
            }
        }

        $guardianCanonStored = ($guardianCanon !== null && strlen($guardianCanon) >= 10)
            ? Str::limit($guardianCanon, 20, '')
            : null;

        $acceptedIds = collect($validated['accepted_policy_ids'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();

        if ($response = $this->validatePolicyAcceptance($acceptedIds->all(), $event)) {
            return $response;
        }

        if (! $this->isOuvrierRegistration($validated['role']) && ($message = $this->eventRegistrationClosedMessage($event))) {
            return response()->json(['message' => $message], 422);
        }

        if ($this->participantIdentityExists(
            $validated['nom'],
            $validated['prenom'],
            $postnomNorm,
            $event->id
        )) {
            return response()->json([
                'message' => 'Une inscription avec ce nom complet existe déjà.',
            ], 409);
        }

        if (! $parentGroupMode && $this->participantMainPhoneExists($mainCanon, $event->id)) {
            return response()->json([
                'message' => 'Ce numéro principal est déjà utilisé par une autre inscription pour cette retraite.',
                'code' => 'duplicate_main_phone',
            ], 409);
        }

        try {
            $participant = DB::transaction(function () use (
                $validated,
                $postnomNorm,
                $request,
                $event,
                $acceptedIds,
                $mainCanon,
                $tutorCanon,
                $guardianCanon,
                $guardianCanonStored,
                $guardianNameNorm,
                $parentGroupMode,
                $parentOtpChannel,
                $parentContactEmail,
                $parentContactPhone,
                $parentFullName,
            ): RetreatParticipant {
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = app(StoragePathService::class)->storeUploadedFile(
                        $request->file('photo'),
                        StoragePath::RETREAT_INSCRIPTION_PHOTOS
                    );
                }

                $dob = Carbon::parse($validated['date_naissance']);

                $familyGroupId = $this->resolveFamilyGroupIdFromLinkedPhones(
                    $event->id,
                    $mainCanon,
                    (bool) ($validated['same_family_emergency_confirm'] ?? false) || $parentGroupMode,
                    $tutorCanon,
                    $guardianCanon,
                    $guardianNameNorm,
                );

                if ($parentGroupMode) {
                    $familyGroupId = $this->resolveFamilyGroupIdFromVerifiedParentContacts(
                        $event->id,
                        $familyGroupId,
                        $parentOtpChannel,
                        $parentContactEmail,
                        $parentContactPhone,
                        $parentFullName
                    );
                }

                $familyContactHash = $parentGroupMode
                    ? $this->familyContactHash($parentOtpChannel, $parentContactEmail, $parentContactPhone)
                    : null;

                $participant = RetreatParticipant::query()->create([
                    'event_id' => $event->id,
                    'family_group_id' => $familyGroupId,
                    'family_group_name' => $parentGroupMode ? $parentFullName : null,
                    'family_contact_hash' => $familyContactHash,
                    'nom' => $validated['nom'],
                    'postnom' => $postnomNorm,
                    'prenom' => $validated['prenom'],
                    'date_naissance' => $dob->toDateString(),
                    'age' => $dob->age,
                    'sexe' => $this->normalizeSexe($validated['sexe']),
                    'email' => $validated['email'],
                    'telephone' => $mainCanon,
                    'indicatif_telephone' => $validated['indicatif'],
                    'telephone_urgence' => $validated['tel_urgence'] ?? null,
                    'guardian_name' => $parentGroupMode ? $parentFullName : $guardianNameNorm,
                    'guardian_phone' => $parentGroupMode && $parentContactPhone ? Str::limit($parentContactPhone, 20, '') : $guardianCanonStored,
                    'adresse' => $validated['adresse'] ?? null,
                    'commune' => $validated['commune'] ?? null,
                    'ville' => $validated['ville'] ?? null,
                    'eglise_assemblee' => $validated['eglise'] ?? null,
                    'departement_cellule' => ($validated['no_departement'] ?? false) ? null : ($validated['departement'] ?? null),
                    'hebergement_choice' => $validated['hebergement'] ?? null,
                    'observation' => $validated['observations'] ?? null,
                    'role_participant' => $this->normalizeRole($validated['role'], $validated['role_autre'] ?? null),
                    'photo' => $photoPath,
                    'participant_type' => app(RetreatPlacementAssignmentService::class)
                        ->participantTypeFromHebergement($validated['hebergement'] ?? null),
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

                $acceptedAt = now();
                $ip = $request->ip();

                foreach ($acceptedIds as $policyId) {
                    DB::table('retreat_policy_acknowledgements')->insert([
                        'policy_id' => (int) $policyId,
                        'user_id' => null,
                        'participant_id' => $participant->id,
                        'has_read' => true,
                        'has_accepted' => true,
                        'acknowledged_at' => $acceptedAt,
                        'signature_type' => 'checkbox',
                        'ip_address' => $ip,
                        'is_active' => true,
                        'created_at' => $acceptedAt,
                        'updated_at' => $acceptedAt,
                    ]);
                }

                return $participant;
            });
        } catch (QueryException $e) {
            report($e);

            if ($this->isDuplicateKeyException($e)) {
                return response()->json([
                    'message' => 'Une inscription identique (nom, prénom et post-nom) existe déjà pour cette retraite.',
                    'code' => 'duplicate_participant',
                ], 409);
            }

            return response()->json([
                'message' => 'Erreur lors de l’enregistrement en base de données. Réessayez ou contactez l’organisation.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Impossible d’enregistrer l’inscription pour le moment.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }

        $this->inscriptionFunnel->record(
            $participant,
            RetreatInscriptionFunnelService::STAGE_REGISTERED,
            'Formulaire validé — passage au paiement attendu.'
        );

        return response()->json([
            'message' => 'Inscription enregistrée. Vous pouvez procéder au paiement.',
            'data' => [
                'participant_id' => $participant->id,
                'download_token' => $participant->download_token,
                'verification_url' => $this->participantJustificatifAbsoluteUrl($participant),
                'event' => $this->publicEventPayload($event->fresh([
                    'afficheMedia',
                    'retreatDetail',
                ])),
            ],
        ], 201);
    }

    public function requestParentContactOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['nullable', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:30'],
            'event_id' => ['nullable', 'exists:events_event,id'],
        ], [
            'email.email' => 'Adresse e-mail parent/tuteur invalide.',
        ]);

        $event = $this->resolveEvent($request);
        $channel = $this->parentOtpChannelForEvent($event);
        $email = isset($validated['email']) ? Str::lower(trim((string) $validated['email'])) : '';
        $phone = isset($validated['phone']) ? $this->normalizeCdMobileMoneyMsisdn((string) $validated['phone']) : '';

        if ($channel === 'email' && $email === '') {
            return response()->json([
                'message' => 'Adresse e-mail parent/tuteur requise.',
            ], 422);
        }

        if ($channel === 'sms' && $phone === '') {
            return response()->json([
                'message' => 'Numéro parent/tuteur invalide pour l’envoi SMS.',
            ], 422);
        }

        $verificationId = Str::uuid()->toString();
        $otp = (string) random_int(100000, 999999);

        Cache::put($this->parentOtpCacheKey($verificationId), [
            'channel' => $channel,
            'email' => $email,
            'phone' => $phone,
            'otp' => password_hash($otp, PASSWORD_BCRYPT),
            'attempts' => 0,
        ], now()->addMinutes(self::PARENT_CONTACT_OTP_TTL_MINUTES));

        try {
            if ($channel === 'sms') {
                $this->sendParentContactOtpSms($phone, $otp);
                $smsLog = $this->keccelSms->lastLog();
            } else {
                Mail::to($email)->send(new RetreatParentContactOtpMail($otp, self::PARENT_CONTACT_OTP_TTL_MINUTES));
                $smsLog = null;
            }
        } catch (\Throwable $e) {
            Cache::forget($this->parentOtpCacheKey($verificationId));
            Log::error('Envoi OTP parent échoué', [
                'channel' => $channel,
                'email' => $email,
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $channel === 'sms'
                    ? 'Impossible d’envoyer l’OTP par SMS. '.$e->getMessage()
                    : 'Impossible d’envoyer l’OTP par e-mail. Vérifiez la configuration e-mail puis réessayez.',
            ], 502);
        }

        return response()->json([
            'message' => $channel === 'sms' ? 'Code OTP envoyé par SMS.' : 'Code OTP envoyé par e-mail.',
            'data' => [
                'verification_id' => $verificationId,
                'channel' => $channel,
                'sms_log_id' => $smsLog?->id,
                'sms_delivery_status' => $smsLog?->delivery_status,
                'expires_in_minutes' => self::PARENT_CONTACT_OTP_TTL_MINUTES,
            ],
        ]);
    }

    public function checkParentContactSmsDelivery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'log_id' => ['required', 'integer', 'exists:sms_message_logs,id'],
        ]);

        $log = SmsMessageLog::query()
            ->whereKey((int) $validated['log_id'])
            ->where('context', 'parent_contact_otp')
            ->firstOrFail();

        try {
            $log = $this->keccelSms->refreshDelivery($log);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => [
                    'status' => $log->status,
                    'delivery_status' => $log->delivery_status,
                ],
            ], 422);
        }

        return response()->json([
            'data' => [
                'status' => $log->status,
                'delivery_status' => $log->delivery_status,
                'checked_at' => $log->delivery_checked_at?->toISOString(),
            ],
        ]);
    }

    public function verifyParentContactOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_id' => ['required', 'string', 'max:80'],
            'otp' => ['required', 'digits:6'],
        ]);

        $verificationId = (string) $validated['verification_id'];
        $payload = Cache::get($this->parentOtpCacheKey($verificationId));

        if (! is_array($payload)) {
            return response()->json([
                'message' => 'Session OTP expirée. Demandez de nouveaux codes.',
            ], 422);
        }

        $attempts = (int) ($payload['attempts'] ?? 0) + 1;
        $payload['attempts'] = $attempts;
        if ($attempts > 8) {
            Cache::forget($this->parentOtpCacheKey($verificationId));

            return response()->json([
                'message' => 'Trop de tentatives OTP. Relancez un nouvel envoi.',
            ], 429);
        }

        $otpOk = password_verify((string) $validated['otp'], (string) ($payload['otp'] ?? ''));

        if (! $otpOk) {
            Cache::put($this->parentOtpCacheKey($verificationId), $payload, now()->addMinutes(self::PARENT_CONTACT_OTP_TTL_MINUTES));

            return response()->json([
                'message' => 'Code OTP invalide. Vérifiez le code et réessayez.',
            ], 422);
        }

        $verifiedToken = Str::random(48);
        Cache::put($this->parentVerifiedCacheKey($verifiedToken), [
            'channel' => $payload['channel'] ?? 'email',
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
        ], now()->addHours(self::PARENT_CONTACT_VERIFIED_TTL_HOURS));
        Cache::forget($this->parentOtpCacheKey($verificationId));

        return response()->json([
            'message' => 'Contacts parent/tuteur vérifiés avec succès.',
            'data' => [
                'verified_token' => $verifiedToken,
                'channel' => $payload['channel'] ?? 'email',
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? null,
                'expires_in_hours' => self::PARENT_CONTACT_VERIFIED_TTL_HOURS,
            ],
        ]);
    }

    protected function participantJustificatifAbsoluteUrl(RetreatParticipant $participant): string
    {
        return route('retraite.inscription.justificatif', ['token' => $participant->download_token], absolute: true);
    }

    protected function participantBilletAbsoluteUrl(RetreatParticipant $participant): string
    {
        return route('retraite.inscription.billet', ['token' => $participant->download_token], absolute: true);
    }

    protected function participantAccessAbsoluteUrl(RetreatParticipant $participant): string
    {
        return route('retraite.inscription.acces', ['token' => $participant->download_token], absolute: true);
    }

    /**
     * Enregistre l’étape du parcours côté navigateur (formulaire / feedback paiement).
     */
    public function recordFunnel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'integer', 'exists:retreat_participant,id'],
            'stage' => ['required', 'string', 'max:64'],
            'detail' => ['nullable', 'string', 'max:500'],
            'payment_reference' => ['nullable', 'string', 'max:64'],
            'channel' => ['nullable', 'string', 'max:20'],
        ]);

        $participant = RetreatParticipant::query()->findOrFail((int) $validated['participant_id']);

        $meta = array_filter([
            'payment_reference' => $validated['payment_reference'] ?? null,
            'channel' => $validated['channel'] ?? null,
        ]);

        $this->inscriptionFunnel->record(
            $participant,
            $validated['stage'],
            $validated['detail'] ?? null,
            $meta
        );

        return response()->json(['ok' => true]);
    }

    public function participantStatus(RetreatParticipant $participant): JsonResponse
    {
        $participant->load(['payments.event']);

        $payment = $participant->payments->first();

        return response()->json([
            'data' => [
                'participant_id' => $participant->id,
                'download_token' => $participant->download_token,
                'verification_url' => $this->participantAccessAbsoluteUrl($participant),
                'justificatif_url' => $this->participantJustificatifAbsoluteUrl($participant),
                'billet_url' => $this->participantBilletAbsoluteUrl($participant),
                'paiement_valide' => $participant->paiement_valide,
                'registration_status' => $participant->registration_status,
                'badge_view' => $this->resolveBadgeView($participant, $payment),
                'payment' => $payment ? [
                    'channel' => $payment->channel,
                    'etat' => $payment->etat,
                    'reference' => $payment->reference,
                    'amount_expected' => $payment->amount_expected,
                    'amount_paid' => $payment->amount_paid,
                    'currency' => $payment->currency,
                    'paid_at' => $payment->paid_at?->toISOString(),
                ] : null,
            ],
        ]);
    }

    public function initMobilePayment(Request $request, RetreatParticipant $participant): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'flexpay_type' => ['required', 'string', 'max:5'],
        ]);

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['message' => 'Événement introuvable.'], 422);
        }

        if ($response = $this->assertParticipantMatchesEvent($participant, $event)) {
            return $response;
        }

        $payment = $this->firstOrCreatePayment($participant, $event, 'mobile_money');
        $normalized = $this->normalizeCdMobileMoneyMsisdn($validated['phone']);

        if (strlen($normalized) !== 12 || ! str_starts_with($normalized, '243')) {
            return response()->json([
                'message' => 'Numéro mobile invalide : saisissez 12 chiffres commençant par 243 (sans « + » ni « 0 » initial). Exemple : 24389 123 45 67.',
            ], 422);
        }

        if (! $this->msisdnMatchesFlexpayMobileType($validated['flexpay_type'], $normalized)) {
            return response()->json([
                'message' => 'Ce numéro ne correspond pas au format habituel du réseau sélectionné. Vérifiez l’opérateur choisi puis le numéro saisi.',
            ], 422);
        }

        $result = $this->flexPayMobile->initiateMobilePayment(
            $payment->reference,
            $payment->amount_expected,
            $payment->currency,
            $normalized,
            $validated['flexpay_type']
        );

        $this->logPaymentTransaction($payment, 'mobile_initiation', $validated, $result);

        if (! ($result['reponse'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'Impossible de joindre le service de paiement mobile. Réessayez ou changez de moyen de paiement.',
                'detail' => $result['raw'] ?? null,
            ], 422);
        }

        $payment->update([
            'phone' => $normalized,
            'provider_reference' => $result['orderNumber'] ?? $payment->provider_reference,
            'provider_message' => $result['message'] ?? null,
            'etat' => 'en_cours',
            'channel' => 'mobile_money',
        ]);

        $this->inscriptionFunnel->record(
            $participant,
            RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_INITIATED,
            'Demande Mobile Money transmise — en attente sur le téléphone.',
            ['payment_reference' => $payment->reference, 'channel' => 'mobile_money']
        );

        return response()->json([
            'message' => $result['message'] ?? 'Validez le paiement sur votre téléphone.',
            'data' => [
                'reference' => $payment->reference,
                'order_number' => $result['orderNumber'] ?? null,
            ],
        ]);
    }

    public function initCardPayment(Request $request, RetreatParticipant $participant): JsonResponse
    {
        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['message' => 'Événement introuvable.'], 422);
        }

        if ($response = $this->assertParticipantMatchesEvent($participant, $event)) {
            return $response;
        }

        $external = config('retraite.card_external_form_url');
        if (filled($external)) {
            $payment = $this->firstOrCreatePayment($participant, $event, 'card');

            $this->inscriptionFunnel->record(
                $participant,
                RetreatInscriptionFunnelService::STAGE_PAYMENT_CARD_INITIATED,
                'Redirection vers le formulaire carte externe.',
                ['payment_reference' => $payment->reference, 'channel' => 'card']
            );

            return response()->json([
                'data' => [
                    'mode' => 'external_form',
                    'redirect_url' => $external.'?'.http_build_query([
                        'participant_id' => $participant->id,
                        'reference' => $payment->reference,
                        'amount' => $payment->amount_expected,
                        'currency' => $payment->currency,
                        'email' => $participant->email,
                    ]),
                ],
            ]);
        }

        $payment = $this->firstOrCreatePayment($participant, $event, 'card');
        $description = 'Retraite — '.$event->name;

        $result = $this->flexPayCard->initiateCardPayment(
            $payment->amount_expected,
            $payment->currency,
            $payment->reference,
            $description
        );

        $this->logPaymentTransaction($payment, 'card_initiation', [
            'reference' => $payment->reference,
        ], $result);

        if (! ($result['rep'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'Impossible d’initier le paiement par carte.',
            ], 422);
        }

        $payment->update([
            'provider_reference' => $result['orderNumber'] ?? $payment->provider_reference,
            'etat' => 'en_cours',
            'channel' => 'card',
        ]);

        $this->inscriptionFunnel->record(
            $participant,
            RetreatInscriptionFunnelService::STAGE_PAYMENT_CARD_INITIATED,
            'Redirection FlexPay carte.',
            ['payment_reference' => $payment->reference, 'channel' => 'card']
        );

        return response()->json([
            'data' => [
                'mode' => 'flexpay_gateway',
                'redirect_url' => $result['url'] ?? null,
                'reference' => $payment->reference,
            ],
        ]);
    }

    public function submitCashPayment(Request $request, RetreatParticipant $participant): JsonResponse
    {
        $request->validate([
            'proof' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['message' => 'Événement introuvable.'], 422);
        }

        if ($response = $this->assertParticipantMatchesEvent($participant, $event)) {
            return $response;
        }

        $path = app(StoragePathService::class)->storeUploadedFile(
            $request->file('proof'),
            StoragePath::RETREAT_INSCRIPTION_PROOFS
        );

        $payment = $this->firstOrCreatePayment($participant, $event, 'cash');
        $payment->update([
            'etat' => 'en_cours',
            'channel' => 'cash',
            'provider_message' => 'Preuve téléversée ; en attente de validation.',
        ]);

        $participant->update([
            'preuve_paiement' => $path,
            'paiement_valide' => false,
            'registration_status' => 'pending',
        ]);

        $this->logPaymentTransaction($payment, 'cash_proof_upload', ['path' => $path], ['ok' => true]);

        try {
            $this->cashPaymentAdminNotifier->notify($participant->fresh(), $payment->fresh(), $event);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->inscriptionFunnel->record(
            $participant->fresh(),
            RetreatInscriptionFunnelService::STAGE_PAYMENT_CASH_PROOF,
            'Preuve espèces téléversée — validation admin requise.',
            ['payment_reference' => $payment->reference, 'channel' => 'cash']
        );

        return response()->json([
            'message' => 'Preuve enregistrée. Après validation par l’équipe, vous recevrez un e-mail avec la confirmation et les prochaines étapes.',
            'data' => [
                'participant_id' => $participant->id,
            ],
        ]);
    }

    public function checkPayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => ['required', 'string', 'max:64'],
        ]);

        $payment = RetreatPayment::query()->where('reference', $request->string('reference'))->first();
        if (! $payment) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        if ($payment->etat === 'payee' || (bool) $payment->participant?->paiement_valide) {
            $payment->participant?->refresh();
            if ($payment->participant) {
                $this->inscriptionFunnel->record(
                    $payment->participant,
                    RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_CONFIRMED,
                    'Paiement déjà confirmé (consultation statut).',
                    ['payment_reference' => $payment->reference, 'channel' => $payment->channel]
                );
            }

            return response()->json([
                'data' => [
                    'statut_code' => 0,
                    'message' => 'Paiement déjà confirmé.',
                    'payee' => true,
                    'en_cours' => false,
                    'participant_id' => $payment->participant_id,
                    'paiement_valide' => true,
                    'payment_etat' => 'payee',
                    'badge_view' => $this->resolveBadgeView($payment->participant, $payment),
                ],
            ]);
        }

        $providerLookupReference = $payment->provider_reference ?: $payment->reference;
        $check = $this->flexPayMobile->checkTransaction($providerLookupReference);
        $this->logPaymentTransaction($payment, 'polling', [
            'reference' => $payment->reference,
            'provider_reference' => $providerLookupReference,
        ], $check);

        if (! ($check['ok'] ?? false)) {
            return response()->json(['message' => $check['error'] ?? 'Erreur vérification.'], 500);
        }

        $payload = $check['payload'] ?? [];
        $mergedPayload = is_array($payload) && isset($payload['data']) && is_array($payload['data'])
            ? array_merge($payload, $payload['data'])
            : $payload;

        $statusRaw = $this->extractFlexPayMobileTransactionStatus(is_array($mergedPayload) ? $mergedPayload : []);

        /*
         * Garde-fou : (int) null === 0 et (int) 'pending' === 0 en PHP → ne jamais traiter comme « payé » sans entier explicite.
         */
        if ($statusRaw === null || $statusRaw === '' || ! is_numeric($statusRaw)) {
            return response()->json([
                'data' => [
                    'statut_code' => null,
                    'message' => data_get(is_array($mergedPayload) ? $mergedPayload : [], 'message', 'Statut de transaction indisponible ou non numérique — le paiement n’est pas confirmé.'),
                    'payee' => false,
                    'en_cours' => $payment->etat === 'en_cours',
                    'participant_id' => $payment->participant_id,
                    'paiement_valide' => (bool) $payment->participant?->paiement_valide,
                    'payment_etat' => $payment->etat,
                    'badge_view' => $this->resolveBadgeView($payment->participant, $payment),
                ],
            ]);
        }

        $status = (int) $statusRaw;

        switch ($status) {
            case 0:
                $this->paymentCompletion->markElectronicPaid($payment, 'FlexPay confirmé via polling.');
                $message = 'Paiement confirmé.';
                if ($payment->participant) {
                    $this->inscriptionFunnel->record(
                        $payment->participant,
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_CONFIRMED,
                        'Encaissement confirmé par l’opérateur.',
                        ['payment_reference' => $payment->reference, 'channel' => $payment->channel]
                    );
                }
                break;
            case 1:
                $payment->update(['etat' => 'annulee']);
                $message = 'Paiement annulé.';
                if ($payment->participant) {
                    $this->inscriptionFunnel->record(
                        $payment->participant,
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_CANCELLED,
                        'Transaction annulée côté opérateur.',
                        ['payment_reference' => $payment->reference, 'channel' => $payment->channel]
                    );
                }
                break;
            case 2:
                $payment->update(['etat' => 'en_cours']);
                $message = 'En attente de paiement.';
                if ($payment->participant) {
                    $this->inscriptionFunnel->record(
                        $payment->participant,
                        RetreatInscriptionFunnelService::STAGE_PAYMENT_MOBILE_POLLING,
                        'Paiement toujours en attente chez l’opérateur.',
                        ['payment_reference' => $payment->reference, 'channel' => $payment->channel]
                    );
                }
                break;
            default:
                return response()->json([
                    'data' => [
                        'statut_code' => $status,
                        'message' => data_get(is_array($mergedPayload) ? $mergedPayload : [], 'message', 'Statut inconnu'),
                        'payee' => false,
                        'en_cours' => false,
                        'participant_id' => $payment->participant_id,
                        'paiement_valide' => (bool) $payment->participant?->paiement_valide,
                        'payment_etat' => $payment->fresh()->etat,
                        'badge_view' => $this->resolveBadgeView($payment->participant, $payment->fresh()),
                    ],
                ]);
        }

        $payment->refresh();
        $payment->participant?->refresh();

        return response()->json([
            'data' => [
                'statut_code' => $status,
                'message' => $message,
                'payee' => $payment->etat === 'payee',
                'en_cours' => $payment->etat === 'en_cours',
                'participant_id' => $payment->participant_id,
                'paiement_valide' => (bool) $payment->participant?->paiement_valide,
                'payment_etat' => $payment->etat,
                'badge_view' => $this->resolveBadgeView($payment->participant, $payment),
            ],
        ]);
    }

    public function flexpayWebhook(Request $request): JsonResponse
    {
        // Point d’entrée pour callbacks FlexPay (journalisation uniquement pour l’instant).
        Log::channel('daily')->info('FlexPay webhook inscription', ['body' => $request->all()]);

        return response()->json(['ok' => true]);
    }

    protected function validateRegistration(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'postnom' => ['nullable', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', 'string', 'max:10'],
            'date_naissance' => ['required', 'date', 'before_or_equal:'.now()->subYears(15)->toDateString()],
            'role' => ['required', 'string', 'max:80'],
            'role_autre' => ['nullable', 'string', 'max:255'],
            'indicatif' => ['required', 'string', 'max:10'],
            'telephone' => ['required', 'string', 'max:30'],
            'tel_urgence' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'same_family_emergency_confirm' => ['sometimes', 'boolean'],
            'email' => ['required', 'email', 'max:254'],
            'adresse' => ['required', 'string', 'max:255'],
            'commune' => ['required', 'string', 'max:120'],
            'ville' => ['required', 'string', 'max:120'],
            'eglise' => ['required', 'string', 'max:200'],
            'departement' => ['nullable', 'string', 'max:150'],
            'no_departement' => ['nullable', 'boolean'],
            'hebergement' => ['nullable', 'string', 'in:interne,externe'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'photo' => ['required', 'image', 'max:6144'],
            'event_id' => ['nullable', 'exists:events_event,id'],
            'accepted_policy_ids' => ['nullable', 'array'],
            'accepted_policy_ids.*' => ['integer', 'exists:retreat_policies,id'],
            'parent_group_mode' => ['nullable', 'boolean'],
            'parent_contact_email' => ['nullable', 'email', 'max:254'],
            'parent_contact_phone' => ['nullable', 'string', 'max:30'],
            'parent_full_name' => ['nullable', 'string', 'max:150'],
            'parent_verified_token' => ['nullable', 'string', 'max:120'],
        ], [
            'date_naissance.before_or_equal' => 'Âge minimum requis : 15 ans.',
            'commune.required' => 'Le champ commune est obligatoire.',
            'adresse.required' => 'Le champ adresse est obligatoire.',
            'photo.required' => 'La photo est obligatoire pour poursuivre.',
            'photo.image' => 'Le fichier photo doit être une image valide.',
            'parent_contact_email.email' => 'Adresse e-mail parent/tuteur invalide.',
        ]);
    }

    protected function parentOtpCacheKey(string $verificationId): string
    {
        return 'retreat_parent_otp:'.$verificationId;
    }

    protected function parentVerifiedCacheKey(string $token): string
    {
        return 'retreat_parent_verified:'.$token;
    }

    protected function parentOtpChannelForEvent(?ChurchEvent $event): string
    {
        if (
            $event &&
            $event->access_auth_mode === EventAccessAuthMode::Otp &&
            $event->access_otp_channel === EventAccessOtpChannel::Sms
        ) {
            return 'sms';
        }

        return 'email';
    }

    protected function parentContactsAreVerified(string $token, string $email, string $phone): bool
    {
        $payload = Cache::get($this->parentVerifiedCacheKey($token));
        if (! is_array($payload)) {
            return false;
        }

        $emailMatches = Str::lower((string) ($payload['email'] ?? '')) === Str::lower($email);
        $phoneMatches = (string) ($payload['phone'] ?? '') === $phone;

        return match (($payload['channel'] ?? 'email')) {
            'sms' => $phoneMatches,
            default => $emailMatches,
        };
    }

    protected function sendParentContactOtpSms(string $phone, string $otp): void
    {
        $message = "Code OTP parent/tuteur CMP: {$otp}. Valable ".self::PARENT_CONTACT_OTP_TTL_MINUTES.' minutes.';
        $this->keccelSms->send($phone, $message, 'parent_contact_otp');
    }

    protected function resolveFamilyGroupIdFromVerifiedParentContacts(
        int $eventId,
        ?string $existingFamilyGroupId,
        string $channel,
        ?string $parentEmail,
        ?string $parentPhone,
        ?string $parentFullName
    ): ?string {
        if ($existingFamilyGroupId) {
            return $existingFamilyGroupId;
        }

        if (($channel === 'sms' && ! $parentPhone) || ($channel !== 'sms' && ! $parentEmail)) {
            return null;
        }

        $query = RetreatParticipant::query()
            ->where('event_id', $eventId)
            ->where('is_active', true);

        $contactHash = $this->familyContactHash($channel, $parentEmail, $parentPhone);

        $query->where(function ($query) use ($channel, $parentEmail, $parentPhone, $parentFullName, $contactHash): void {
            $query->where('family_contact_hash', $contactHash);

            if ($channel === 'sms') {
                $query->orWhere('telephone', $parentPhone)
                    ->orWhere('guardian_phone', $parentPhone);
            } else {
                $query->orWhereRaw('LOWER(TRIM(email)) = ?', [Str::lower((string) $parentEmail)]);
            }

            if (filled($parentFullName)) {
                $query->orWhereRaw('LOWER(TRIM(family_group_name)) = ?', [Str::lower((string) $parentFullName)])
                    ->orWhereRaw('LOWER(TRIM(guardian_name)) = ?', [Str::lower((string) $parentFullName)]);
            }
        });

        $ref = $query
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (! $ref) {
            return Str::uuid()->toString();
        }

        if (filled($ref->family_group_id)) {
            $ref->forceFill([
                'family_group_name' => $ref->family_group_name ?: $parentFullName,
                'family_contact_hash' => $ref->family_contact_hash ?: $contactHash,
            ])->save();

            return (string) $ref->family_group_id;
        }

        $group = Str::uuid()->toString();
        $ref->update([
            'family_group_id' => $group,
            'family_group_name' => $ref->family_group_name ?: $parentFullName,
            'family_contact_hash' => $ref->family_contact_hash ?: $contactHash,
        ]);

        return $group;
    }

    protected function familyContactHash(string $channel, ?string $parentEmail, ?string $parentPhone): string
    {
        $contact = $channel === 'sms'
            ? (string) $parentPhone
            : Str::lower(trim((string) $parentEmail));

        return hash('sha256', $channel.'|'.$contact);
    }

    protected function resolveEvent(Request $request): ?ChurchEvent
    {
        $query = ChurchEvent::query()
            ->openForPublicRegistration()
            ->with(['afficheMedia', 'retreatDetail']);

        if ($request->filled('event_id')) {
            return $query->clone()
                ->whereKey($request->integer('event_id'))
                ->first();
        }

        return $query->orderByDesc('start_at')->orderByDesc('id')->first();
    }

    protected function publicEventPayload(ChurchEvent $event): array
    {
        $event->loadMissing(['afficheMedia', 'retreatDetail']);

        $detail = $event->retreatDetail;
        $capacity = $event->capacity ? (int) $event->capacity : null;
        $registeredParticipants = $this->retreatParticipantCountForEvent($event->id);
        $registeredWorkers = $this->retreatWorkerCountForEvent($event->id);
        $remaining = ($capacity !== null && $capacity > 0) ? max(0, $capacity - $registeredParticipants) : null;

        return [
            'id' => $event->id,
            'name' => $event->name,
            'type' => $event->type,
            'start_at' => $event->start_at?->toISOString(),
            'end_at' => $event->end_at?->toISOString(),
            'location' => $event->location,
            'price_to_pay' => $event->price_to_pay,
            'currency' => $event->currency,
            'affiche_url' => $this->resolveAfficheUrl($event),
            'capacity' => $capacity,
            'registered_count' => $registeredParticipants,
            'worker_registered_count' => $registeredWorkers,
            'places_remaining' => $remaining,
            'is_sold_out' => $remaining !== null && $remaining === 0,
            'registration_open' => $event->isOpenForPublicRetreatRegistration(),
            'registration_closes_at' => $event->end_at?->toISOString(),
            'places_message' => $remaining === null
                ? null
                : ($remaining === 0
                    ? 'Toutes les places sont occupées pour cette retraite.'
                    : "Il reste {$remaining} place".($remaining > 1 ? 's' : '').($capacity ? " sur {$capacity}." : '.')),
            'retreat_detail' => $detail ? [
                'theme' => $detail->theme,
                'speaker' => $detail->speaker,
                'notes' => $detail->notes,
            ] : null,
            'step_context' => [
                'identity' => $detail && filled($detail->theme)
                    ? "Cette inscription concerne la retraite « {$detail->theme} » (intervenant principal : {$detail->speaker}). Merci de renseigner votre identité comme sur une pièce officielle."
                    : 'Votre identité servira à votre badge et à votre accueil lors de la grande retraite des jeunes CMP.',
                'contact' => $detail && filled($detail->notes)
                    ? "Nous vous contacterons aux coordonnées ci-dessous pour les confirmations. Rappel organisation : {$detail->notes}"
                    : 'Nous utiliserons votre téléphone et votre e-mail pour le suivi de l’inscription et l’envoi des confirmations.',
                'participation' => 'Indiquez vos préférences (église locale, hébergement) ; l’équipe d’organisation finalisera selon les disponibilités.',
                'recap' => 'Contrôlez vos réponses. Vous pourrez encore revenir en arrière pour corriger avant la phase de paiement.',
                'payment' => ($remaining !== null && $remaining <= 5 && $remaining > 0)
                    ? 'Places limitées : finalisez le paiement dès confirmation pour garantir votre participation.'
                    : 'Le montant défini ci-dessous valide officiellement votre inscription une fois encaissé.',
            ],
            'flexpay_mobile_providers' => config('retraite.flexpay_mobile_providers', []),
            'card_payment' => [
                'mode' => filled(config('retraite.card_external_form_url')) ? 'external' : 'flexpay_redirect',
                'external_form_url' => config('retraite.card_external_form_url'),
            ],
            'participant_notifications' => $this->participantNotificationPayload($event),
        ];
    }

    /**
     * Libellés lisibles pour le portail (mot de passe vs OTP) et le canal de réception des codes.
     */
    protected function participantNotificationPayload(ChurchEvent $event): array
    {
        $mode = $event->access_auth_mode ?? EventAccessAuthMode::Password;
        $channel = $event->access_otp_channel;

        $lines = [];

        if ($mode === EventAccessAuthMode::Otp) {
            $chanLabel = $channel?->label() ?? 'SMS ou e-mail';
            $lines[] = "Portail de l’événement : authentification par code à usage unique (OTP), envoyé par {$chanLabel}.";
        } else {
            $lines[] = 'Portail de l’événement : accès sécurisé par mot de passe.';
        }

        $lines[] = 'Confirmation d’inscription et billet : envoyés en priorité à l’adresse e-mail indiquée.';

        if ($mode === EventAccessAuthMode::Otp && $channel === EventAccessOtpChannel::Sms) {
            $lines[] = 'Pour les étapes OTP, surveillez également les SMS sur le numéro principal du formulaire.';
        }
        if ($mode === EventAccessAuthMode::Otp && $channel === EventAccessOtpChannel::Email) {
            $lines[] = 'Les codes OTP sont envoyés par e-mail : vérifiez aussi les dossiers spam ou promotions.';
        }

        return [
            'summary' => implode(' ', $lines),
            'lines' => $lines,
            'access_auth_mode' => $mode->value,
            'access_auth_mode_label' => $mode->label(),
            'access_otp_channel' => $channel?->value,
            'access_otp_channel_label' => $channel?->label(),
        ];
    }

    protected function resolveAfficheUrl(ChurchEvent $event): ?string
    {
        $event->loadMissing('afficheMedia');
        $file = $event->afficheMedia;
        if ($file && method_exists($file, 'getUrl')) {
            $url = $file->getUrl();
            if ($url) {
                return $url;
            }
        }

        $legacy = $event->affiche;
        if (blank($legacy)) {
            return null;
        }
        if (Str::startsWith($legacy, ['http://', 'https://'])) {
            return $legacy;
        }

        return app(PublicStorageUrl::class)->fromPath($legacy);
    }

    protected function participantIdentityExists(string $nom, string $prenom, ?string $postnom, ?int $eventId = null): bool
    {
        $q = RetreatParticipant::query()
            ->where('nom', $nom)
            ->where('prenom', $prenom);

        if ($eventId !== null) {
            $q->where('event_id', $eventId);
        }

        if (filled($postnom)) {
            $q->where('postnom', $postnom);
        } else {
            $q->whereNull('postnom');
        }

        return $q->exists();
    }

    protected function participantMainPhoneExists(string $mainCanon, ?int $eventId): bool
    {
        if ($mainCanon === '' || $eventId === null) {
            return false;
        }

        return RetreatParticipant::query()
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where('telephone', $mainCanon)
            ->exists();
    }

    /** Mobile Money RDC : uniquement chiffres, préfixe 243 (12 caractères), sans « + » ni 0 national initial. */
    protected function normalizeCdMobileMoneyMsisdn(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', trim($raw));
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '243'.substr($digits, 1);
        }
        if (! str_starts_with($digits, '243')) {
            $digits = '243'.ltrim($digits, '0');
        }

        return $digits;
    }

    protected function msisdnMatchesFlexpayMobileType(string $flexpayType, string $normalizedMsisdn): bool
    {
        $providers = config('retraite.flexpay_mobile_providers', []);
        foreach ($providers as $provider) {
            if (! is_array($provider)) {
                continue;
            }
            $type = isset($provider['type']) ? (string) $provider['type'] : '';
            if ($type !== (string) $flexpayType) {
                continue;
            }
            $regex = isset($provider['msisdn_regex']) ? trim((string) $provider['msisdn_regex']) : '';
            if ($regex === '') {
                return preg_match('/^243\d{9}$/', $normalizedMsisdn) === 1;
            }

            return preg_match('#'.$regex.'#', $normalizedMsisdn) === 1;
        }

        return preg_match('/^243\d{9}$/', $normalizedMsisdn) === 1;
    }

    protected function isDuplicateKeyException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;

        return $sqlState === '23000' || in_array($code, [1062, 19], true);
    }

    /**
     * Politiques lecteur public (participant) applicables pour un événement.
     *
     * @return Builder<RetreatPolicy>
     */
    protected function publicPoliciesForEventQuery(ChurchEvent $event): Builder
    {
        return RetreatPolicy::query()
            ->where('is_active', true)
            ->whereIn('target_audience', ['all', 'participant'])
            ->where(function ($query) use ($event): void {
                $query->whereNull('event_id')->orWhere('event_id', $event->id);
            })
            ->where(function ($query): void {
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('effective_to')->orWhere('effective_to', '>=', now());
            });
    }

    protected function validatePolicyAcceptance(array $acceptedPolicyIds, ChurchEvent $event): ?JsonResponse
    {
        $accepted = collect($acceptedPolicyIds)->map(fn ($id): int => (int) $id)->unique();
        $allowed = $this->publicPoliciesForEventQuery($event)->pluck('id');

        if ($accepted->diff($allowed)->isNotEmpty()) {
            return response()->json([
                'message' => 'Une ou plusieurs politiques ne sont pas valides pour cet événement.',
            ], 422);
        }

        $mandatoryIds = $this->publicPoliciesForEventQuery($event)
            ->where('is_mandatory', true)
            ->pluck('id');

        if ($mandatoryIds->isNotEmpty() && $mandatoryIds->diff($accepted)->isNotEmpty()) {
            return response()->json([
                'message' => 'Vous devez reconnaître la lecture et accepter toutes les politiques obligatoires.',
            ], 422);
        }

        return null;
    }

    protected function retreatRegistrationCountForEvent(int $eventId): int
    {
        return $this->retreatParticipantCountForEvent($eventId) + $this->retreatWorkerCountForEvent($eventId);
    }

    /**
     * Inscriptions participants (hors ouvriers) pour le quota de places.
     */
    protected function retreatParticipantCountForEvent(int $eventId): int
    {
        return (int) RetreatParticipant::query()
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('role_participant', 'participant')
                    ->orWhereNull('role_participant')
                    ->orWhere('role_participant', '');
            })
            ->count();
    }

    /**
     * Inscriptions ouvriers (hors quota participants).
     */
    protected function retreatWorkerCountForEvent(int $eventId): int
    {
        return (int) RetreatParticipant::query()
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where('role_participant', 'ouvrier')
            ->count();
    }

    protected function isOuvrierRegistration(string $role): bool
    {
        return $this->normalizeRole($role, null) === 'ouvrier';
    }

    protected function eventRegistrationClosedMessage(ChurchEvent $event): ?string
    {
        $capacity = $event->capacity ? (int) $event->capacity : null;
        if (! $capacity || $capacity < 1) {
            return null;
        }

        $count = $this->retreatParticipantCountForEvent($event->id);
        if ($count >= $capacity) {
            return 'Le nombre maximal de participants pour cette retraite est atteint.';
        }

        return null;
    }

    protected function assertParticipantMatchesEvent(RetreatParticipant $participant, ChurchEvent $event): ?JsonResponse
    {
        if ($participant->event_id === null || (int) $participant->event_id !== (int) $event->id) {
            return response()->json([
                'message' => 'Ce participant n’est pas rattaché à l’événement actif.',
            ], 422);
        }

        return null;
    }

    protected function isWorkerUser(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->hasAnyRole(['ouvrier', 'worker', 'staff'])) {
            return true;
        }

        foreach (['fonction_metier', 'role_jeunesse'] as $column) {
            $value = Str::lower(trim((string) $user->getAttribute($column)));

            if (
                in_array($value, [
                    'ouvrier',
                    'worker',
                    'staff',
                    'encadreur',
                    'responsable_chambre',
                    'responsable_atelier',
                ], true) ||
                str_contains($value, 'ouvrier')
            ) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeSexe(string $sexe): string
    {
        $s = strtoupper(substr($sexe, 0, 1));

        return match ($s) {
            'M' => 'homme',
            'F' => 'femme',
            default => Str::lower($sexe) === 'homme' || Str::lower($sexe) === 'femme'
                ? Str::lower($sexe)
                : 'homme',
        };
    }

    protected function normalizeRole(string $role, ?string $autre): string
    {
        $key = Str::lower(trim($role));

        if (($key === 'autre' || $key === 'other') && filled($autre)) {
            return Str::limit(Str::lower(trim((string) $autre)), 20, '');
        }

        if (str_contains($key, 'ouvrier')) {
            return 'ouvrier';
        }

        if (str_contains($key, 'participant')) {
            return 'participant';
        }

        return Str::limit(preg_replace('/[^a-z0-9_\-]/i', '_', $key), 20, '');
    }

    /**
     * @param  array<int, array{participant_id: int, masked_label: string, motives: array<int, string>}>  $bucket
     */
    protected function accumulateFamilyHintMatch(array &$bucket, RetreatParticipant $p, string $motive): void
    {
        $id = (int) $p->id;
        if (! isset($bucket[$id])) {
            $bucket[$id] = [
                'participant_id' => $id,
                'masked_label' => $this->maskParticipantNameForFamilyHint($p),
                'motives' => [],
            ];
        }
        if (! in_array($motive, $bucket[$id]['motives'], true)) {
            $bucket[$id]['motives'][] = $motive;
        }
    }

    protected function maskParticipantNameForFamilyHint(RetreatParticipant $p): string
    {
        $prenom = trim((string) ($p->prenom ?? ''));
        $nom = trim((string) ($p->nom ?? ''));
        $pn = trim((string) ($p->postnom ?? ''));

        $nomInit = $nom !== '' ? Str::substr($nom, 0, 1).'.' : '';
        $pnInit = $pn !== '' ? Str::substr($pn, 0, 1).'.' : '';

        $parts = array_filter([$prenom, $nomInit, $pnInit]);
        $label = implode(' ', $parts);

        return $label !== '' ? $label : 'Inscription #'.(int) $p->id;
    }

    protected function normalizePhone(string $indicatif, string $telephone): string
    {
        $ind = ltrim(trim($indicatif), '+');
        $num = preg_replace('/\D+/', '', $telephone);

        return $ind.$num;
    }

    /**
     * Numérique unique (sans +) pour le tél. d’urgence : international si préfixé +, sinon même indicatif que le principal.
     */
    protected function canonicalEmergencyPhoneDigits(string $raw, string $indicatifPrincipal): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $compact = preg_replace('/\s+/u', '', $raw);
        $compact = $compact !== null ? $compact : '';

        if (str_starts_with($compact, '+')) {
            return preg_replace('/\D+/', '', $compact);
        }

        return $this->normalizePhone($indicatifPrincipal, $compact);
    }

    /**
     * Clé de comparaison pour regrouper par nom de tuteur (min. 3 caractères utiles).
     */
    protected function normalizeGuardianNameKey(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $n = mb_strtolower(trim($name), 'UTF-8');
        $n = (string) preg_replace('/\s+/u', ' ', $n);

        if ($n === '' || mb_strlen($n, 'UTF-8') < 3) {
            return null;
        }

        return $n;
    }

    /**
     * Premier inscrit (référence) permettant de rattacher la nouvelle inscription à un groupe foyer.
     */
    protected function findFirstFamilyGroupingReference(
        int $eventId,
        string $mainCanon,
        ?string $tutorCanon,
        ?string $guardianCanon,
        ?string $guardianNameNorm,
    ): ?RetreatParticipant {
        $digits = array_values(array_unique(array_filter([$tutorCanon, $guardianCanon])));
        foreach ($digits as $c) {
            if ($c === null || $c === '' || $c === $mainCanon) {
                continue;
            }
            $hit = RetreatParticipant::query()
                ->where('event_id', $eventId)
                ->where('is_active', true)
                ->where('telephone', $c)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if ($hit instanceof RetreatParticipant) {
                return $hit;
            }
        }

        if ($guardianCanon !== null && strlen($guardianCanon) >= 10 && $guardianCanon !== $mainCanon) {
            $g = Str::limit($guardianCanon, 20, '');
            $hit = RetreatParticipant::query()
                ->where('event_id', $eventId)
                ->where('is_active', true)
                ->where('guardian_phone', $g)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if ($hit instanceof RetreatParticipant) {
                return $hit;
            }
        }

        $nameKey = $this->normalizeGuardianNameKey($guardianNameNorm);
        if ($nameKey === null) {
            return null;
        }

        /** @var \Illuminate\Support\LazyCollection<int, RetreatParticipant> $stream */
        $stream = RetreatParticipant::query()
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->whereNotNull('guardian_name')
            ->orderBy('id')
            ->lockForUpdate()
            ->cursor();

        foreach ($stream as $row) {
            if ($this->normalizeGuardianNameKey($row->guardian_name) === $nameKey) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Regroupement foyer : uniquement si le participant confirme explicitement, et qu’un lien est trouvé
     * (tél. urgence / tél. tuteur ↔ portable principal ou tél. tuteur ou nom du tuteur déjà présents sur une autre fiche).
     */
    protected function resolveFamilyGroupIdFromLinkedPhones(
        int $eventId,
        string $mainCanon,
        bool $participantConfirmsSameFamily,
        ?string $tutorCanon,
        ?string $guardianCanon,
        ?string $guardianNameNorm,
    ): ?string {
        if (! $participantConfirmsSameFamily) {
            return null;
        }

        $ref = $this->findFirstFamilyGroupingReference(
            $eventId,
            $mainCanon,
            $tutorCanon,
            $guardianCanon,
            $guardianNameNorm,
        );

        if (! $ref instanceof RetreatParticipant) {
            return null;
        }

        if (filled($ref->family_group_id)) {
            return $ref->family_group_id;
        }

        $uid = Str::uuid()->toString();
        RetreatParticipant::query()->whereKey($ref->id)->update(['family_group_id' => $uid]);

        return $uid;
    }

    protected function firstOrCreatePayment(RetreatParticipant $participant, ChurchEvent $event, string $channel): RetreatPayment
    {
        $payment = RetreatPayment::query()->firstOrNew([
            'participant_id' => $participant->id,
            'event_id' => $event->id,
        ]);

        if (! $payment->exists) {
            $payment->reference = 'RET-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
        }

        $attributes = [
            'amount_expected' => $event->price_to_pay ?? 0,
            'currency' => $event->currency ?? 'USD',
            'channel' => $channel,
            'phone' => $participant->telephone,
            'etat' => 'init',
            'access_granted' => false,
            'is_active' => true,
        ];

        if (! $payment->exists) {
            $attributes['amount_paid'] = 0;
        }

        $payment->fill($attributes)->save();

        return $payment->fresh();
    }

    /**
     * Statut numérique FlexPay (0 payé, 1 annulé, 2 en attente) depuis différentes formes de réponses HTTP.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function extractFlexPayMobileTransactionStatus(array $payload): mixed
    {
        $paths = [
            'transaction.status',
            'transaction.TransactionStatus',
            'Transaction.status',
            'status',
            'data.transaction.status',
            'data.status',
            'result.status',
            'payment.status',
        ];

        foreach ($paths as $path) {
            $raw = data_get($payload, $path);
            if ($raw !== null && $raw !== '' && is_numeric($raw)) {
                return $raw;
            }
        }

        $transaction = data_get($payload, 'transaction');
        if (is_array($transaction)) {
            foreach (['status', 'Status', 'transactionStatus'] as $key) {
                if (isset($transaction[$key]) && $transaction[$key] !== '' && is_numeric($transaction[$key])) {
                    return $transaction[$key];
                }
            }
        }

        return null;
    }

    protected function resolveBadgeView(?RetreatParticipant $participant, ?RetreatPayment $payment): string
    {
        if (! $participant) {
            return 'unknown';
        }

        if ($participant->paiement_valide && ! $participant->badge_received) {
            return 'badge_pending';
        }

        $channel = $payment?->channel;

        if ($participant->paiement_valide && in_array($channel, ['mobile_money', 'card'], true)) {
            return 'electronic_success';
        }

        if ($channel === 'cash') {
            return $participant->paiement_valide ? 'cash_validated' : 'cash_pending';
        }

        return 'payment_incomplete';
    }

    protected function logPaymentTransaction(RetreatPayment $payment, string $type, ?array $requestPayload, mixed $responsePayload): void
    {
        try {
            DB::table('retreat_payment_transactions')->insert([
                'payment_id' => $payment->id,
                'transaction_type' => $type,
                'provider_reference' => $payment->provider_reference,
                'request_payload' => $requestPayload ? json_encode($requestPayload) : null,
                'response_payload' => is_array($responsePayload) ? json_encode($responsePayload) : json_encode(['value' => $responsePayload]),
                'message' => null,
                'processed_at' => now(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

}

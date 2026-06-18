<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Models\RetreatVoluntaryDonation;
use App\Services\FlexPay\FlexPayCardService;
use App\Services\FlexPay\FlexPayMobileService;
use App\Services\RegistrationFormConfigService;
use App\Services\RetreatDonation\RetreatSponsorshipVoucherService;
use App\Services\RetreatDonation\RetreatVoluntaryDonationService;
use App\Services\RetreatRegistration\RetreatEventCapacityService;
use App\Services\StoragePathService;
use App\Support\StoragePath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * API publique : dons volontaires retraite et codes parrainage.
 */
class RetreatVoluntaryDonationController extends Controller
{
    public function __construct(
        protected RetreatVoluntaryDonationService $donationService,
        protected RetreatSponsorshipVoucherService $voucherService,
        protected FlexPayMobileService $flexPayMobile,
        protected FlexPayCardService $flexPayCard,
        protected RegistrationFormConfigService $formConfigService,
        protected RetreatEventCapacityService $capacityService,
    ) {}

    /**
     * Contexte retraite pour la page don (prix, devise).
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse
     */
    public function context(Request $request): JsonResponse
    {
        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['message' => 'Aucune retraite active.'], 404);
        }

        return response()->json([
            'data' => array_merge([
                'event_id' => $event->id,
                'event_name' => $event->name,
                'price_to_pay' => (float) ($event->price_to_pay ?? 0),
                'currency' => (string) ($event->currency ?? 'USD'),
                'flexpay_mobile_providers' => $this->formConfigService->resolvedMobileProvidersForEvent($event),
                'card_payment' => [
                    'mode' => filled(config('retraite.card_external_form_url')) ? 'external' : 'flexpay_redirect',
                    'external_form_url' => config('retraite.card_external_form_url'),
                ],
            ], $this->capacityService->snapshot($event)),
        ]);
    }

    /**
     * Enregistre un don en nature.
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse
     */
    public function storeInKind(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donor_name' => ['required', 'string', 'max:150'],
            'donor_phone' => ['nullable', 'string', 'max:30'],
            'donor_email' => ['required', 'email', 'max:254'],
            'in_kind_description' => ['required', 'string', 'max:2000'],
            'donor_message' => ['nullable', 'string', 'max:1000'],
            'event_id' => ['nullable', 'integer', 'exists:events_event,id'],
        ]);

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['message' => 'Aucune retraite active.'], 422);
        }

        $donation = $this->donationService->createInKind($event, $validated);

        return response()->json([
            'message' => 'Merci ! Votre proposition de don en nature a été transmise à l’organisation.',
            'data' => [
                'reference' => $donation->reference,
            ],
        ], 201);
    }

    /**
     * Initialise un don en espèces (avant paiement).
     *
     * @param Request $request Requête HTTP
     * @return JsonResponse
     */
    public function initCash(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'donor_name' => ['required', 'string', 'max:150'],
            'donor_phone' => ['nullable', 'string', 'max:30'],
            'donor_email' => ['required', 'email', 'max:254'],
            'cash_purpose' => ['required', 'in:general,sponsor_youth'],
            'amount' => ['required_if:cash_purpose,general', 'nullable', 'numeric', 'min:1'],
            'youth_slots_count' => ['required_if:cash_purpose,sponsor_youth', 'nullable', 'integer', 'min:1', 'max:50'],
            'donor_message' => ['nullable', 'string', 'max:1000'],
            'event_id' => ['nullable', 'integer', 'exists:events_event,id'],
        ]);

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['message' => 'Aucune retraite active.'], 422);
        }

        try {
            $donation = $this->donationService->createCashPending($event, $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Don préparé. Procédez au paiement.',
            'data' => [
                'donation_id' => $donation->id,
                'reference' => $donation->reference,
                'amount_expected' => (float) $donation->amount_expected,
                'currency' => $donation->currency,
                'cash_purpose' => $donation->cash_purpose,
                'youth_slots_count' => $donation->youth_slots_count,
            ],
        ], 201);
    }

    /**
     * Lance un paiement Mobile Money FlexPay pour un don cash.
     *
     * @param Request $request Requête HTTP
     * @param RetreatVoluntaryDonation $donation Don cible
     * @return JsonResponse
     */
    public function initMobilePayment(Request $request, RetreatVoluntaryDonation $donation): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'provider_code' => ['nullable', 'string', 'max:32'],
        ]);

        if ($donation->donation_kind !== RetreatVoluntaryDonation::KIND_CASH) {
            return response()->json(['message' => 'Ce don ne requiert pas de paiement mobile.'], 422);
        }

        if ($donation->status === RetreatVoluntaryDonation::STATUS_PAID) {
            return response()->json(['message' => 'Ce don est déjà payé.'], 422);
        }

        if ($donation->status === RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED) {
            return response()->json(['message' => 'Preuve cash en attente de validation admin.'], 422);
        }

        $phone = preg_replace('/\D+/', '', $validated['phone']);
        if (str_starts_with($phone, '0')) {
            $phone = '243'.substr($phone, 1);
        }
        if (! str_starts_with($phone, '243')) {
            $phone = '243'.ltrim($phone, '0');
        }

        if (strlen($phone) !== 12) {
            return response()->json([
                'message' => 'Numéro invalide : 12 chiffres commençant par 243.',
            ], 422);
        }

        $result = $this->flexPayMobile->initiateMobilePayment(
            $donation->reference,
            $donation->amount_expected,
            $donation->currency,
            $phone,
            (string) config('retraite.flexpay_mobile_money_api_type', '1')
        );

        if (! ($result['reponse'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'Paiement mobile refusé.',
            ], 422);
        }

        $donation->update([
            'payment_channel' => 'mobile_money',
            'provider_reference' => $result['orderNumber'] ?? $donation->provider_reference,
            'status' => RetreatVoluntaryDonation::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => $result['message'] ?? 'Validez le paiement sur votre téléphone.',
            'data' => [
                'reference' => $donation->reference,
                'order_number' => $result['orderNumber'] ?? null,
            ],
        ]);
    }

    /**
     * Lance un paiement par carte FlexPay pour un don.
     *
     * @param Request $request Requête HTTP
     * @param RetreatVoluntaryDonation $donation Don cible
     * @return JsonResponse
     */
    public function initCardPayment(Request $request, RetreatVoluntaryDonation $donation): JsonResponse
    {
        if ($donation->donation_kind !== RetreatVoluntaryDonation::KIND_CASH) {
            return response()->json(['message' => 'Ce don ne requiert pas de paiement carte.'], 422);
        }

        if (in_array($donation->status, [RetreatVoluntaryDonation::STATUS_PAID, RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED], true)) {
            return response()->json(['message' => 'Ce don ne peut plus être payé par carte.'], 422);
        }

        $external = config('retraite.card_external_form_url');
        if (filled($external)) {
            $donation->update(['payment_channel' => 'card']);

            return response()->json([
                'data' => [
                    'mode' => 'external_form',
                    'redirect_url' => $external.'?'.http_build_query([
                        'donation_id' => $donation->id,
                        'reference' => $donation->reference,
                        'amount' => $donation->amount_expected,
                        'currency' => $donation->currency,
                        'email' => $donation->donor_email,
                    ]),
                ],
            ]);
        }

        $event = $donation->event;
        $description = 'Don retraite — '.($event?->name ?? 'CMP');

        $result = $this->flexPayCard->initiateCardPayment(
            (float) $donation->amount_expected,
            (string) $donation->currency,
            (string) $donation->reference,
            $description
        );

        if (! ($result['rep'] ?? false)) {
            return response()->json([
                'message' => $result['message'] ?? 'Impossible d’initier le paiement par carte.',
            ], 422);
        }

        $donation->update([
            'payment_channel' => 'card',
            'provider_reference' => $result['orderNumber'] ?? $donation->provider_reference,
            'status' => RetreatVoluntaryDonation::STATUS_PENDING,
        ]);

        return response()->json([
            'data' => [
                'mode' => 'flexpay_gateway',
                'redirect_url' => $result['url'] ?? null,
                'reference' => $donation->reference,
            ],
        ]);
    }

    /**
     * Soumet une preuve de paiement cash (validation admin avant codes).
     *
     * @param Request $request Requête avec fichier proof
     * @param RetreatVoluntaryDonation $donation Don cible
     * @return JsonResponse
     */
    public function submitCashPayment(Request $request, RetreatVoluntaryDonation $donation): JsonResponse
    {
        $request->validate([
            'proof' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        if ($donation->donation_kind !== RetreatVoluntaryDonation::KIND_CASH) {
            return response()->json(['message' => 'Ce don ne requiert pas de preuve cash.'], 422);
        }

        if ($donation->status === RetreatVoluntaryDonation::STATUS_PAID) {
            return response()->json(['message' => 'Ce don est déjà payé.'], 422);
        }

        $path = app(StoragePathService::class)->storeUploadedFile(
            $request->file('proof'),
            StoragePath::RETREAT_DONATION_PROOFS
        );

        $donation = $this->donationService->submitCashProof($donation, $path);

        return response()->json([
            'message' => 'Preuve enregistrée. Après validation par l’équipe, vous recevrez un e-mail de confirmation.',
            'data' => [
                'reference' => $donation->reference,
                'status' => $donation->status,
            ],
        ]);
    }

    /**
     * Vérifie le statut d'un paiement don (polling).
     *
     * @param Request $request Requête avec reference
     * @return JsonResponse
     */
    public function checkPayment(Request $request): JsonResponse
    {
        $reference = (string) $request->query('reference', '');
        $donation = RetreatVoluntaryDonation::query()->where('reference', $reference)->first();

        if (! $donation) {
            return response()->json(['message' => 'Don introuvable.'], 404);
        }

        if ($donation->status === RetreatVoluntaryDonation::STATUS_PAID) {
            return response()->json([
                'data' => $this->donationPaidPayload($donation),
            ]);
        }

        if ($donation->status === RetreatVoluntaryDonation::STATUS_CASH_SUBMITTED) {
            return response()->json([
                'data' => [
                    'paid' => false,
                    'cash_pending' => true,
                    'reference' => $donation->reference,
                    'message' => 'Preuve en attente de validation par l’administration.',
                ],
            ]);
        }

        $check = $this->flexPayMobile->checkTransaction($donation->reference);
        $payload = is_array($check['payload'] ?? null) ? $check['payload'] : [];
        $transaction = is_array($payload['transaction'] ?? null) ? $payload['transaction'] : [];
        $status = (string) ($transaction['status'] ?? '');

        if ($status === '0') {
            $amount = (float) ($transaction['amount'] ?? $donation->amount_expected);
            try {
                $donation = $this->donationService->markCashPaid(
                    $donation,
                    $amount,
                    $donation->payment_channel ?? 'mobile_money',
                    $donation->provider_reference
                );
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'data' => $this->donationPaidPayload($donation),
            ]);
        }

        return response()->json([
            'data' => [
                'paid' => false,
                'status' => $status,
                'reference' => $donation->reference,
            ],
        ]);
    }

    /**
     * Validation temps réel d'un code parrainage.
     *
     * @param Request $request code + event_id
     * @return JsonResponse
     */
    public function sponsorshipVoucherHint(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'event_id' => ['nullable', 'integer', 'exists:events_event,id'],
        ]);

        $event = $this->resolveEvent($request);
        if (! $event) {
            return response()->json(['data' => ['eligible' => false, 'valid' => false]]);
        }

        $voucher = $this->voucherService->findRedeemable($validated['code'], $event->id);

        return response()->json([
            'data' => [
                'eligible' => true,
                'valid' => $voucher !== null,
                'hint' => $voucher
                    ? 'Code valide — le paiement sera couvert pour cette inscription.'
                    : 'Code invalide, expiré ou déjà utilisé.',
            ],
        ]);
    }

    /**
     * Applique un code parrainage à un participant inscrit (étape paiement).
     *
     * @param Request $request code
     * @param RetreatParticipant $participant Participant
     * @return JsonResponse
     */
    public function applySponsorshipVoucher(Request $request, RetreatParticipant $participant): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        if ($participant->paiement_valide) {
            return response()->json(['message' => 'Le paiement est déjà validé pour cette inscription.'], 422);
        }

        $voucher = $this->voucherService->findRedeemable($validated['code'], (int) $participant->event_id);
        if (! $voucher) {
            return response()->json([
                'message' => 'Code parrainage invalide, expiré ou déjà utilisé.',
            ], 422);
        }

        try {
            $this->voucherService->redeemForParticipant($voucher, $participant);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Code accepté — inscription couverte par le parrainage. Vous pouvez continuer vers votre billet.',
            'data' => [
                'participant_id' => $participant->id,
                'payment_covered' => true,
            ],
        ]);
    }

    /**
     * @param RetreatVoluntaryDonation $donation Don payé
     * @return array<string, mixed>
     */
    protected function donationPaidPayload(RetreatVoluntaryDonation $donation): array
    {
        $donation->load(['vouchers']);

        return [
            'paid' => true,
            'reference' => $donation->reference,
            'cash_purpose' => $donation->cash_purpose,
        ];
    }

    /**
     * @param Request $request Requête HTTP
     * @return ChurchEvent|null
     */
    protected function resolveEvent(Request $request): ?ChurchEvent
    {
        $query = ChurchEvent::query()
            ->openForPublicRegistration()
            ->with(['retreatDetail']);

        if ($request->filled('event_id')) {
            return $query->clone()
                ->whereKey($request->integer('event_id'))
                ->first();
        }

        return $query->orderByDesc('start_at')->orderByDesc('id')->first();
    }
}

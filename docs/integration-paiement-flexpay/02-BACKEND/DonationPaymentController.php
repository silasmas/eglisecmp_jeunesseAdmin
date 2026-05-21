<?php

/**
 * Contrôleur de paiement pour les DONS
 *
 * Ce fichier gère :
 * - Création d'un don (init)
 * - Initiation du paiement (Mobile Money ou Carte bancaire)
 * - Vérification du statut (polling Mobile Money)
 * - Retour après paiement carte (success/cancel/decline)
 *
 * ADAPTATION : Remplacer Don par votre modèle (Commande, Abonnement, etc.)
 */

namespace App\Http\Controllers;

use App\Models\Don;
use App\Services\FlexPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonationPaymentController extends Controller
{
    protected $flexPayService;

    public function __construct(FlexPayService $flexPayService)
    {
        $this->flexPayService = $flexPayService;
    }

    /**
     * Étape 1 : Créer le don et retourner les infos pour le paiement
     */
    public function initDon(Request $request)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'nom' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'message' => 'nullable|string|max:500',
        ]);

        $reference = generateUniqueReference(Don::class);

        $don = Don::create([
            'reference' => $reference,
            'montant' => $validated['montant'],
            'currency' => 'USD', // ou depuis $request
            'nom' => $validated['nom'] ?? null,
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'] ?? null,
            'user_id' => Auth::id(),
            'etat' => 'init',
        ]);

        return response()->json([
            'success' => true,
            'id' => $don->id,
            'reference' => $don->reference,
            'total' => $don->montant,
            'currency' => $don->currency,
        ]);
    }

    /**
     * Étape 2 : Lancer le paiement (Mobile Money ou Carte)
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'channel' => 'required|in:mobile_money,card',
            'phone' => 'required_if:channel,mobile_money',
        ]);

        $don = Don::where('reference', $request->reference)->firstOrFail();

        if ($request->channel === 'mobile_money') {
            $data = [
                'merchant' => env('FLEXPAY_MARCHAND'),
                'type' => '1',
                'phone' => $request->phone,
                'reference' => $don->reference,
                'amount' => $don->montant,
                'currency' => $don->currency,
                'callbackUrl' => env('APP_URL').'/payment/callback',
            ];
            $rep = initRequeteFlexPayMobile($data, $don);

            return response()->json($rep);
        }

        // Carte bancaire
        $retour = $this->flexPayService->initiatePayment(
            $don->montant,
            $don->currency,
            $don->reference,
            'Don - '.($don->nom ?? 'Anonyme'),
            'don'
        );

        if ($retour['rep']) {
            $don->update([
                'provider_reference' => $retour['orderNumber'],
                'etat' => 'En cours',
            ]);

            return response()->json([
                'reponse' => true,
                'redirect_url' => $retour['url'],
            ], 200);
        }

        return response()->json([
            'reponse' => false,
            'message' => $retour['message'],
        ], 400);
    }

    /**
     * Vérification du statut (polling Mobile Money)
     */
    public function checkTransactionStatus(Request $request)
    {
        $reference = $request->input('reference');
        $url = 'https://backend.flexpay.cd/api/rest/v1/check/'.urlencode($reference);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.env('FLEXPAY_API_TOKEN'),
        ]);
        $curlResponse = curl_exec($curl);
        $curlError = curl_errno($curl);
        curl_close($curl);

        if ($curlError) {
            return response()->json(['error' => 'Erreur FlexPay'], 500);
        }

        $jsonRes = json_decode($curlResponse, true);
        $transactionData = $jsonRes['transaction'] ?? [];
        $ref = $transactionData['reference'] ?? $reference;
        $don = Don::where('reference', $ref)->first();

        if (! $don) {
            return response()->json(['reponse' => false, 'message' => 'Don non trouvé'], 404);
        }

        $status = $jsonRes['transaction']['status'] ?? -1;

        switch ($status) {
            case 0: // Payée
                $don->update(['etat' => 'Payée']);

                return response()->json([
                    'reponse' => true,
                    'message' => 'Paiement effectué avec succès',
                    'status' => $status,
                ]);
            case 1: // Annulée
                $don->update(['etat' => 'Annulée']);

                return response()->json([
                    'reponse' => false,
                    'status' => $status,
                    'message' => $jsonRes['message'] ?? 'Paiement annulé',
                ]);
            case 2: // En attente
                return response()->json([
                    'reponse' => true,
                    'status' => $status,
                    'message' => 'Paiement en attente',
                    'orderNumber' => $don->provider_reference,
                ]);
            default:
                return response()->json([
                    'reponse' => false,
                    'status' => $status,
                    'message' => $jsonRes['message'] ?? 'Statut inconnu',
                ]);
        }
    }

    /**
     * Retour après paiement carte (success / cancel / decline)
     */
    public function paid($reference, $amount, $currency, $status)
    {
        $don = Don::where('reference', $reference)->first();

        if (! $don) {
            return redirect('/')->with('error', 'Don non trouvé');
        }

        switch ($status) {
            case 'success':
                $don->update(['etat' => 'Payée']);
                $msg = 'Merci pour votre don !';
                break;
            case 'cancel':
                $don->update(['etat' => 'Annulée']);
                $msg = 'Paiement annulé';
                break;
            case 'decline':
                $don->update(['etat' => 'Annulée']);
                $msg = 'Paiement refusé';
                break;
            default:
                return redirect('/')->with('error', 'Statut inconnu');
        }

        return redirect()->route('don.merci')->with([
            'message' => $msg,
            'reference' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'status' => $don->etat,
        ]);
    }
}

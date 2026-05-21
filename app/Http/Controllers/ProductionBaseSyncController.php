<?php

namespace App\Http\Controllers;

use App\Services\ProductionBaseDataSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Point d'entrée HTTP pour synchroniser les données de base via un token secret.
 */
class ProductionBaseSyncController extends Controller
{
    /**
     * @param Request $request Requête entrante
     * @param string $token Jeton fourni dans l'URL
     * @return JsonResponse Résultat de la synchronisation
     */
    public function __invoke(Request $request, string $token): JsonResponse
    {
        $expected = config('cmp.production_base_sync_token');

        if (! is_string($expected) || $expected === '' || ! hash_equals($expected, $token)) {
            abort(404);
        }

        try {
            app(ProductionBaseDataSyncService::class)->run();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Échec de la synchronisation : '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Données de base et rôles Shield synchronisés.',
        ]);
    }
}

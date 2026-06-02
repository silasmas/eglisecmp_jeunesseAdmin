<?php

namespace App\Http\Controllers;

use App\Models\RetreatParticipant;
use App\Services\PublicStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON du studio badges (participants pour génération des visuels).
 */
class RetreatBadgeStudioApiController extends Controller
{
    /**
     * Liste paginée des participants pour le studio badges.
     */
    public function participants(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'chambre' => ['nullable', 'string', 'max:50'],
            'atelier' => ['nullable', 'string', 'max:20'],
            'paiement_valide' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $perPage = min(max((int) ($validated['per_page'] ?? 100), 1), 200);
        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';
        $chambreFilter = isset($validated['chambre']) ? trim((string) $validated['chambre']) : '';
        $atelierFilter = isset($validated['atelier']) ? trim((string) $validated['atelier']) : '';

        $participants = RetreatParticipant::query()
            ->with(['chambre', 'atelier'])
            ->where('is_active', true)
            ->when(
                array_key_exists('paiement_valide', $validated),
                fn (Builder $query): Builder => $query->where('paiement_valide', (bool) $validated['paiement_valide']),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('postnom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->when($chambreFilter !== '' && $chambreFilter !== 'all', function (Builder $query) use ($chambreFilter): void {
                $query->whereHas('chambre', fn (Builder $chambreQuery): Builder => $chambreQuery->where('nom', $chambreFilter));
            })
            ->when($atelierFilter !== '' && $atelierFilter !== 'all', function (Builder $query) use ($atelierFilter): void {
                $query->whereHas('atelier', fn (Builder $atelierQuery): Builder => $atelierQuery->where('numero', (int) $atelierFilter));
            })
            ->orderBy('prenom')
            ->orderBy('nom')
            ->paginate($perPage);

        $storage = app(PublicStorageUrl::class);

        $data = collect($participants->items())->map(function (RetreatParticipant $participant) use ($storage): array {
            $chambreNom = $participant->chambre?->nom;
            $atelierNumero = $participant->atelier?->numero;

            return [
                'id' => (string) $participant->id,
                'prenom' => (string) ($participant->prenom ?? ''),
                'nom' => (string) ($participant->nom ?? ''),
                'photoUrl' => $storage->fromPath($participant->photo),
                'chambre' => $chambreNom ? (string) $chambreNom : '—',
                'atelier' => $atelierNumero !== null ? (int) $atelierNumero : 0,
                'paiementValide' => (bool) $participant->paiement_valide,
                'source' => $participant->paiement_valide ? 'Validé' : 'En attente',
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $participants->currentPage(),
                'last_page' => $participants->lastPage(),
                'per_page' => $participants->perPage(),
                'total' => $participants->total(),
            ],
        ]);
    }
}

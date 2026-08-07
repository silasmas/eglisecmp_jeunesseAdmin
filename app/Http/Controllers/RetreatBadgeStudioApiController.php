<?php

namespace App\Http\Controllers;

use App\Models\ChurchEvent;
use App\Models\RetreatParticipant;
use App\Services\PublicStorageUrl;
use App\Support\RetreatActiveEventScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON du studio badges (participants de la session / édition opérationnelle).
 */
class RetreatBadgeStudioApiController extends Controller
{
    /**
     * Métadonnées de session pour le studio (utilisateur + édition courante).
     *
     * @return JsonResponse
     */
    public function sessionContext(): JsonResponse
    {
        $user = request()->user();
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        $participantsQuery = RetreatActiveEventScope::applyToParticipants(
            RetreatParticipant::query()->where('is_active', true)
        );

        if ($event !== null) {
            $participantsQuery->where('event_id', $event->getKey());
        }

        return response()->json([
            'user' => [
                'id' => $user?->getKey(),
                'name' => $user?->name,
                'email' => $user?->email,
            ],
            'event' => $event === null ? null : [
                'id' => $event->getKey(),
                'name' => $event->name,
            ],
            'participants_total' => (int) $participantsQuery->count(),
        ]);
    }

    /**
     * Liste paginée des participants pour le studio badges (édition courante uniquement).
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
        $event = ChurchEvent::resolveOperationalLogisticsEvent();

        $query = RetreatActiveEventScope::applyToParticipants(
            RetreatParticipant::query()
                ->with(['chambre', 'atelier'])
                ->where('is_active', true)
        );

        if ($event !== null) {
            $query->where('event_id', $event->getKey());
        }

        $participants = $query
            ->when(
                array_key_exists('paiement_valide', $validated),
                fn (Builder $builder): Builder => $builder->where('paiement_valide', (bool) $validated['paiement_valide']),
            )
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $inner) use ($search): void {
                    $inner
                        ->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('postnom', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->when($chambreFilter !== '' && $chambreFilter !== 'all', function (Builder $builder) use ($chambreFilter): void {
                $builder->whereHas('chambre', fn (Builder $chambreQuery): Builder => $chambreQuery->where('nom', $chambreFilter));
            })
            ->when($atelierFilter !== '' && $atelierFilter !== 'all', function (Builder $builder) use ($atelierFilter): void {
                $builder->whereHas('atelier', fn (Builder $atelierQuery): Builder => $atelierQuery->where('numero', (int) $atelierFilter));
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
                'sexe' => $participant->sexe ? (string) $participant->sexe : null,
                'role' => $participant->role_participant
                    ? (string) $participant->role_participant
                    : null,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $participants->currentPage(),
                'last_page' => $participants->lastPage(),
                'per_page' => $participants->perPage(),
                'total' => $participants->total(),
                'event_id' => $event?->getKey(),
                'event_name' => $event?->name,
            ],
        ]);
    }
}

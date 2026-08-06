@php
    use App\Models\RetreatAtelier;
    use App\Services\RetreatAtelierProposalService;
    use App\Services\RetreatPlacementAssignmentService;

    $record = $getRecord();
    $placement = app(RetreatPlacementAssignmentService::class);
    $proposals = app(RetreatAtelierProposalService::class);
    $isAtelier = $record instanceof RetreatAtelier;
    $atelierRange = $isAtelier ? $placement->describeAtelierAgeRange($record) : null;

    $participants = $isAtelier
        ? $record->participants()->with(['chambre', 'atelier'])->orderBy('nom')->orderBy('prenom')->get()
        : (method_exists($record, 'participants')
            ? $record->participants()->with(['chambre', 'atelier'])->orderBy('nom')->orderBy('prenom')->get()
            : collect());

    $mismatchCount = $isAtelier
        ? $participants->filter(fn ($p) => $placement->isAgeOutsideAtelierRange($p, $record))->count()
        : 0;
@endphp

<div style="width: 100%; overflow-x: auto;">
    <div style="margin-bottom: .75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <div style="font-size: .85rem; color: #4b5563;">
            Total: <strong style="color: #111827;">{{ $participants->count() }}</strong> participant(s)
            @if ($isAtelier)
                · Tranche: <strong>{{ $atelierRange }}</strong>
            @endif
        </div>
        @if ($mismatchCount > 0)
            <div style="font-size: .82rem; font-weight: 700; color: #b91c1c; background: #fee2e2; border-radius: 999px; padding: .25rem .75rem;">
                {{ $mismatchCount }} mauvaise(s) affectation(s) d’âge
            </div>
        @endif
    </div>

    @if ($participants->isEmpty())
        <div style="border: 1px dashed #d1d5db; border-radius: .75rem; padding: 1rem; text-align: center; color: #6b7280;">
            Aucun participant affecte pour le moment.
        </div>
    @else
        <table style="width: 100%; border-collapse: collapse; font-size: .82rem;">
            <thead>
                <tr style="background: #f9fafb; color: #374151; text-align: left;">
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Nom complet</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Age</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Sexe</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Telephone</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Email</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Chambre</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Atelier</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Affectation âge</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Proposition</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Paiement</th>
                    <th style="padding: .65rem; border-bottom: 1px solid #e5e7eb;">Presence</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($participants as $participant)
                    @php
                        $isMismatch = $isAtelier && $placement->isAgeOutsideAtelierRange($participant, $record);
                        $proposalSummary = $isMismatch
                            ? $proposals->summaryForParticipant($participant)
                            : null;
                    @endphp
                    <tr style="{{ $isMismatch ? 'background:#fff7ed;' : '' }}">
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6; font-weight: 700; color: #111827;">
                            {{ $participant->full_name }}
                        </td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">{{ $participant->age ?? '-' }}</td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">{{ $participant->sexe ?? '-' }}</td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">{{ $participant->telephone ?? '-' }}</td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">{{ $participant->email ?? '-' }}</td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">{{ $participant->chambre?->nom ?? '-' }}</td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">{{ $participant->atelier?->numero ?? '-' }}</td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">
                            @if ($isMismatch)
                                <span style="display:inline-block;background:#fecaca;color:#991b1b;border-radius:999px;padding:.15rem .55rem;font-weight:700;font-size:.75rem;">
                                    Possibilité de réaffecter
                                </span>
                            @else
                                <span style="display:inline-block;background:#dcfce7;color:#166534;border-radius:999px;padding:.15rem .55rem;font-weight:600;font-size:.75rem;">
                                    OK
                                </span>
                            @endif
                        </td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6; max-width: 220px; color: #9a3412;">
                            {{ $proposalSummary ?? '—' }}
                        </td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">
                            {{ $participant->paiement_valide ? 'Valide' : 'Non valide' }}
                        </td>
                        <td style="padding: .65rem; border-bottom: 1px solid #f3f4f6;">
                            {{ $participant->present ? 'Present' : 'Absent' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div style="font-size:0.9rem;color:#374151;max-height:420px;overflow:auto;">
    <p style="margin:0 0 0.75rem;font-weight:600;color:#b42318;">
        Les éléments ci-dessous seront supprimés définitivement. Un historique compact sera conservé.
    </p>

    <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
        <thead>
            <tr style="background:#f3f4f6;text-align:left;">
                <th style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;">Participant</th>
                <th style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;">Contact</th>
                <th style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;">Événement</th>
                <th style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;">Inscription</th>
                <th style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;">Lié supprimé</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;vertical-align:top;">
                        #{{ $row['id'] }}<br>
                        <strong>{{ $row['identity'] }}</strong>
                    </td>
                    <td style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;vertical-align:top;">
                        {{ $row['email'] }}<br>
                        {{ $row['telephone'] }}
                    </td>
                    <td style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;vertical-align:top;">
                        {{ $row['event'] }}<br>
                        Chambre : {{ $row['chambre'] }}<br>
                        Atelier : {{ $row['atelier'] }}
                    </td>
                    <td style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;vertical-align:top;">
                        Statut : {{ $row['registration_status'] }}<br>
                        Paiement : {{ $row['paiement_valide'] }}<br>
                        Parrainage : {{ $row['sponsorship'] }}
                    </td>
                    <td style="padding:0.45rem 0.5rem;border:1px solid #e5e7eb;vertical-align:top;">
                        Paiements : {{ $row['payments_count'] }}<br>
                        Pointages : {{ $row['attendances_count'] }}<br>
                        Mouvements : {{ $row['movements_count'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:0.85rem;padding:0.65rem 0.75rem;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;">
        <strong>Totaux :</strong>
        {{ $totals['participants'] }} participant(s),
        {{ $totals['payments'] }} paiement(s),
        {{ $totals['transactions'] }} transaction(s),
        {{ $totals['attendances'] }} pointage(s),
        {{ $totals['movements'] }} mouvement(s),
        {{ $totals['policy_acknowledgements'] }} accusé(s) politique,
        {{ $totals['payment_failure_alerts'] }} alerte(s) paiement,
        {{ $totals['sponsorship_vouchers'] }} code(s) parrainage lié(s).
    </div>
</div>

@extends('layouts.retraite-inscription')

@section('title', 'Paiement carte confirmé')

@section('content')
  @php($apiBase = url('/api/v1/retreat/inscription'))

  <div class="card-return-shell" style="max-width: 640px; margin: 2rem auto 4rem; padding: 1.5rem;">
    <header class="info-box success" style="margin-bottom: 1.25rem;">
      <i class="bi bi-patch-check-fill"></i>
      <span><strong>Paiement carte enregistré.</strong> Votre transaction a été traitée avec succès.</span>
    </header>

    <div class="recap-section" style="margin-bottom: 1.25rem;">
      <div class="recap-row">
        <span class="recap-label">Référence</span>
        <span class="recap-value" id="crtRef">{{ $paymentReference }}</span>
      </div>
      <div class="recap-row">
        <span class="recap-label">Participant (ID)</span>
        <span class="recap-value">#{{ $participantId }}</span>
      </div>
    </div>

    <p class="field-hint" style="margin-bottom: 1rem;">
      Vous pouvez consulter le récapitulatif et les montants officiels avant de télécharger votre aperçu de badge dans le formulaire d’inscription.
    </p>

    <div class="badge-actions" style="display:flex; flex-wrap:wrap; gap:0.75rem;">
      <button type="button" class="btn btn-next" id="btnOpenReceipt">
        <i class="bi bi-receipt"></i> Voir récap / reçu
      </button>
      <a class="btn btn-submit" href="{{ route('retraite.inscription', ['resume_payment_ref' => $paymentReference]) }}">
        <i class="bi bi-person-badge"></i> Étape suivante · badge
      </a>
    </div>

    <div id="receiptPanel" class="hidden" style="margin-top: 1rem; padding: 1rem; border-radius: 12px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);"></div>
  </div>

  @push('scripts')
    <script>
      (function () {
        const ref = @json($paymentReference);
        const base = @json(rtrim($apiBase, '/'));
        const btn = document.getElementById('btnOpenReceipt');
        const panel = document.getElementById('receiptPanel');
        btn.addEventListener('click', async function () {
          panel.classList.remove('hidden');
          panel.textContent = 'Chargement…';
          try {
            const r = await fetch(base + '/payments/receipt?reference=' + encodeURIComponent(ref));
            const j = await r.json();
            if (!r.ok) {
              panel.innerHTML = '<span class="text-danger">' + (j.message || 'Impossible de charger le reçu.') + '</span>';
              return;
            }
            const d = j.data;
            panel.innerHTML = '<pre style="white-space:pre-wrap; margin:0; font-family: inherit; font-size:0.9rem;">'
              + escapeHtml(JSON.stringify(d, null, 2))
              + '</pre>';
          } catch (e) {
            panel.innerHTML = '<span>Erreur réseau.</span>';
          }
        });
        function escapeHtml(text) {
          const div = document.createElement('div');
          div.textContent = typeof text === 'string' ? text : String(text);
          return div.innerHTML;
        }
      })();
    </script>
  @endpush
@endsection

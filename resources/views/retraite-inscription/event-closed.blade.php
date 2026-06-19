@extends('layouts.retraite-inscription')

@section('title', 'Retraite clôturée — Jeunesse CMP')

@section('content')
  <div class="cmp-page-shell" style="min-height: 60vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">
    <div class="info-box warning" style="max-width: 560px; text-align: center; padding: 2rem;">
      <div style="font-size: 2.5rem; margin-bottom: 0.75rem;" aria-hidden="true">
        <i class="bi bi-calendar-x"></i>
      </div>
      <h1 style="font-size: 1.35rem; margin: 0 0 0.75rem;">Retraite clôturée</h1>
      <p style="margin: 0 0 0.5rem; line-height: 1.6;">
        Cette page concernait <strong>{{ $event->name }}</strong>.
      </p>
      @if($event->end_at)
        <p style="margin: 0 0 1rem; color: #6b5d65; font-size: 0.95rem;">
          Fin de l'événement : {{ $event->end_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
        </p>
      @endif
      <p style="margin: 0 0 1.25rem; line-height: 1.6;">
        L'accès public (inscription, billet, justificatif, chambre, atelier) n'est plus disponible pour cette édition.
        Pour toute question, contactez le département de la jeunesse.
      </p>
      <a href="{{ url('/') }}" class="btn btn-next" style="display: inline-flex; align-items: center; gap: 0.35rem;">
        <i class="bi bi-house"></i> Retour à l'accueil
      </a>
    </div>
  </div>
@endsection

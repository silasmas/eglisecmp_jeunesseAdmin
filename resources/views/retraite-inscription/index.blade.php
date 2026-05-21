@extends('layouts.retraite-inscription')

@section('content')
  @include('retraite-inscription.partials.gate-overlay')

  <div id="retraiteMainShell" class="retraite-main-shell hidden">
    @include('retraite-inscription.partials.hero')
    @include('retraite-inscription.partials.stepper-mobile')

    <div class="app-layout">
      @include('retraite-inscription.partials.stepper-sidebar')

      <main class="content-area">
        @include('retraite-inscription.partials.steps-content')
      </main>
    </div>

    @include('retraite-inscription.partials.photo-crop-modal')
    @include('retraite-inscription.partials.mandatory-policies-modal')
  </div>
@endsection

@push('scripts')
  @if(session('inscription_card_status'))
    <script>
      window.__RETRAITE_CARD_RETURN__ = {
        status: @json(session('inscription_card_status')),
        ref: @json(session('inscription_payment_ref'))
      };
    </script>
  @endif
@endpush

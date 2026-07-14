@php
  $participantName = $participant?->full_name ?? 'Participant';
  $proofUrl = $proofUrl ?? null;
  $mediaKind = $mediaKind ?? 'unknown';
@endphp

<div class="fi-payment-proof-modal space-y-4">
  @if(filled($proofUrl))
    @if($mediaKind === 'image')
      <div class="flex justify-center overflow-auto rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
        <img
          src="{{ $proofUrl }}"
          alt="Preuve de paiement — {{ $participantName }}"
          class="max-h-[70vh] max-w-full rounded-lg object-contain"
        >
      </div>
    @elseif($mediaKind === 'pdf')
      <iframe
        src="{{ $proofUrl }}"
        title="Preuve de paiement — {{ $participantName }}"
        class="h-[70vh] w-full rounded-xl border border-gray-200 dark:border-gray-700"
      ></iframe>
    @else
      <iframe
        src="{{ $proofUrl }}"
        title="Preuve de paiement — {{ $participantName }}"
        class="h-[70vh] w-full rounded-xl border border-gray-200 dark:border-gray-700"
      ></iframe>
    @endif

    <p class="text-sm text-gray-500 dark:text-gray-400">
      Participant : <strong>{{ $participantName }}</strong>
    </p>
  @else
    <p class="text-sm text-gray-600 dark:text-gray-300">
      Aucune preuve de paiement disponible pour ce participant.
    </p>
  @endif
</div>
